<?php namespace ABWebDevelopers\Forms;

use System\Classes\PluginBase;
use ABWebDevelopers\Forms\Models\Settings;
use Event;

class Plugin extends PluginBase
{

    /**
     * @var string Event namespace
     */
    public const EVENTS_PREFIX = 'abweb.forms.';

    /**
     * Register Plugin Components
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'ABWebDevelopers\Forms\Components\CustomForm' => 'customForm'
        ];
    }

    /**
     * Register Plugin Settings
     *
     * @return array
     */
    public function registerSettings()
    {
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

    /**
     * Register Plugin Mail Templates
     *
     * @return array
     */
    public function registerMailTemplates()
    {
        return [
            'abwebdevelopers.forms::mail.autoreply' => 'abwebdevelopers.forms::lang.mail.templates.autoreply',
            'abwebdevelopers.forms::mail.notification' => 'abwebdevelopers.forms::lang.mail.templates.notification',
        ];
    }

    /**
     * Inject patch JS and CSS when creating/updating forms
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/views', 'abwebdevelopers.forms');

        Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
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
