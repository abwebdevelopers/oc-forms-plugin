<?php

namespace ABWebDevelopers\Forms\Tests\Unit;

use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use PluginTestCase;

class FormTest extends PluginTestCase
{

    /**
     * Instatiate a basic form for test cases
     */
    private function getForm()
    {
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

    /**
     * Verify that if a field is "assigned" to the auto reply email field, it
     * can be retrieved through the autoReplyEmailField() method
     */
    public function testCanRetrieveAutoReplyEmailField()
    {
        $form = $this->getForm();

        // Assert that no email field returns null
        $this->assertEquals(null, $form->autoReplyEmailField());

        // Create an email field for this form
        $field = Field::create([
            'type' => 'email',
            'name' => 'Email',
            'code' => 'email',
            'description' => 'Test',
            'form_id' => $form->id,
        ]);

        // Shouldn't guess the email field (maybe a setting one day)
        $this->assertEquals(null, $form->autoReplyEmailField());

        // Set the field id
        $form->auto_reply_email_field_id = $field->id;
        $form->save();
        $form->load('auto_reply_email_field');

        // Now the field should be returned
        $this->assertEquals($field->id, $form->autoReplyEmailField()->id);
    }

    /**
     * Verify that if a field is "assigned" to the auto reply name field, it
     * can be retrieved through the autoReplyEmailField() method
     */
    public function testCanRetrieveAutoReplyNameField()
    {
        $form = $this->getForm();

        // Assert that no name field returns null
        $this->assertEquals(null, $form->autoReplyNameField());

        // Create an name field for this form
        $field = Field::create([
            'type' => 'text',
            'name' => 'Name',
            'code' => 'name',
            'description' => 'Test',
            'form_id' => $form->id,
        ]);

        // Shouldn't guess the name field (maybe a setting one day)
        $this->assertEquals(null, $form->autoReplyNameField());

        // Set the field id
        $form->auto_reply_name_field_id = $field->id;
        $form->save();
        $form->load('auto_reply_name_field');

        // Now the field should be returned
        $this->assertEquals($field->id, $form->autoReplyNameField()->id);
    }
}
