<?php

namespace ABWebDevelopers\Forms\Tests\Unit;

use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use ABWebDevelopers\Forms\Components\CustomForm;
use PluginTestCase;
use YeTii\HtmlElement\Elements\HtmlForm;
use YeTii\HtmlElement\Elements\HtmlDiv;
use YeTii\HtmlElement\Elements\HtmlSpan;
use YeTii\HtmlElement\Elements\HtmlLabel;
use YeTii\HtmlElement\Elements\HtmlInput;
use YeTii\HtmlElement\Elements\HtmlSelect;
use YeTii\HtmlElement\Elements\HtmlTextarea;

class HtmlGeneratorTest extends PluginTestCase
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

        $component->loadForm();

        return $component;
    }

    /** @test */
    public function itGeneratesTheFormElement()
    {
        $component = $this->getComponent();

        $htmlGenerator = $component->getHtmlGenerator();

        $this->assertTrue($htmlGenerator instanceof HtmlForm);

        $this->assertEquals([
            'id' => 'form_example',
            'class' => 'custom-form',
        ], $htmlGenerator->getAttributes(['id', 'class']));
    }
}
