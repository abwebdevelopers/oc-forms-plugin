<?php

namespace ABWebDevelopers\Forms\Tests\Unit;

use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Submission;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use ABWebDevelopers\Forms\Components\CustomForm;
use PluginTestCase;
use Input;
use Mail;
use Lang;

class CustomFormTest extends PluginTestCase
{
    protected $form;

    /**
     * Reset the settings with optional overrides
     *
     * @param array $data
     * @return void
     */
    private function initSettings(array $data = [])
    {
        // Merge per-test settings with defaults (acting as a reset)
        $data = array_merge([
            'enable_caching' => false,
            'on_success_redirect' => '/test',
            'on_success_message' => 'Test',
            'on_success' => 'hide',
            'store_ips' => false,
            'saves_data' => false,
            'enable_recaptcha' => false,
            'queue_emails' => false,
            'send_notifications' => false,
            'auto_reply' => false,
            'notification_recipients' => '',
        ], $data);

        foreach ($data as $key => $value) {
            Settings::set($key, $value);
        }
    }

    /**
     * Return an instance of Form model
     *
     * @param array $data
     * @return Form
     */
    private function getForm(array $data = [])
    {
        if (!empty($this->form)) {
            return $this->form;
        }

        $data = array_merge([
            'title' => 'Example',
            'code' => 'example',
            'saves_data' => null,
            'send_notifications' => null,
            'auto_reply' => null,
            'notification_recipients' => null,
        ], $data);

        $this->form = Form::updateOrCreate([
            'id' => 1
        ], $data);

        $name = Field::updateOrCreate([
            'form_id' => $this->form->id,
            'code' => 'name',
        ], [
            'name' => 'Name',
            'type' => 'text',
            'description' => 'Full Name',
            'sort_order' => 1,
            'validation_rules' => 'required|string|min:1|max:100'
        ]);

        $email = Field::updateOrCreate([
            'form_id' => $this->form->id,
            'code' => 'email',
        ], [
            'name' => 'Email',
            'type' => 'email',
            'description' => 'Email Address',
            'sort_order' => 2,
            'validation_rules' => 'required|string|email'
        ]);

        $comment = Field::updateOrCreate([
            'form_id' => $this->form->id,
            'code' => 'comment',
        ], [
            'name' => 'Comment',
            'type' => 'textarea',
            'description' => 'User\'s Comments',
            'sort_order' => 3,
            'validation_rules' => 'required|string|min:1|max:200'
        ]);

        $this->form->auto_reply_email_field = $email->id;
        $this->form->auto_reply_name_field = $name->id;
        $this->form->save();

        return $this->form;
    }

    /**
     * Return an instance of CustomForm component
     *
     * @param string $formCode
     * @return CustomForm
     */
    private function getComponent(string $formCode = null)
    {
        $this->getForm();

        $component = new CustomForm();

        $component->setProperty('formCode', ($formCode === null) ? $this->form->code : $formCode);

        return $component;
    }

    /**
     * Test that submitting the form with no data fails and returns 400 "No data supplied"
     */
    public function testSubmitFailsOnNoData()
    {
        $component = $this->getComponent();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 400);

