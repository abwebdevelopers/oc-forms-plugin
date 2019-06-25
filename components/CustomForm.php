<?php

namespace ABWebDevelopers\Forms\Components;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Submission;
use Cms\Classes\ComponentBase;
use Backend;
use Cache;
use Event;
use Input;
use Lang;
use Mail;
use Request;
use Response;
use Validator;

class CustomForm extends ComponentBase
{

    /**
     * @var string Event namespace
     */
    public const EVENTS_PREFIX = 'abweb.forms.';

    /**
     * @var Form The form to render / use
     */
    protected $form;

    /**
     * @var Submission The current form submission entity
     */
    private $submission;

    /**
     * @var array All variables that will be available in the email templates
     */
    protected $templateVars = [];

    /**
     * Component details definition
     *
     * @return array
     */
    public function componentDetails()
    {
        return [
            'name' => 'Custom Form',
            'description' => 'Displays a custom Form'
        ];
    }

    /**
     * Property definition
     *
     * @return array
     */
    public function defineProperties()
    {
        return [
            'formCode' => [
                'title'             => 'abwebdevelopers.forms::lang.customForm.formCode.title',
                'description'       => 'abwebdevelopers.forms::lang.customForm.formCode.description',
                'default'           => '',
                'type'              => 'string',
                'required'          => true,
            ],
        ];
    }

    /**
     * Get this component's form
     *
     * @return Form
     */
    public function form()
    {
        return $this->form;
    }

    /**
     * Autoload the form using the form code with fields
     *
     * @return Form
     */
    public function loadForm() {
        return $this->form = Form::with([
            'fields' => function($query) {
                return $query->orderBy('sort_order', 'asc');
            }
        ])->where('code', $this->property('formCode'))->firstOrFail();
    }

    /**
     * On Run Handler - The GET action
     *
     * @return void
     */
    public function onRun()
    {
        // Fire beforeRun event
        Event::fire(self::EVENTS_PREFIX . 'beforeRun', [$this]);

        // Autoload the form
        $this->loadForm();

        // Load required CSS
        $this->addCss('/plugins/abwebdevelopers/forms/assets/custom-form.css');

        // Fire afterRun event
        Event::fire(self::EVENTS_PREFIX . 'afterRun', [$this]);
    }

