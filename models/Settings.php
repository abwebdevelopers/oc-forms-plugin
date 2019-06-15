<?php

namespace ABWebDevelopers\Forms\Models;

use Model;
use Backend;

class Settings extends Model {

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
     * Retrieve the link to view this submission
     * 
     * @return string
     */
    public function viewLink() {
        return Backend::url('abwebdevelopers/forms/submission/' . $this->id);
    }
}