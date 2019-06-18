<?php namespace ABWebDevelopers\Forms;

use System\Classes\PluginBase;
use ABWebDevelopers\Forms\Models\Settings;
use Event;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
        return [
            'ABWebDevelopers\Forms\Components\CustomForm' => 'customForm'
        ];
    }

    public function registerSettings() {

        return [
            'settings' => [
                'label' => 'Custom Forms',
                'description' => 'Simple multipurpose form builder',
                'category'    => 'Small plugins',
                'icon' => 'icon-inbox',
                'class' => 'ABWebDevelopers\Forms\Models\Settings',
                'keywords' => 'form custom contact abweb recaptcha antispam',
                'order' => 555,
                'permissions' => ['abwebdevelopers.forms.access_settings'],
            ]
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'abwebdevelopers.forms::mail.autoreply' => 'abwebdevelopers.forms::lang.mail.templates.autoreply',
            'abwebdevelopers.forms::mail.notification' => 'abwebdevelopers.forms::lang.mail.templates.notification',
        ];
    }

    public function boot() {
        Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
            if ($controller instanceof \ABWebDevelopers\Forms\Controllers\Form) {
                // Check this is the settings page for this plugin:
                if ($action === 'update' || $action === 'create') {
                    // Add CSS (minor patch)
                    $controller->addCss('/plugins/abwebdevelopers/forms/assets/settings-patch.css');
                    $controller->addJs('/plugins/abwebdevelopers/forms/assets/settings-patch.js');
                }
            }
        });
    }

}
