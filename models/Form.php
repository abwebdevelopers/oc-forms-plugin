<?php namespace ABWebDevelopers\Forms\Models;

use Model;
use ABWebDevelopers\Forms\Models\Submission;
use ABWebDevelopers\Forms\Models\Field;
use ABWebDevelopers\Forms\Models\Settings;
use October\Rain\Database\Traits\Validation;

class Form extends Model
{
    use Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'abwebdevelopers_forms_forms';

    /**
     * @var array Has many relations
     */
    public $hasMany = [
        'submissions' => Submission::class,
        'fields' => Field::class,
    ];

    /**
     * @var array Belongs to relations
     */
    public $belongsTo = [
        'auto_reply_email_field' => Field::class,
        'auto_reply_name_field' => Field::class,
    ];

    /**
     * @var array Whitelist of fields allowing mass assignment
     */
    public $fillable = [
        'title',
        'code',
        'override_styling',
        'form_class',
        'field_class',
        'row_class',
        'group_class',
        'label_class',
        'submit_class',
        'submit_text',
        'enable_cancel',
        'cancel_class',
        'cancel_text',
        'override_privacy',
        'saves_data',
        'override_antispam',
        'enable_recaptcha',
        'enable_ip_restriction',
        'max_requests_per_day',
        'throttle_message',
        'override_emailing',
        'send_notifications',
        'notification_template',
        'notification_recipients',
        'auto_reply',
        'auto_reply_email_field_id',
        'auto_reply_name_field_id',
        'auto_reply_template',
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'title' => 'required|string|max:255',
        'code' => 'required|string|max:255',
        'override_styling' => 'boolean',
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
        'override_privacy' => 'boolean',
        'saves_data' => 'boolean',
        'override_antispam' => 'boolean',
        'enable_recaptcha' => 'boolean',
        'enable_ip_restriction' => 'boolean',
        'max_requests_per_day' => 'int|min:1',
        'throttle_message' => 'string|max:255',
        'override_emailing' => 'boolean',
        'send_notifications' => 'boolean',
        'notification_template' => 'string|max:255',
        'notification_recipients' => 'string|max:255',
        'auto_reply' => 'boolean',
        'auto_reply_email_field_id' => '',
        'auto_reply_name_field_id' => '',
        'auto_reply_template' => 'string|max:255',
    ];

    /**
     * Return the available options for auto reply name field dropdown
     * 
     * @return array
     */
    public function getAutoReplyNameFieldIdOptions()
    {
        $fields = [];
        
        foreach ($this->fields->sortBy(function($a) {
            $score = -1;
            $score += ($a->type === 'text') ? 1 : 0;
            $score += (stripos($a->code, 'name') !== false) ? 1 : 0;
            $score += (stripos($a->name, 'name') !== false) ? 1 : 0;
            $score += ($a->code === 'name') ? 1 : 0;

            return 0 - $score;
        }) as $field) {
            $fields[$field->id] = $field->name . ' [' . $field->code . ']';
        }

        return $fields;
    }

    /**
     * Return the available options for auto reply email field dropdown
     * 
     * @return array
     */
    public function getAutoReplyEmailFieldIdOptions()
    {
        $fields = [];
        
        foreach ($this->fields->sortBy(function($a) {
            $score = -1;
            $score += ($a->type === 'email') ? 1 : 0;
            $score += (stripos($a->code, 'email') !== false) ? 1 : 0;
            $score += (stripos($a->name, 'email') !== false) ? 1 : 0;
            $score += ($a->code === 'email') ? 1 : 0;

            return 0 - $score;
        }) as $field) {
            $fields[$field->id] = $field->name . ' [' . $field->code . ']';
        }

        return $fields;
    }
    

    // Form component helpers:

    // ====== ANTI-SPAM

    /**
     * Determine if recaptcha is enabled for form
     * 
     * @return bool
     */
    public function recaptchaEnabled() {
        if ($this->override_antispam) {
            return (bool) $this->enable_recaptcha;
        }

        return (bool) Settings::get('enable_recaptcha', false);
    }

    /**
     * Determine if IP restriction is enabled for form
     * 
     * @return bool
     */
    public function hasIpRestriction() {
        if ($this->override_antispam) {
            return (bool) $this->enable_ip_restriction;
        }

        return (bool) Settings::get('enable_ip_restriction', false);
    }

    /**
     * Retrieve max amount of requests per day (min 1)
     * 
     * @return int
     */
    public function maxRequestsPerDay() {
        if ($this->override_antispam) {
            return max((int) $this->max_requests_per_day, 1);
        }

        return max((int) Settings::get('max_requests_per_day', 5), 1);
    }

    /**
     * Retrieve the forms throttle message
     * 
     * @return string
     */
    public function throttleMessage() {
        if ($this->override_antispam) {
            return (string) $this->throttle_message;
        }

        return (string) Settings::get('throttle_message', 'Failed to send due to too many requests.');
    }

    // ====== EMAILING

    /**
     * Determine if saving data is enabled
     * 
     * @return bool
     */
    public function savesData() {
        if ($this->override_privacy) {
            return (bool) $this->saves_data;
        }

        return (bool) Settings::get('saves_data', true);
    }

