<?php

namespace ABWebDevelopers\Forms\Tests\Unit;

use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use PluginTestCase;

class SettingsTest extends PluginTestCase
{

    private function getForm(bool $withData = false) {
        if ($withData) {
            return Form::create([
                'title' => 'Example',
                'code' => 'example',
                'override_styling' => false,
                'form_class' => 'form class',
                'field_class' => 'field class',
                'row_class' => 'row class',
                'group_class' => 'group class',
                'label_class' => 'label class',
                'submit_class' => 'submit class',
                'submit_text' => 'submit',
                'enable_cancel' => false,
                'cancel_class' => 'cancel class',
                'cancel_text' => 'cancel',
                'override_privacy' => false,
                'saves_data' => false,
                'override_antispam' => false,
                'enable_recaptcha' => false,
                'enable_ip_restriction' => false,
                'max_requests_per_day' => 5,
                'throttle_message' => 'throttle',
                'override_emailing' => false,
                'send_notifications' => false,
                'notification_template' => 'abwebdevelopers.forms::mail.notification',
                'notification_recipients' => 'bob@example.org,rob@example.org',
                'auto_reply' => false,
                'auto_reply_email_field_id' => null,
                'auto_reply_name_field_id' => null,
                'auto_reply_template' => 'abwebdevelopers.forms::mail.autoreply',
            ]);
        }

        return new Form();
    }

    public function testAutoReplyEmailFieldSelectSuggestsEmailFirst()
    {
        $form = $this->getForm(1);

        $fields = [
            [
                'name' => 'Test Text Field',
                'type' => 'text',
                'code' => 'example',
            ],
            [
                'name' => 'Test Email Field',
                'type' => 'email',
                'code' => 'email',
            ],
            [
                'name' => 'Test Text Field 2',
                'type' => 'text',
                'code' => 'example2',
            ],
            [
                'name' => 'Should email customer?',
                'type' => 'boolean',
                'code' => 'send_email',
            ]
        ];
        
        foreach ($fields as $field) {
            $field = Field::create($field + [
                'form_id' => $form->id
            ]);
        }

        $suggests = $form->getAutoReplyEmailFieldIdOptions();

        $this->assertEquals(4, count($suggests));

        $first = array_shift($suggests);
        $this->assertEquals('Test Email Field [email]', $first);
        
        $second = array_shift($suggests);
        $this->assertEquals('Should email customer? [send_email]', $second);
    }

    public function testAutoReplyNameFieldSelectSuggestsNameFirst()
    {
        $form = $this->getForm(1);

        $fields = [
            [
                'name' => 'Test Text Field',
                'type' => 'text',
                'code' => 'example',
            ],
            [
                'name' => 'Test Name Field',
                'type' => 'text',
                'code' => 'name',
            ],
            [
                'name' => 'Has Name?',
                'type' => 'boolean',
                'code' => 'has_name',
            ],
            [
                'name' => 'Test Text Field2',
                'type' => 'text',
                'code' => 'example2',
            ],
        ];
        
        foreach ($fields as $field) {
            $field = Field::create($field + [
                'form_id' => $form->id
            ]);
        }

        $suggests = $form->getAutoReplyNameFieldIdOptions();

        $this->assertEquals(4, count($suggests));

        $first = array_shift($suggests);
        $this->assertEquals('Test Name Field [name]', $first);
        
        $second = array_shift($suggests);
        $this->assertEquals('Has Name? [has_name]', $second);
    }

    public function testCanOverrideRecaptchaEnabled()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = true;
        $override = false;

        // Set global value, expect to be able to retrieve it
        Settings::set('enable_recaptcha', $value);
        $this->assertEquals($value, $form->recaptchaEnabled());

        // Override global value without allowing override, expect same as before
        $form->enable_recaptcha = $override;
        $this->assertEquals($value, $form->recaptchaEnabled());