    /**
     * On Form Submit Handler - The (ajax) POST action
     *
     * @return void
     */
    public function onFormSubmit() {
        // Fire beforeFormSubmit event
        Event::fire(self::EVENTS_PREFIX . 'beforeFormSubmit', [$this]);

        // Autoload the form
        $this->loadForm();

        $fields = [];
        $rules = [];
        $messages = [];

        // Get a list of all fields, any validation rules and messages
        foreach ($this->form->fields as $field) {
            $fields[] = $field->code;

            $fieldRules = $field->compiled_rules;
            if (!empty($fieldRules)) {
                $rules[$field->code] = $fieldRules;

                if (!empty($field->validation_messages)) {
                    $messages[$field->code] = $field->validation_messages;
                } else {
                    $messages[$field->code] = $field->name . ' is invalid';
                }
            }

            if (in_array($field->type, ['checkbox', 'radio', 'select'])) {
                $rules[$field->code . '.*'] = $field->option_rules;
            }
        }

        // If Google recaptcha enabled, add validation for it
        if ($this->form->recaptchaEnabled()) {
            $fields[] = 'g-recaptcha-response';
            $rules['g-recaptcha-response'] = 'required|string';
            $messages['g-recaptcha-response.*'] = Lang::get('abwebdevelopers.forms::lang.customForm.validation.recaptchaFailed');
        }

        // Get only what we asked for
        $data = Input::only($fields);

        // Ensure checkboxes, radios and selects are dealt with as arrays (even if only accepting one value)
        foreach ($data as $code => $value) {
            foreach ($this->form->fields as $field) {
                if ($field->code === $code) {
                    if (in_array($field->type, ['checkbox', 'radio', 'select'])) {
                        $data[$code] = (array) $value;
                    }
                }
            }
        }

        // If no data was supplied, reject the request
        if (empty($data)) {
            return Response::json([
                'success' => false,
                'error' =>  Lang::get('abwebdevelopers.forms::lang.customForm.validation.noData')
            ], 400);
        }

        // Validate the form
        $validator = Validator::make($data, $rules, $messages);

        // Fire beforeValidateForm event
        Event::fire(self::EVENTS_PREFIX . 'beforeValidateForm', [$this, &$data, &$rules, &$messages, &$validator]);

        if (!$validator->passes()) {
            // Fire onValidateFormFail event
            Event::fire(self::EVENTS_PREFIX . 'onValidateFormFail', [$this, $data, $rules, $messages, $validator]);

            // // Store the submission
            // if ($this->form->savesData()) {
            //     // TODO: create a setting to save invalid submissions
            //     $this->saveSubmission($data);
            // }

            // Compile the errors into one string
            $errors = [];
            foreach ($validator->messages()->toArray() as $fieldName => $fieldErrors) {
                $errors[$fieldName] = implode(" \n", $fieldErrors);
            }

            // Send (multiple, field-based) error messages
            return Response::json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        // Fire afterValidateForm event
        Event::fire(self::EVENTS_PREFIX . 'afterValidateForm', [$this, $data, $rules, $messages]);

        // Set the email template vars
        $this->setTemplateVars($data);

        // Validation past, so let check if the recaptcha response is valid
        if ($this->form->recaptchaEnabled()) {
            if (!$this->passesRecaptcha($data['g-recaptcha-response'])) {
                // Fire onRecaptchaFail event
                Event::fire(self::EVENTS_PREFIX . 'onRecaptchaFail', [$this, $data['g-recaptcha-response']]);

                // TODO: Fails
                return Response::json([
                    'success' => false,
                    'errors' => [
                        'g-recaptcha-response' => 'Invalid ReCAPTCHA response'
                    ]
                ], 400);
            }

            // Fire onRecaptchaSuccess event
            Event::fire(self::EVENTS_PREFIX . 'onRecaptchaSuccess', [$this, $data, $rules, $messages]);
        }

        // If we have are storing IPs for submissions (requirement for throttle) and..
        if ($this->form->savesData() && Settings::get('store_ips', true)) {
            // if the form is throttling requests then...
            if ($this->form->hasIpRestriction() && $max = $this->form->maxRequestsPerDay()) {
                // Check if this IP has submitted $max requests today for this form
                $attempts = Submission::throttleCheck($this->form->id)->count();

                // If they have attempted too many in the last 24h then abort
                if ($attempts >= $max) {
                    $message = $this->form->throttleMessage();
                    return Response::json([
                        'success' => false,
                        'error' => $message,
                    ], 429);
                }
            }
        }

        // Store the submission
        if ($this->form->savesData()) {
            $this->saveSubmission($data);
        }

        // Send notification
        if ($this->form->sendsNotifications()) {
            $response = $this->sendNotification($data);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }
        }

        // Send auto reply
        if ($this->form->autoReply()) {
            $response = $this->sendAutoReply($data);

            if ($response instanceof \Illuminate\Http\JsonResponse) {
                return $response;
            }
        }

        $response = Response::json([
            'success' => true,
            'action' => $this->form->onSuccess(),
            'url' => $this->form->onSuccessRedirect(),
            'message' => $this->form->onSuccessMessage()
        ]);

        // Fire afterFormSubmit event
        Event::fire(self::EVENTS_PREFIX . 'afterFormSubmit', [$this, $data, $response]);

        // Whoop
        return $response;
    }