    /**
     * Determine if sending notifications is enabled
     * 
     * @return bool
     */
    public function sendsNotifications() {
        if ($this->override_emailing) {
            return (bool) $this->send_notifications;
        }

        return (bool) Settings::get('send_notifications', true);
    }

    /**
     * Retrieve form's notification template (not empty)
     * 
     * @return string
     */
    public function notificationTemplate() {
        if ($this->override_emailing && !empty($this->notification_template)) {
            return (string) $this->notification_template;
        }

        return (string) Settings::get('notification_template', 'abwebdevelopers.forms::mail.notification');
    }

    /**
     * Retrieve form's notification recipients
     * 
     * @return string
     */
    public function notificationRecipients() {
        if ($this->override_emailing) {
            return (string) $this->notification_recipients;
        }

        return (string) Settings::get('notification_recipients', '');
    }
    
    /**
     * Determine if the form auto replies
     * 
     * @return bool
     */
    public function autoReply() {
        if ($this->override_emailing) {
            return (bool) $this->auto_reply;
        }

        return (bool) Settings::get('auto_reply', false);
    }
    
    /**
     * Retrieve the form's auto reply email field
     * 
     * @return Field|null
     */
    public function autoReplyEmailField() {
        return $this->auto_reply_email_field;
    }
    
    /**
     * Retrieve the form's auto reply name field
     * 
     * @return Field|null
     */
    public function autoReplyNameField() {
        return $this->auto_reply_name_field;
    }

    /**
     * Retrieve form's auto reply template
     * 
     * @return string
     */
    public function autoReplyTemplate() {
        if ($this->override_emailing && !empty($this->auto_reply_template)) {
            return (string) $this->auto_reply_template;
        }

        return (string) Settings::get('auto_reply_template', 'abwebdevelopers.forms::mail.autoreply');
    }

    // ====== STYLING
    
    /**
     * Retrieve the form's form class
     * 
     * @return string
     */
    public function formClass() {
        if ($this->override_styling) {
            return (string) $this->form_class;
        }

        return (string) Settings::get('form_class', 'form');
    }
    
    /**
     * Retrieve the form's field class
     * 
     * @param Field $field
     * @return string
     */
    public function fieldClass(Field $field = null) {
        if ($field !== null) {
            if (!empty($field->field_class)) {
                return $field->field_class;
            }
        }

        if ($this->override_styling) {
            return (string) $this->field_class;
        }

        return (string) Settings::get('field_class', 'form-control');
    }
    
    /**
     * Retrieve the form's row class
     * 
     * @param Field $field
     * @return string
     */
    public function rowClass(Field $field = null) {
        if ($field !== null) {
            if (!empty($field->row_class)) {
                return $field->row_class;
            }
        }

        if ($this->override_styling) {
            return (string) $this->row_class;
        }

        return (string) Settings::get('row_class', 'row');
    }
    
    /**
     * Retrieve the form's group class
     * 
     * @param Field $field
     * @return string
     */
    public function groupClass(Field $field = null) {
        if ($field !== null) {
            if (!empty($field->group_class)) {
                return $field->group_class;
            }
        }

        if ($this->override_styling) {
            return (string) $this->group_class;
        }

        return (string) Settings::get('group_class', 'form-group col-md-12');
    }
    
    /**
     * Retrieve the form's label class
     * 
     * @param Field $field
     * @return string
     */
    public function labelClass(Field $field = null) {
        if ($field !== null) {
            if (!empty($field->label_class)) {
                return $field->label_class;
            }
        }

        if ($this->override_styling) {
            return (string) $this->label_class;
        }

        return (string) Settings::get('label_class', 'form-label');
    }
    
    /**
     * Retrieve the form's submit button class
     * 
     * @return string
     */
    public function submitClass() {
        if ($this->override_styling) {
            return (string) $this->submit_class;
        }

        return (string) Settings::get('submit_class', 'btn btn-primary');
    }
    
    /**
     * Retrieve the form's submit button text
     * 
     * @return string
     */
    public function submitText() {
        if ($this->override_styling) {
            return (string) $this->submit_text;
        }

        return (string) Settings::get('submit_text', 'Submit');
    }
    
    /**
     * Determine if the form's cancel button is enabled
     * 
     * @return string
     */
    public function enableCancel() {
        if ($this->override_styling) {
            return (bool) $this->enable_cancel;
        }

        return (bool) Settings::get('enable_cancel', false);
    }
    
    /**
     * Retrieve the form's cancel button class
     * 
     * @return string
     */
    public function cancelClass() {
        if ($this->override_styling) {
            return (string) $this->cancel_class;
        }

        return (string) Settings::get('cancel_class', 'btn btn-danger');
    }
    
    /**
     * Retrieve the form's cancel button text
     * 
     * @return string
     */
    public function cancelText() {
        if ($this->override_styling) {
            return (string) $this->cancel_text;
        }

        return (string) Settings::get('cancel_text', 'Cancel');
    }

}
