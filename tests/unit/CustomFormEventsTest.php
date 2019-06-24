<?php

namespace ABWebDevelopers\Forms\Tests\Unit;

use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Submission;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use ABWebDevelopers\Forms\Components\CustomForm;
use Illuminate\Validation\Validator;
use PluginTestCase;
use Input;
use Event;

class CustomFormEventsTest extends PluginTestCase
{

    protected $form;

    /**
     * Reset the settings with optional overrides
     * 
     * @param array $data
     * @return void
     */
    private function initSettings(array $data = []) {
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
    private function getForm(array $data = []) {
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
    private function getComponent(string $formCode = null) {
        $this->getForm();

        $component = new CustomForm();

        $component->setProperty('formCode', ($formCode === null) ? $this->form->code : $formCode);

        return $component;
    }

    // ==== TESTS

    function testEventBeforeRun()
    {
        $this->initSettings();

        $component = $this->getComponent();

        Event::listen('abweb.forms.beforeRun', function(CustomForm $customForm) use ($component) {
            $this->assertEquals($component, $customForm);
        });

        $component->onRUn();
    }

    function testEventAfterRun()
    {
        $this->initSettings();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.afterRun', function(CustomForm $customForm) use ($component) {
            $this->assertEquals($component, $customForm);
        });

        $component->onRUn();
    }

    function testEventBeforeFormSubmit()
    {
        $this->initSettings();

        $component = $this->getComponent();

        Event::listen('abweb.forms.beforeFormSubmit', function(CustomForm $customForm) use ($component) {
            $this->assertEquals($component, $customForm);
        });

        $component->onFormSubmit();
    }

    function testEventBeforeValidateForm()
    {
        $this->initSettings();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        $expectRules = [
            'name' => 'required|string|min:1|max:100',
            'email' => 'required|string|email',
            'comment' => 'required|string|min:1|max:200',
        ];

        Event::listen('abweb.forms.beforeValidateForm', function(CustomForm $customForm, array &$data, array &$rules, array &$messages, Validator $validator) use ($post, $expectRules) {
            $this->assertEquals($data, $post);
            $this->assertEquals($rules, $expectRules);
            $this->assertEquals(array_keys($messages), array_keys($expectRules));
        });

        $component->onFormSubmit();
    }

    function testEventOnValidateFormFail()
    {
        $this->initSettings();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'validxample.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        $expectRules = [
            'name' => 'required|string|min:1|max:100',
            'email' => 'required|string|email',
            'comment' => 'required|string|min:1|max:200',
        ];

        Event::listen('abweb.forms.onValidateFormFail', function(CustomForm $customForm, array $data, array $rules, array $messages, Validator $validator) use ($post, $expectRules) {
            $this->assertEquals($data, $post);
            $this->assertEquals($rules, $expectRules);
            $this->assertEquals(array_keys($messages), array_keys($expectRules));
        });