    /**
     * Verify if a given response is a valid recaptcha response by cross referencing it
     * with Google's recaptcha verify API
     *
     * @param string $response
     * @return bool
     */
    private function passesRecaptcha(string $response) {
        $secret = Settings::get('recaptcha_secret_key');

        if (empty($secret)) {
            // Nah boi
            return false;
        }

        $context  = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret' => $secret,
                    'response' => $response,
                    'remoteip' => Request::ip()
                ])
            ]
        ]);

        $response = file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        $result = json_decode($response);

        return (bool) $result->success;
    }

    /**
     * Caching facility to retrieve pre-rendered partial of the form if configured to save
     *
     * @return string
     */
    public function getRenderedPartial()
    {
        // Is caching enabled?
        $cachingEnabled = $this->form->enableCaching();

        // Fire beforeRenderPartial event
        Event::fire(self::EVENTS_PREFIX . 'beforeRenderPartial', [$this, $cachingEnabled]);

        // If caching is enabled, return the cached version...
        if ($cachingEnabled) {
            // ..and/or initialise the cache, for the configured lifetime
            $form = Cache::remember('abwebdevelopers_form_' . $this->form->code, $this->form->cacheLifetime(), function() {
                return $this->renderForm();
            });
        } else {
            // Just render the form now
            $form = $this->renderForm();
        }

        // Fire afterRenderPartial event
        Event::fire(self::EVENTS_PREFIX . 'afterRenderPartial', [$this, &$form]);

        // Return the form HTML
        return $form;
    }

    /**
     * Render the form! Not sure if I still need this...
     *
     * @return string;
     */
    public function renderForm()
    {
        return $this->renderPartial('@form');
    }

    /**
     * Return the setting for the ReCAPTCHA Public Key
     */
    public function recpatchaPublicKey() {
        return Settings::get('recaptcha_public_key');
    }

    /**
     * Send the notificaiton to the administrator(s) which is either
     * defined at a global level, or per form.
     *
     * @param array $data
     * @return Response|bool
     */
    private function sendNotification(array $data) {
        // Get notification recipients from form, or global settings if not set
        $to = $this->form->notificationRecipients();

        // Fire beforeSendNotification event
        Event::fire(self::EVENTS_PREFIX . 'beforeSendNotification', [$this, &$data, &$to]);

        // If there are recipients
        if (!empty($to)) {
            $to = array_filter(explode(',', $to));

            // Check if there are no recipients
            if (empty($to)) {
                // Fire afterSendNotification event
                Event::fire(self::EVENTS_PREFIX . 'afterSendNotification', [$this, $data, false]);

                // Don't send notification, silent abort
                return false;
            }

            // Compile ruleset with matching $key(s) for validation
            $rules = [];
            foreach ($to as $key => $value) {
                $rules[$key] = 'required|email';
            }

            // Get the validator instance
            $validator = Validator::make($to, $rules);

            // Fire beforeNotificationValidation event
            Event::fire(self::EVENTS_PREFIX . 'beforeNotificationValidation', [$this, $data, &$to, &$rules, &$validator]);

            // Validate the list of email addresses
            if (!$validator->passes()) {
                // Fire onNotificationValidationFail event
                Event::fire(self::EVENTS_PREFIX . 'onNotificationValidationFail', [$this, $data, $to, $rules, $validator]);

                // Return (a single) error
                return Response::json([
                    'success' => false,
                    'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.invalidNotificationRecipients')
                ], 501);
            }

            // Fire onNotificationValidationSuccess event
            Event::fire(self::EVENTS_PREFIX . 'onNotificationValidationSuccess', [$this, $data, $to, $rules]);

            // Get the template to use in the email
            $template = $this->form->notificationTemplate();

            // Only queue if configured to queue emails
            $method = Settings::get('queue_emails', true) ? 'queue' : 'send';

            // Send the notification
            Mail::{$method}($template, $this->templateVars, function($message) use ($to) {
                if (count($to) === 1) {
                    $message->to(current($to), 'Admin');
                } else {
                    $main = array_shift($to);
                    $message->to($main, 'Admin');
                    foreach ($to as $recipient) {
                        $message->cc($recipient, 'Admin');
                    }
                }

                Event::fire(self::EVENTS_PREFIX . 'onSendNotification', [$this, &$message, $to]);
            });

            // Fire afterSendNotification event
            Event::fire(self::EVENTS_PREFIX . 'afterSendNotification', [$this, $data, true]);

            return true;
        }

        // Fire afterSendNotification event
        Event::fire(self::EVENTS_PREFIX . 'afterSendNotification', [$this, $data, false]);

        return false;
    }

    /**
     * Send the auto reply to the user/customer who submitted the form
     * using the email (and name) fields in the form as the recipients
     * name/email. Requires configuration, per form.
     *
     * @param array $data
     * @return Response|bool
     */
    private function sendAutoReply($data) {
        // Resolve the user's email address using the configured field
        $to_email = $this->form->autoReplyEmailField();
        $to_email = (!empty($to_email) && !empty($data[$to_email->code])) ? $data[$to_email->code] : null;

        // Resolve the user's name using the configured field
        $to_name = $this->form->autoReplyNameField();
        $to_name = (!empty($to_name) && !empty($data[$to_name->code])) ? $data[$to_name->code] : null;

        // Fire beforeSendAutoReply event
        Event::fire(self::EVENTS_PREFIX . 'beforeSendAutoReply', [$this, &$data, &$to_email, &$to_name]);

        if (empty($to_email)) {
            // Fire onAutoReplyValidationFail event
            Event::fire(self::EVENTS_PREFIX . 'onAutoReplyValidationFail', [$this, $data, $to_email, $to_name, 'email']);

            // Return (a single) error if the email field could not be resolved
            return Response::json([
                'success' => false,
                'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.noAutoReplyEmailField'),
            ], 501);
        }

        if (empty($to_name)) {
            // Fire onAutoReplyValidationFail event
            Event::fire(self::EVENTS_PREFIX . 'onAutoReplyValidationFail', [$this, $data, $to_email, $to_name, 'name']);

            // Return (a single) error if the name field could not be resolved
            return Response::json([
                'success' => false,
                'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.noAutoReplyNameField'),
            ], 501);
        }

        // Get the template to use in the email
        $template = $this->form->autoReplyTemplate();

        // Only queue if configured to queue emails
        $method = Settings::get('queue_emails', true) ? 'queue' : 'send';

        // Send the auto reply
        Mail::{$method}($template, $this->templateVars, function($message) use ($to_email, $to_name) {
            $message->to($to_email, $to_name);

            Event::fire(self::EVENTS_PREFIX . 'onSendAutoReply', [$this, &$message, $to_email, $to_name]);
        });

        // Fire afterSendAutoReply event
        Event::fire(self::EVENTS_PREFIX . 'afterSendAutoReply', [$this, $data, true]);

        return true;
    }

    /**
     * Store the submission in the database with the form field values
     *
     * @param array $data
     * @return Submission
     */
    private function saveSubmission($data) {
        // Compile the data to store with the submission
        $submissionData = [
            'url' => '/' . trim(Request::path(), '/'),
            'data' => $data,
            'form_id' => $this->form->id
        ];

        // If store IPs, then store the IP address of the user
        if (Settings::get('store_ips', true)) {
            $submissionData['ip'] = Request::ip();
        }

        // Fire beforeSaveSubmission event
        Event::fire(self::EVENTS_PREFIX . 'beforeSaveSubmission', [$this, &$submissionData]);

        // Save it, in case any errors occur when sending emails
        $this->submission = Submission::create($submissionData);

        // Fire afterSaveSubmission event
        Event::fire(self::EVENTS_PREFIX . 'afterSaveSubmission', [$this, $this->submission]);

        // Return the newly created submission
        return $this->submission;
    }

    /**
     * Set template vars for email templates, including the form, the fields with
     * their respective values, and a moreInfo link (to submission, if saved)
     *
     * @param array $data
     * @return array
     */
    public function setTemplateVars(array $data) {
        // Build an array of fields for the template
        $fields = [];
        foreach ($data as $key => $value) {
            $field = null;
            foreach ($this->form->fields as $fld) {
                if ($fld->code === $key) {
                    $field = $fld;
                    break;
                }
            }

            // Field not found (likely Google Recaptcha?)
            if ($field === null) {
                continue;
            }

            if (is_array($value)) {
                if (count($value) === 0) {
                    $value = 'N/A';
                } else if (count($value) === 1) {
                    $value = current($value);
                } else {
                    $value = implode(", ", $value);
                }
            }

            // Add this field
            $fields[$key] = [
                'name' => $field->name,
                'type' => $field->type,
                'description' => $field->description,
                'value' => $value,
            ];
        }

        $vars = [
            'fields' => $fields,
            'form' => $this->form->toArray(),
            'moreInfoLink' => ($this->submission) ? $this->submission->viewLink() : null
        ];

        // Fire beforeSetTemplateVars event
        Event::fire(self::EVENTS_PREFIX . 'beforeSetTemplateVars', [$this, &$vars]);

        // Set the variables
        return $this->templateVars = $vars;
    }

    /**
     * Get a Settings value by $key
     */
    public function setting(string $key) {
        return Settings::get($key);
    }

    /**
     * Retrieve the submission from the component
     */
    public function getSubmission() {
        return $this->submission;
    }

}