        $this->assertEquals($resp->getData(), (object)[
            'success' => false,
            'error' => 'abwebdevelopers.forms::lang.customForm.validation.noData' // 'No data supplied'
        ]);
    }

    /**
     * Test that submitting the form with invalid data fails and returns 400 with field errors
     */
    public function testSubmitFailsOnInvalidData()
    {
        $component = $this->getComponent();

        Input::replace([
            'name' => '',
            'email' => 'invalid',
            'comment' => ''
        ]);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 400);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals(array_keys((array) $resp->getData()->errors), ['name', 'email', 'comment']);
    }

    /**
     * Test that submitting the form with no recaptcha data fails and returns respective error
     */
    public function testSubmitFailsOnNoRecaptcha()
    {
        $this->initSettings([
            'enable_recaptcha' => true
        ]);
        $component = $this->getComponent();

        Input::replace([
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid'
        ]);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 400);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals(array_keys((array) $resp->getData()->errors), ['g-recaptcha-response']);
    }

    /**
     * Test that submitting the form with invalid recaptcha data fails and returns respective error
     */
    public function testSubmitFailsOnInvalidRecaptcha()
    {
        $this->initSettings([
            'enable_recaptcha' => true
        ]);
        $component = $this->getComponent();

        Input::replace([
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
            'g-recaptcha-response' => 'TESTING_INVALID'
        ]);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 400);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals(array_keys((array) $resp->getData()->errors), ['g-recaptcha-response']);
    }

    /**
     * Test that submitting the form will save a submission, if told to
     */
    public function testSubmissionIsSavedWhenSetTo()
    {
        $this->initSettings([
            'saves_data' => true,
        ]);
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);

        $submission = $component->getSubmission();
        $this->assertEquals(get_class($submission), Submission::class);
        $this->assertEquals($submission->data, $post);
        $this->assertTrue(empty($submission->ip));
    }

    /**
     * Test that submitting the form will save a submission, if told to
     */
    public function testSuccessfulSubmissionResponseIsCorrect()
    {
        $redirect = '/thank-you';
        $message = 'Chur bro';
        $success = 'redirect';

        $this->initSettings([
            'saves_data' => true,
            'on_success_redirect' => $redirect,
            'on_success_message' => $message,
            'on_success' => $success,
        ]);
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
        $this->assertEquals($resp->getData()->action, $success);
        $this->assertEquals($resp->getData()->url, $redirect);
        $this->assertEquals($resp->getData()->message, $message);

        $submission = $component->getSubmission();
        $this->assertEquals(get_class($submission), Submission::class);
        $this->assertEquals($submission->data, $post);
    }

    /**
     * Test that submitting the form will save a submission with IP, if told to
     */
    public function testSubmissionIsSavedWithIpWhenSetTo()
    {
        $this->initSettings([
            'saves_data' => true,
            'store_ips' => true,
        ]);
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
        $this->assertEquals($resp->getData()->message, $this->form->onSuccessMessage());

        $submission = $component->getSubmission();
        $this->assertEquals(get_class($submission), Submission::class);
        $this->assertEquals($submission->data, $post);
        $this->assertTrue(!empty($submission->ip));
    }

    /**
     * Test that submitting the form will not save a submission, if not told to
     */
    public function testSubmissionIsNotSavedWhenSetNotTo()
    {
        $this->initSettings();
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
        $this->assertEquals($resp->getData()->message, $this->form->onSuccessMessage());

        $submission = $component->getSubmission();
        $this->assertEquals($submission, null);
    }

    /**
     * Test that notification alerts will be sent
     */
    public function testNotificationSendingWorks()
    {
        $this->initSettings([
            'send_notifications' => true,
            'notification_recipients' => 'bob@example.org',
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('send')->once();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
    }

    /**
     * Test that notification alerts will be queued
     */
    public function testNotificationQueueingWorks()
    {
        $this->initSettings([
            'queue_emails' => true,
            'send_notifications' => true,
            'notification_recipients' => 'bob@example.org',
        ]);
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('queue')->once();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
    }

    /**
     * Test that notifications alerts are skipped if no recipients are specified
     */
    public function testNotificationSendingSkipsIfNoRecipients()
    {
        $this->initSettings([
            'send_notifications' => true,
            'notification_recipients' => '',
        ]);
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('send')->never();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
    }

    /**
     * Test that notification alerts are aborted if invalid recipients are specified
     */
    public function testNotificationSendingAbortsIfInvalidRecipients()
    {
        $this->initSettings([
            'send_notifications' => true,
            'notification_recipients' => 'invalid',
        ]);
        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('send')->never();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 501);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals($resp->getData()->error, Lang::get('abwebdevelopers.forms::lang.customForm.validation.invalidNotificationRecipients'));
    }

    /**
     * Test that auto reply alert is sent if email/name fields specified
     */
    public function testAutoReplySendsWithAutoReplyFieldsSpecified()
    {
        $this->initSettings([
            'auto_reply' => true,
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('send')->once();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
    }

    /**
     * Test that auto reply alert is aborted if email field is not specified
     */
    public function testAutoReplyAbortsWithoutAutoReplyEmailFieldSpecified()
    {
        $this->initSettings([
            'auto_reply' => true,
        ]);

        // No email field specified
        $this->getForm();
        $this->form->auto_reply_email_field_id = null;
        $this->form->save();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('send')->never();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 501);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals($resp->getData()->error, Lang::get('abwebdevelopers.forms::lang.customForm.validation.noAutoReplyEmailField'));
    }

    /**
     * Test that auto reply alert is aborted if name field is not specified
     */
    public function testAutoReplyAbortsWithoutAutoReplyNameFieldSpecified()
    {
        $this->initSettings([
            'auto_reply' => true,
        ]);

        // No name field specified
        $this->getForm();
        $this->form->auto_reply_name_field_id = null;
        $this->form->save();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('send')->never();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 501);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals($resp->getData()->error, Lang::get('abwebdevelopers.forms::lang.customForm.validation.noAutoReplyNameField'));
    }

    /**
     * Test that auto reply alert is can be queued
     */
    public function testAutoReplyCanQueue()
    {
        $this->initSettings([
            'auto_reply' => true,
            'queue_emails' => true,
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Mail::shouldReceive('queue')->once();

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
    }

    /**
     * Test that the throttle system works
     */
    public function testTooManyAttemptsLocksUserOut()
    {
        $this->initSettings([
            'saves_data' => true,
            'store_ips' => true,
            'enable_ip_restriction' => true,
            'max_requests_per_day' => 3,
            'throttle_message' => 'Chill'
        ]);

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        // Attempt 1 Accepted
        $component = $this->getComponent();
        $resp = $component->onFormSubmit();
        $this->assertEquals($resp->getStatusCode(), 200);

        // Attempt 2 Accepted
        $component = $this->getComponent();
        $resp = $component->onFormSubmit();
        $this->assertEquals($resp->getStatusCode(), 200);

        // Attempt 3 Accepted
        $component = $this->getComponent();
        $resp = $component->onFormSubmit();
        $this->assertEquals($resp->getStatusCode(), 200);

        // Attempt 4 Locked Out
        $component = $this->getComponent();
        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 429);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals($resp->getData()->error, 'Chill');
    }

    /**
     * Test that submitting the form with invalid option (for select, radio, checkbox fields) fails and returns 400 with field errors
     */
    public function testSubmitFailsOnInvalidOption()
    {
        $this->getForm();
        $field = Field::updateOrCreate([
            'form_id' => $this->form->id,
            'code' => 'option',
        ], [
            'name' => 'Option',
            'type' => 'select',
            'options' => [
                [
                    'option_code' => 'apples',
                    'option_label' => 'Apples',
                ],
                [
                    'option_code' => 'bananas',
                    'option_label' => 'Bananas',
                ],
                [
                    'option_code' => 'oranges',
                    'option_label' => 'Oranges',
                ]
            ],
            'required' => true
        ]);

        $component = $this->getComponent();

        Input::replace([
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'rehrtjhrtyj',
            'option' => 'INVALID'
        ]);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 400);
        $this->assertEquals($resp->getData()->success, false);
        $this->assertEquals(array_keys((array) $resp->getData()->errors), ['option.0']);
    }

    /**
     * Test that submitting the form with valid option (for select, radio, checkbox fields) passes and returns 200
     */
    public function testSubmitPassesOnValidOption()
    {
        $this->getForm();
        $field = Field::updateOrCreate([
            'form_id' => $this->form->id,
            'code' => 'option',
        ], [
            'name' => 'Option',
            'type' => 'select',
            'options' => [
                [
                    'option_code' => 'Apples',
                    'option_label' => 'Apples',
                ],
                [
                    'option_code' => 'Bananas',
                    'option_label' => 'Bananas',
                ],
                [
                    'option_code' => 'Oranges',
                    'option_label' => 'Oranges',
                ]
            ],
            'required' => true
        ]);

        $component = $this->getComponent();

        Input::replace([
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'rehrtjhrtyj',
            'option' => 'Apples'
        ]);

        $resp = $component->onFormSubmit();

        $this->assertEquals($resp->getStatusCode(), 200);
        $this->assertEquals($resp->getData()->success, true);
    }
}
