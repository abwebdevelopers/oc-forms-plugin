<?php namespace ABWebDevelopers\Forms;

use System\Classes\PluginBase;
use ABWebDevelopers\Forms\Models\Settings;

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
                'label' => 'ABWebDevelopers Forms',
                'description' => 'Simple multipurpose form builder',
                'category'    => 'Small plugins',
                'icon' => 'icon-inbox',
                'class' => 'ABWebDevelopers\Forms\Models\Settings',
                'keywords' => 'form custom contact abweb recaptcha antispam',
                'order' => 555,
                'permixssions' => ['abwebdevelopers.forms.access_settings'],
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

}
