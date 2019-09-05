<?php

namespace ABWebDevelopers\Forms\Tests\Unit;

use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use PluginTestCase;

class SettingsTest extends PluginTestCase
{
    private function getForm(bool $withData = false)
    {
        if ($withData) {
            return Form::create([
                'title' => 'Example',
                'code' => 'example',
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
                'saves_data' => false,
                'enable_recaptcha' => false,
                'enable_ip_restriction' => false,
                'max_requests_per_day' => 5,
                'throttle_message' => 'throttle',
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

    private function getField()
    {
        return new Field();
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
        $form->override_enable_recaptcha = true;
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
        $form->override_enable_ip_restriction = true;
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
        $form->override_max_requests_per_day = true;
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
        $form->override_throttle_message = true;
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
        $form->override_saves_data = true;
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
        $form->override_send_notifications = true;
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
        $form->override_notification_template = true;
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
        $form->override_notification_recipients = true;
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
        $form->override_auto_reply = true;
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
        $form->override_auto_reply_template = true;
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
        $form->override_form_class = true;
        $this->assertEquals($override, $form->formClass());
    }

    public function testCanOverrideFieldClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form-control';
        $override = 'field';
        $third = 'my-field';

        // Set global value, expect to be able to retrieve it
        Settings::set('field_class', $value);
        $this->assertEquals($value, $form->fieldClass());

        // Override global value without allowing override, expect same as before
        $form->field_class = $override;
        $this->assertEquals($value, $form->fieldClass());

        // Now allow overriding, expect form value to take effect
        $form->override_field_class = true;
        $this->assertEquals($override, $form->fieldClass());

        // Confirm that the field-specific value is not used unless overriding
        $field = $this->getField();
        $field->field_class = $third;
        $this->assertEquals($override, $form->fieldClass($field));

        // Confirm that overriding means the field value is used
        $field->override_field_class = true;
        $this->assertEquals($third, $form->fieldClass($field));
    }

    public function testCanOverrideRowClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'row';
        $override = 'rows';
        $third = 'my-row';

        // Set global value, expect to be able to retrieve it
        Settings::set('row_class', $value);
        $this->assertEquals($value, $form->rowClass());

        // Override global value without allowing override, expect same as before
        $form->row_class = $override;
        $this->assertEquals($value, $form->rowClass());

        // Now allow overriding, expect form value to take effect
        $form->override_row_class = true;
        $this->assertEquals($override, $form->rowClass());

        // Confirm that the field-specific value is not used unless overriding
        $field = $this->getField();
        $field->row_class = $third;
        $this->assertEquals($override, $form->rowClass($field));

        // Confirm that overriding means the field value is used
        $field->override_row_class = true;
        $this->assertEquals($third, $form->rowClass($field));
    }

    public function testCanOverrideGroupClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form-group';
        $override = 'group';
        $third = 'my-group';

        // Set global value, expect to be able to retrieve it
        Settings::set('group_class', $value);
        $this->assertEquals($value, $form->groupClass());

        // Override global value without allowing override, expect same as before
        $form->group_class = $override;
        $this->assertEquals($value, $form->groupClass());

        // Now allow overriding, expect form value to take effect
        $form->override_group_class = true;
        $this->assertEquals($override, $form->groupClass());

        // Confirm that the field-specific value is not used unless overriding
        $field = $this->getField();
        $field->group_class = $third;
        $this->assertEquals($override, $form->groupClass($field));

        // Confirm that overriding means the field value is used
        $field->override_group_class = true;
        $this->assertEquals($third, $form->groupClass($field));
    }

    public function testCanOverrideLabelClass()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'form-label';
        $override = 'label';
        $third = 'my-label';

        // Set global value, expect to be able to retrieve it
        Settings::set('label_class', $value);
        $this->assertEquals($value, $form->labelClass());

        // Override global value without allowing override, expect same as before
        $form->label_class = $override;
        $this->assertEquals($value, $form->labelClass());

        // Now allow overriding, expect form value to take effect
        $form->override_label_class = true;
        $this->assertEquals($override, $form->labelClass());

        // Confirm that the field-specific value is not used unless overriding
        $field = $this->getField();
        $field->label_class = $third;
        $this->assertEquals($override, $form->labelClass($field));

        // Confirm that overriding means the field value is used
        $field->override_label_class = true;
        $this->assertEquals($third, $form->labelClass($field));
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
        $form->override_submit_class = true;
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
        $form->override_submit_text = true;
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
        $form->override_enable_cancel = true;
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
        $form->override_cancel_class = true;
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
        $form->override_cancel_text = true;
        $this->assertEquals($override, $form->cancelText());
    }

    public function testCanOverrideEnableCaching()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = true;
        $override = false;

        // Set global value, expect to be able to retrieve it
        Settings::set('enable_caching', $value);
        $this->assertEquals($value, $form->enableCaching());

        // Override global value without allowing override, expect same as before
        $form->enable_caching = $override;
        $this->assertEquals($value, $form->enableCaching());

        // Now allow overriding, expect form value to take effect
        $form->override_enable_caching = true;
        $this->assertEquals($override, $form->enableCaching());
    }

    public function testCanOverrideCacheLifetime()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 54;
        $override = 122;

        // Set global value, expect to be able to retrieve it
        Settings::set('cache_lifetime', $value);
        $this->assertEquals($value, $form->cacheLifetime());

        // Override global value without allowing override, expect same as before
        $form->cache_lifetime = $override;
        $this->assertEquals($value, $form->cacheLifetime());

        // Now allow overriding, expect form value to take effect
        $form->override_cache_lifetime = true;
        $this->assertEquals($override, $form->cacheLifetime());
    }

    public function testCanOverrideOnSuccess()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'hide';
        $override = 'redirect';

        // Set global value, expect to be able to retrieve it
        Settings::set('on_success', $value);
        $this->assertEquals($value, $form->onSuccess());

        // Override global value without allowing override, expect same as before
        $form->on_success = $override;
        $this->assertEquals($value, $form->onSuccess());

        // Now allow overriding, expect form value to take effect
        $form->override_on_success = true;
        $this->assertEquals($override, $form->onSuccess());
    }

    public function testCanOverrideOnSuccessMessage()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = 'Thanks';
        $override = 'Chur';

        // Set global value, expect to be able to retrieve it
        Settings::set('on_success_message', $value);
        $this->assertEquals($value, $form->onSuccessMessage());

        // Override global value without allowing override, expect same as before
        $form->on_success_message = $override;
        $this->assertEquals($value, $form->onSuccessMessage());

        // Now allow overriding, expect form value to take effect
        $form->override_on_success_message = true;
        $this->assertEquals($override, $form->onSuccessMessage());
    }

    public function testCanOverrideOnSuccessRedirect()
    {
        // Get form
        $form = $this->getForm();

        // Set value and override value
        $value = '/thank-you';
        $override = '/chur';

        // Set global value, expect to be able to retrieve it
        Settings::set('on_success_redirect', $value);
        $this->assertEquals($value, $form->onSuccessRedirect());

        // Override global value without allowing override, expect same as before
        $form->on_success_redirect = $override;
        $this->assertEquals($value, $form->onSuccessRedirect());

        // Now allow overriding, expect form value to take effect
        $form->override_on_success_redirect = true;
        $this->assertEquals($override, $form->onSuccessRedirect());
    }
}
