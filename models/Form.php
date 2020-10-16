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
        'notif_replyto_email_field' => Field::class,
        'notif_replyto_name_field' => Field::class,
    ];

    /**
     * @var array Whitelist of fields allowing mass assignment
     */
    public $fillable = [
        'title',
        'code',
        'enable_caching',
        'cache_lifetime',
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
        'saves_data',
        'enable_recaptcha',
        'enable_ip_restriction',
        'max_requests_per_day',
        'throttle_message',
        'send_notifications',
        'notification_template',
        'notification_recipients',
        'auto_reply',
        'auto_reply_email_field_id',
        'auto_reply_name_field_id',
        'auto_reply_template',
        'notif_replyto',
        'notif_replyto_email_field_id',
        'notif_replyto_name_field_id',
        'on_success',
        'on_success_message',
        'on_success_redirect',
    ];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'title' => 'required|string|max:255',
        'code' => 'required|string|max:255',
        'form_class' => 'nullable|string|max:255',
        'field_class' => 'nullable|string|max:255',
        'row_class' => 'nullable|string|max:255',
        'group_class' => 'nullable|string|max:255',
        'label_class' => 'nullable|string|max:255',
        'submit_class' => 'nullable|string|max:255',
        'submit_text' => 'nullable|string|max:255',
        'enable_cancel' => 'nullable|boolean',
        'cancel_class' => 'nullable|string|max:255',
        'cancel_text' => 'nullable|string|max:255',
        'saves_data' => 'nullable|boolean',
        'enable_recaptcha' => 'nullable|boolean',
        'enable_ip_restriction' => 'nullable|boolean',
        'max_requests_per_day' => 'nullable|int|min:1',
        'throttle_message' => 'nullable|string|max:255',
        'send_notifications' => 'nullable|boolean',
        'notification_template' => 'nullable|string|max:255',
        'notification_recipients' => 'nullable|string|max:255',
        'auto_reply' => 'nullable|boolean',
        'auto_reply_email_field_id' => 'nullable',
        'auto_reply_name_field_id' => 'nullable',
        'auto_reply_template' => 'nullable|string|max:255',
        'notif_replyto' => 'nullable|boolean',
        'notif_replyto_email_field_id' => 'nullable',
        'notif_replyto_name_field_id' => 'nullable',
        'enable_caching' => 'nullable|boolean',
        'cache_lifetime' => 'nullable|integer|min:0',
        'on_success' => 'nullable|in:hide,clear,redirect',
        'on_success_message' => 'nullable|string|max:255',
        'on_success_redirect' => 'nullable|string|max:255'
    ];

    /**
     * @var array List of fields which have an occumpanied "Override" checkbox configuration
     */
    public $overrides = [
        'enable_caching',
        'cache_lifetime',
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
        'saves_data',
        'enable_recaptcha',
        'enable_ip_restriction',
        'max_requests_per_day',
        'throttle_message',
        'send_notifications',
        'notification_template',
        'notification_recipients',
        'auto_reply',
        'auto_reply_email_field_id',
        'auto_reply_name_field_id',
        'auto_reply_template',
        'on_success',
        'on_success_message',
        'on_success_redirect',
        'notif_replyto',
        'notif_replyto_email_field_id',
        'notif_replyto_name_field_id',
    ];

    /**
     * After fetching the Field event
     * Create override_{field} Fields which represent the fields' states on whether or not
     * to inherit the setting value - used in the forms.
     *
     * @return void
     */
    public function afterFetch()
    {
        if (!empty($this->overrides)) {
            // Create virtual fields for auto selecting override checkboxes in backend form
            foreach ($this->overrides as $field) {
                $override = 'override_' . $field;
                $hasValue = ($this->{$field} !== null);
                $this->{$override} = $hasValue;
                if (!$hasValue) {
                    $this->{$field} = Settings::get($field, null);
                }
            }
        }
    }

    /**
     * Before the Field's Save event.
     * Remove override_{field} Fields
     *
     * @return void
     */
    public function beforeSave()
    {
        if (!empty($this->overrides)) {
            // Convert inherited values to null
            foreach ($this->overrides as $field) {
                $override = 'override_' . $field;
                if ($this->{$override} !== null) {
                    if (!$this->{$override}) {
                        $this->{$field} = null;
                    }
                }
                unset($this->{$override});
            }
        }
    }

    /**
     * Return the available options for auto reply name field dropdown
     *
     * @return array
     */
    public function getAutoReplyNameFieldIdOptions()
    {
        $fields = [];

        foreach ($this->fields->sortBy(function ($a) {
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

        foreach ($this->fields->sortBy(function ($a) {
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

    /**
     * Return the available options for confirmation replyto name field dropdown
     *
     * @return array
     */
    public function getNotifReplytoNameFieldIdOptions()
    {
        $fields = [];

        foreach ($this->fields->sortBy(function ($a) {
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
    public function getNotifReplytoEmailFieldIdOptions()
    {
        $fields = [];

        foreach ($this->fields->sortBy(function ($a) {
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
    public function recaptchaEnabled()
    {
        if ($this->override_enable_recaptcha) {
            return (bool) $this->enable_recaptcha;
        }

        return (bool) Settings::get('enable_recaptcha', false);
    }

    /**
     * Determine if IP restriction is enabled for form
     *
     * @return bool
     */
    public function hasIpRestriction()
    {
        if ($this->override_enable_ip_restriction) {
            return (bool) $this->enable_ip_restriction;
        }

        return (bool) Settings::get('enable_ip_restriction', false);
    }

    /**
     * Retrieve max amount of requests per day (min 1)
     *
     * @return int
     */
    public function maxRequestsPerDay()
    {
        if ($this->override_max_requests_per_day) {
            return max((int) $this->max_requests_per_day, 1);
        }

        return max((int) Settings::get('max_requests_per_day', 5), 1);
    }

    /**
     * Retrieve the forms throttle message
     *
     * @return string
     */
    public function throttleMessage()
    {
        if ($this->override_throttle_message) {
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
    public function savesData()
    {
        if ($this->override_saves_data) {
            return (bool) $this->saves_data;
        }

        return (bool) Settings::get('saves_data', true);
    }

    /**
     * Determine if sending notifications is enabled
     *
     * @return bool
     */
    public function sendsNotifications()
    {
        if ($this->override_send_notifications) {
            return (bool) $this->send_notifications;
        }

        return (bool) Settings::get('send_notifications', true);
    }

    /**
     * Retrieve form's notification template (not empty)
     *
     * @return string
     */
    public function notificationTemplate()
    {
        if ($this->override_notification_template) {
            return (string) $this->notification_template;
        }

        return (string) Settings::get('notification_template', 'abwebdevelopers.forms::mail.notification');
    }

    /**
     * Retrieve form's notification recipients
     *
     * @return string
     */
    public function notificationRecipients()
    {
        if ($this->override_notification_recipients) {
            return (string) $this->notification_recipients;
        }

        return (string) Settings::get('notification_recipients', '');
    }

    /**
     * Determine if the form auto replies
     *
     * @return bool
     */
    public function autoReply()
    {
        if ($this->override_auto_reply) {
            return (bool) $this->auto_reply;
        }

        return (bool) Settings::get('auto_reply', false);
    }

    /**
     * Retrieve the form's auto reply email field
     *
     * @return Field|null
     */
    public function autoReplyEmailField()
    {
        return $this->auto_reply_email_field;
    }

    /**
     * Retrieve the form's auto reply name field
     *
     * @return Field|null
     */
    public function autoReplyNameField()
    {
        return $this->auto_reply_name_field;
    }
    

    /**
     * Retrieve form's auto reply template
     *
     * @return string
     */
    public function autoReplyTemplate()
    {
        if ($this->override_auto_reply_template) {
            return (string) $this->auto_reply_template;
        }

        return (string) Settings::get('auto_reply_template', 'abwebdevelopers.forms::mail.autoreply');
    }

    /**
     * Determine if the form should send the reply-to header for notifications
     *
     * @return bool
     */
    public function notifReplyto()
    {
        return (bool) $this->notif_replyto;
    }

    /**
     * Retrieve the form's reply-to email field
     *
     * @return Field|null
     */
    public function notifReplytoEmailField()
    {
        return $this->notif_replyto_email_field;
    }

    /**
     * Retrieve the form's reply-to name field
     *
     * @return Field|null
     */
    public function notifReplytoNameField()
    {
        return $this->notif_replyto_name_field;
    }

    // ====== STYLING

    /**
     * Retrieve the form's form class
     *
     * @return string
     */
    public function formClass()
    {
        if ($this->override_form_class) {
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
    public function fieldClass(Field $field = null)
    {
        if ($field !== null) {
            if ($field->override_field_class) {
                return $field->field_class;
            }
        }

        if ($this->override_field_class) {
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
    public function rowClass(Field $field = null)
    {
        if ($field !== null) {
            if ($field->override_row_class) {
                return $field->row_class;
            }
        }

        if ($this->override_row_class) {
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
    public function groupClass(Field $field = null)
    {
        if ($field !== null) {
            if ($field->override_group_class) {
                return $field->group_class;
            }
        }

        if ($this->override_group_class) {
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
    public function labelClass(Field $field = null)
    {
        if ($field !== null) {
            if ($field->override_label_class) {
                return $field->label_class;
            }
        }

        if ($this->override_label_class) {
            return (string) $this->label_class;
        }

        return (string) Settings::get('label_class', 'form-label');
    }

    /**
     * Retrieve the form's submit button class
     *
     * @return string
     */
    public function submitClass()
    {
        if ($this->override_submit_class) {
            return (string) $this->submit_class;
        }

        return (string) Settings::get('submit_class', 'btn btn-primary');
    }

    /**
     * Retrieve the form's submit button text
     *
     * @return string
     */
    public function submitText()
    {
        if ($this->override_submit_text) {
            return (string) $this->submit_text;
        }

        return (string) Settings::get('submit_text', 'Submit');
    }

    /**
     * Determine if the form's cancel button is enabled
     *
     * @return string
     */
    public function enableCancel()
    {
        if ($this->override_enable_cancel) {
            return (bool) $this->enable_cancel;
        }

        return (bool) Settings::get('enable_cancel', false);
    }

    /**
     * Retrieve the form's cancel button class
     *
     * @return string
     */
    public function cancelClass()
    {
        if ($this->override_cancel_class) {
            return (string) $this->cancel_class;
        }

        return (string) Settings::get('cancel_class', 'btn btn-danger');
    }

    /**
     * Retrieve the form's cancel button text
     *
     * @return string
     */
    public function cancelText()
    {
        if ($this->override_cancel_text) {
            return (string) $this->cancel_text;
        }

        return (string) Settings::get('cancel_text', 'Cancel');
    }

    /**
     * Determine if caching is enabled
     *
     * @return bool
     */
    public function enableCaching()
    {
        if ($this->override_enable_caching) {
            return (bool) $this->enable_caching;
        }

        return (bool) Settings::get('enable_caching', false);
    }

    /**
     * Retrieve the amount of minutes to cache the form for
     *
     * @return int
     */
    public function cacheLifetime()
    {
        if ($this->override_cache_lifetime) {
            return (int) $this->cache_lifetime;
        }

        return (int) Settings::get('cache_lifetime', 60);
    }

    /**
     * Determine what to do on success
     *
     * @return string
     */
    public function onSuccess()
    {
        if ($this->override_on_success) {
            return (string) $this->on_success;
        }

        return (string) Settings::get('on_success', 'hide');
    }

    /**
     * Retrieve the message to display in a flash on success
     *
     * @return string
     */
    public function onSuccessMessage()
    {
        if ($this->override_on_success_message) {
            return (string) $this->on_success_message;
        }

        return (string) Settings::get('on_success_message', 'Successfully sent');
    }

    /**
     * Retrieve the URL to redirect to on success
     *
     * @return string
     */
    public function onSuccessRedirect()
    {
        if ($this->override_on_success_redirect) {
            return (string) $this->on_success_redirect;
        }

        return (string) Settings::get('on_success_redirect', '/');
    }

    /**
     * Retrieve the class for label success
     *
     * @return string
     */
    public function labelSuccessClass()
    {
        return (string) Settings::get('label_success_class', '');
    }

    /**
     * Retrieve the class for label error
     *
     * @return string
     */
    public function labelErrorClass()
    {
        return (string) Settings::get('label_error_class', '');
    }

    /**
     * Retrieve the class for field success
     *
     * @return string
     */
    public function fieldSuccessClass()
    {
        return (string) Settings::get('field_success_class', '');
    }

    /**
     * Retrieve the class for field error
     *
     * @return string
     */
    public function fieldErrorClass()
    {
        return (string) Settings::get('field_error_class', '');
    }

    /**
     * Retrieve the class for form success
     *
     * @return voistringd
     */
    public function formSuccessClass()
    {
        return (string) Settings::get('form_success_class', '');
    }

    /**
     * Retrieve the class for form error
     *
     * @return string
     */
    public function formErrorClass()
    {
        return (string) Settings::get('form_error_class', '');
    }

    /**
     * Determine if at least one of the form's fields is a "file" field
     *
     * @return boolean
     */
    public function hasFileField()
    {
        foreach ($this->fields as $field) {
            if ($field->type === 'file') {
                return true;
            }
        }

        return false;
    }
}