        $component->onFormSubmit();
    }

    function testEventAfterValidateForm()
    {
        $this->initSettings();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.afterValidateForm', function(CustomForm $customForm, array $data, array $rules, array $messages) use ($post) {
            $this->assertEquals($post, $data);
        });

        $component->onFormSubmit();
    }

    function testEventOnRecaptchaFail()
    {
        $this->initSettings([
            'enable_recaptcha' => true,
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
            'g-recaptcha-response' => 'Invalid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.onRecaptchaFail', function(CustomForm $customForm, string $recaptchaResponse) use ($post) {
            $this->assertEquals($recaptchaResponse, $post['g-recaptcha-response']);
        });

        $component->onFormSubmit();
    }

    function testEventAfterFormSubmit()
    {
        $this->initSettings();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.afterFormSubmit', function(CustomForm $customForm, $data, $response) use ($post) {
            $this->assertEquals($data, $post);
            $this->assertEquals('Illuminate\Http\JsonResponse', get_class($response));
            $this->assertTrue($response->getData()->success);
        });

        $component->onFormSubmit();
    }

    function testEventBeforeSendNotification()
    {
        $this->initSettings([
            'send_notifications' => true,
            'queue_emails' => true,
            'notification_recipients' => 'bob@example.org',
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.beforeSendNotification', function(CustomForm $customForm, array $data, $to) {
            $this->assertEquals($to, 'bob@example.org');
        });

        $component->onFormSubmit();
    }

    function testEventBeforeNotificationValidation()
    {
        $this->initSettings([
            'send_notifications' => true,
            'queue_emails' => true,
            'notification_recipients' => 'bob@example.org,rob@example.org',
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.beforeNotificationValidation', function(CustomForm $customForm, array $data, &$to, array &$rules, Validator &$validator) {
            $this->assertEquals($to, [
                'bob@example.org',
                'rob@example.org'
            ]);
            $this->assertEquals($rules, [
                'required|email',
                'required|email'
            ]);
        });

        $component->onFormSubmit();
    }

    function testEventOnNotificationValidationFail()
    {
        $this->initSettings([
            'send_notifications' => true,
            'queue_emails' => true,
            'notification_recipients' => 'rob@example.org,BobDidNotGetTheMemoOnWhatValidEmailAddressesAre',
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.onNotificationValidationFail', function(CustomForm $customForm, array $data, $to, array $rules, Validator $validator) {
            $messages = $validator->messages()->toArray();
            $this->assertEquals(1, count($messages));
            $this->assertTrue(isset($messages[1]));
        });

        $component->onFormSubmit();
    }

    function testEventOnNotificationValidationSuccess()
    {
        $this->initSettings([
            'send_notifications' => true,
            'queue_emails' => true,
            'notification_recipients' => 'rob@example.org,bob@example.org',
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.onNotificationValidationSuccess', function(CustomForm $customForm, array $data, array $to, array $rules) {
            $this->assertEquals(2, count($to));
            $this->assertEquals(2, count($rules));
        });

        $component->onFormSubmit();
    }

    function testEventAfterSendNotification()
    {
        $this->initSettings([
            'send_notifications' => true,
            'queue_emails' => true,
            'notification_recipients' => 'rob@example.org,bob@example.org',
        ]);

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.afterSendNotification', function(CustomForm $customForm, array $data, bool $success) {
            $this->assertEquals(true, $success);
        });

        $component->onFormSubmit();
    }

    function testEventBeforeSendAutoReply()
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

        Event::listen('abweb.forms.beforeSendAutoReply', function(CustomForm $customForm, array &$data, string &$toEmail, string &$toName) use ($post) {
            $this->assertEquals($post['name'], $toName);
            $this->assertEquals($post['email'], $toEmail);
        });

        $component->onFormSubmit();
    }

    function testEventOnAutoReplyValidationFailOnEmail()
    {
        $this->initSettings([
            'auto_reply' => true,
            'queue_emails' => true,
        ]);

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

        Event::listen('abweb.forms.onAutoReplyValidationFail', function(CustomForm $customForm, array $data, $toEmail, $toName, string $failedOn) {
            $this->assertEquals('email', $failedOn);
        });

        $component->onFormSubmit();
    }

    function testEventOnAutoReplyValidationFailOnName()
    {
        $this->initSettings([
            'auto_reply' => true,
            'queue_emails' => true,
        ]);

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

        Event::listen('abweb.forms.onAutoReplyValidationFail', function(CustomForm $customForm, array $data, $toEmail, $toName, string $failedOn) {
            $this->assertEquals('name', $failedOn);
        });

        $component->onFormSubmit();
    }

    function testEventAfterSendAutoReply()
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

        Event::listen('abweb.forms.afterSendAutoReply', function(CustomForm $customForm, array $data, bool $success) {
            $this->assertEquals(true, $success);
        });

        $component->onFormSubmit();
    }

    function testEventBeforeSaveSubmission()
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

        Event::listen('abweb.forms.beforeSaveSubmission', function(CustomForm $customForm, array &$submissionData) use ($post) {
            $this->assertEquals($submissionData['data'], $post);
        });

        $component->onFormSubmit();
    }

    function testEventAfterSaveSubmission()
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

        Event::listen('abweb.forms.afterSaveSubmission', function(CustomForm $customForm, Submission $submission) use ($post) {
            $this->assertEquals(get_class($submission), Submission::class);
            $this->assertEquals($submission->data, $post);
        });

        $component->onFormSubmit();
    }

    function testEventBeforeSetTemplateVars()
    {
        $this->initSettings();

        $component = $this->getComponent();

        $post = [
            'name' => 'valid',
            'email' => 'valid@example.org',
            'comment' => 'valid',
        ];
        Input::replace($post);

        Event::listen('abweb.forms.beforeSetTemplateVars', function(CustomForm $customForm, array &$vars) use ($post) {
            $this->assertEquals($vars['fields'], [
                'name' => [
                    'name' => 'Name',
                    'type' => 'text',
                    'description' => 'Full Name',
                    'value' => $post['name'],
                ],
                'email' => [
                    'name' => 'Email',
                    'type' => 'email',
                    'description' => 'Email Address',
                    'value' => $post['email'],
                ],
                'comment' => [
                    'name' => 'Comment',
                    'type' => 'textarea',
                    'description' => 'User\'s Comments',
                    'value' => $post['comment'],
                ],
            ]);
            $this->assertTrue(array_key_exists('form', $vars));
            $this->assertTrue(array_key_exists('moreInfoLink', $vars));
        });

        $component->onFormSubmit();
    }

}