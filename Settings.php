<?php

namespace ABWebDevelopers\Forms\Models;

use Model;
use Backend;
use October\Rain\Database\Traits\Validation;

class Settings extends Model
{
    use Validation;

    /**
     * @var array Implement settings model
     */
    public $implement = [
        'System.Behaviors.SettingsModel',
    ];

    /**
     * @var array Required permissions to access settings
     */
    public $requiredPermissions = [
        'abwebdevelopers.forms.access_settings'
    ];

    /**
     * @var string Define the unique settings code
     */
    public $settingsCode = 'abwebdevelopers_forms';

    /**
     * @var string Define the fields for these settings
     */
    public $settingsFields = 'fields.yaml';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'form_class' => 'string|max:255',
        'field_class' => 'string|max:255',
        'row_class' => 'string|max:255',
        'group_class' => 'string|max:255',
        'label_class' => 'string|max:255',
        'submit_class' => 'string|max:255',
        'submit_text' => 'string|max:255',
        'enable_cancel' => 'boolean',
        'cancel_class' => 'string|max:255',
        'cancel_text' => 'string|max:255',
        'saves_data' => 'boolean',
        'enable_recaptcha' => 'boolean',
        'recaptcha_public_key' => 'string|max:255',
        'recaptcha_secret_key' => 'string|max:255',
        'enable_ip_restriction' => 'boolean',
        'max_requests_per_day' => 'int|min:1',
        'throttle_message' => 'string|max:255',
        'send_notifications' => 'boolean',
        'notification_template' => 'string|max:255',
        'notification_recipients' => 'string|max:255',
        'auto_reply' => 'boolean',
        'auto_reply_email_field_id' => 'nullable',
        'auto_reply_name_field_id' => 'nullable',
        'auto_reply_template' => 'string|max:255',
        'enable_caching' => 'boolean',
        'cache_lifetime' => 'integer|min:0',
    ];
}