        // Now allow overriding, expect form value to take effect
        $form->override_antispam = true;
        $this->assertEquals($override, $form->recaptchaEnabled());
    }

    public function testCanOverrideHasIpRestriction()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = false;
        $override = true;

        // Set global value, expect to be able to retrieve it
        Settings::set('enable_ip_restriction', $value);
        $this->assertEquals($value, $form->hasIpRestriction());

        // Override global value without allowing override, expect same as before
        $form->enable_ip_restriction = $override;
        $this->assertEquals($value, $form->hasIpRestriction());

        // Now allow overriding, expect form value to take effect
        $form->override_antispam = true;
        $this->assertEquals($override, $form->hasIpRestriction());
    }

    public function testCanOverrideMaxRequestsPerDay()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 65;
        $override = 74;

        // Set global value, expect to be able to retrieve it
        Settings::set('max_requests_per_day', $value);
        $this->assertEquals($value, $form->maxRequestsPerDay());

        // Override global value without allowing override, expect same as before
        $form->max_requests_per_day = $override;
        $this->assertEquals($value, $form->maxRequestsPerDay());

        // Now allow overriding, expect form value to take effect
        $form->override_antispam = true;
        $this->assertEquals($override, $form->maxRequestsPerDay());
    }

    public function testCanOverrideThrottleMessage()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'Generic message';
        $override = 'Specific message for form';

        // Set global value, expect to be able to retrieve it
        Settings::set('throttle_message', $value);
        $this->assertEquals($value, $form->throttleMessage());

        // Override global value without allowing override, expect same as before
        $form->throttle_message = $override;
        $this->assertEquals($value, $form->throttleMessage());

        // Now allow overriding, expect form value to take effect
        $form->override_antispam = true;
        $this->assertEquals($override, $form->throttleMessage());
    }

    public function testCanOverrideSavesData()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = true;
        $override = false;

        // Set global value, expect to be able to retrieve it
        Settings::set('saves_data', $value);
        $this->assertEquals($value, $form->savesData());

        // Override global value without allowing override, expect same as before
        $form->saves_data = $override;
        $this->assertEquals($value, $form->savesData());

        // Now allow overriding, expect form value to take effect
        $form->override_privacy = true;
        $this->assertEquals($override, $form->savesData());
    }

    public function testCanOverrideSendsNotifications()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = true;
        $override = false;

        // Set global value, expect to be able to retrieve it
        Settings::set('send_notifications', $value);
        $this->assertEquals($value, $form->sendsNotifications());

        // Override global value without allowing override, expect same as before
        $form->send_notifications = $override;
        $this->assertEquals($value, $form->sendsNotifications());

        // Now allow overriding, expect form value to take effect
        $form->override_emailing = true;
        $this->assertEquals($override, $form->sendsNotifications());
    }

    public function testCanOverrideNotificationTemplate()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'abwebdevelopers.forms::mail.notification';
        $override = 'custom.forms::mail.notification';

        // Set global value, expect to be able to retrieve it
        Settings::set('notification_template', $value);
        $this->assertEquals($value, $form->notificationTemplate());

        // Override global value without allowing override, expect same as before
        $form->notification_template = $override;
        $this->assertEquals($value, $form->notificationTemplate());

        // Now allow overriding, expect form value to take effect
        $form->override_emailing = true;
        $this->assertEquals($override, $form->notificationTemplate());
    }

    public function testCanOverrideNotificationRecipients()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'bob@example.org';
        $override = 'rob@example.org';

        // Set global value, expect to be able to retrieve it
        Settings::set('notification_recipients', $value);
        $this->assertEquals($value, $form->notificationRecipients());

        // Override global value without allowing override, expect same as before
        $form->notification_recipients = $override;
        $this->assertEquals($value, $form->notificationRecipients());

        // Now allow overriding, expect form value to take effect
        $form->override_emailing = true;
        $this->assertEquals($override, $form->notificationRecipients());
    }

    public function testCanOverrideAutoReply()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = false;
        $override = true;

        // Set global value, expect to be able to retrieve it
        Settings::set('auto_reply', $value);
        $this->assertEquals($value, $form->autoReply());

        // Override global value without allowing override, expect same as before
        $form->auto_reply = $override;
        $this->assertEquals($value, $form->autoReply());

        // Now allow overriding, expect form value to take effect
        $form->override_emailing = true;
        $this->assertEquals($override, $form->autoReply());
    }

    public function testCanOverrideAutoReplyTemplate()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'abwebdevelopers.forms::mail.autoreply';
        $override = 'custom.forms::mail.autoreply';

        // Set global value, expect to be able to retrieve it
        Settings::set('auto_reply_template', $value);
        $this->assertEquals($value, $form->autoReplyTemplate());

        // Override global value without allowing override, expect same as before
        $form->auto_reply_template = $override;
        $this->assertEquals($value, $form->autoReplyTemplate());

        // Now allow overriding, expect form value to take effect
        $form->override_emailing = true;
        $this->assertEquals($override, $form->autoReplyTemplate());
    }

    public function testCanOverrideFormClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form';
        $override = 'custom-form';

        // Set global value, expect to be able to retrieve it
        Settings::set('form_class', $value);
        $this->assertEquals($value, $form->formClass());

        // Override global value without allowing override, expect same as before
        $form->form_class = $override;
        $this->assertEquals($value, $form->formClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->formClass());
    }

    public function testCanOverrideFieldClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form-control';
        $override = 'field';

        // Set global value, expect to be able to retrieve it
        Settings::set('field_class', $value);
        $this->assertEquals($value, $form->fieldClass());

        // Override global value without allowing override, expect same as before
        $form->field_class = $override;
        $this->assertEquals($value, $form->fieldClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->fieldClass());
    }

    public function testCanOverrideRowClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'row';
        $override = 'rows';

        // Set global value, expect to be able to retrieve it
        Settings::set('row_class', $value);
        $this->assertEquals($value, $form->rowClass());

        // Override global value without allowing override, expect same as before
        $form->row_class = $override;
        $this->assertEquals($value, $form->rowClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->rowClass());
    }

    public function testCanOverrideGroupClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form-group';
        $override = 'group';

        // Set global value, expect to be able to retrieve it
        Settings::set('group_class', $value);
        $this->assertEquals($value, $form->groupClass());

        // Override global value without allowing override, expect same as before
        $form->group_class = $override;
        $this->assertEquals($value, $form->groupClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->groupClass());
    }

    public function testCanOverrideLabelClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form-label';
        $override = 'label';

        // Set global value, expect to be able to retrieve it
        Settings::set('label_class', $value);
        $this->assertEquals($value, $form->labelClass());

        // Override global value without allowing override, expect same as before
        $form->label_class = $override;
        $this->assertEquals($value, $form->labelClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->labelClass());
    }

    public function testCanOverrideSubmitClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'btn btn-primary';
        $override = 'btn-submit';

        // Set global value, expect to be able to retrieve it
        Settings::set('submit_class', $value);
        $this->assertEquals($value, $form->submitClass());

        // Override global value without allowing override, expect same as before
        $form->submit_class = $override;
        $this->assertEquals($value, $form->submitClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->submitClass());
    }

    public function testCanOverrideSubmitText()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'Submit';
        $override = 'Send';

        // Set global value, expect to be able to retrieve it
        Settings::set('submit_text', $value);
        $this->assertEquals($value, $form->submitText());

        // Override global value without allowing override, expect same as before
        $form->submit_text = $override;
        $this->assertEquals($value, $form->submitText());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->submitText());
    }

    public function testCanOverrideEnableCancel()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = true;
        $override = false;

        // Set global value, expect to be able to retrieve it
        Settings::set('enable_cancel', $value);
        $this->assertEquals($value, $form->enableCancel());

        // Override global value without allowing override, expect same as before
        $form->enable_cancel = $override;
        $this->assertEquals($value, $form->enableCancel());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->enableCancel());
    }

    public function testCanOverrideCancelClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'btn btn-danger';
        $override = 'btn-cancel';

        // Set global value, expect to be able to retrieve it
        Settings::set('cancel_class', $value);
        $this->assertEquals($value, $form->cancelClass());

        // Override global value without allowing override, expect same as before
        $form->cancel_class = $override;
        $this->assertEquals($value, $form->cancelClass());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->cancelClass());
    }

    public function testCanOverrideCancelText()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'Cancel';
        $override = 'Back';

        // Set global value, expect to be able to retrieve it
        Settings::set('cancel_text', $value);
        $this->assertEquals($value, $form->cancelText());

        // Override global value without allowing override, expect same as before
        $form->cancel_text = $override;
        $this->assertEquals($value, $form->cancelText());

        // Now allow overriding, expect form value to take effect
        $form->override_styling = true;
        $this->assertEquals($override, $form->cancelText());
    }
    
}