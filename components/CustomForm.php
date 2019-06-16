<?php

namespace ABWebDevelopers\Forms\Components;

use Cms\Classes\ComponentBase;
use ABWebDevelopers\Forms\Models\Submission;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Settings;
use Cache;
use Input;
use Validator;
use Request;
use Mail;
use Backend;
use Response;
use Lang;

class CustomForm extends ComponentBase
{

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
        // Autoload the form
        $this->loadForm();
    }

    /**
     * On Form Submit Handler - The (ajax) POST action
     * 
     * @return void
     */
    public function onFormSubmit() {
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
        }

        // If Google recaptcha enabled, add validation for it
        if ($this->form->recaptchaEnabled()) {
            $fields[] = 'g-recaptcha-response';
            $rules['g-recaptcha-response'] = 'required|string';
            $messages['g-recaptcha-response.*'] = Lang::get('abwebdevelopers.forms::lang.customForm.validation.recaptchaFailed');
        }

        // Get only what we asked for
        $data = Input::only($fields);

        if (empty($data)) {
            return Response::json([
                'success' => false,
                'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.noData')
            ]);
        }

        // Validate the form
        $validator = Validator::make($data, $rules, $messages);
        if (!$validator->passes()) {
            // Store the submission
            if ($this->form->savesData()) {
                // TODO: create a setting to save invalid submissions
                $this->saveSubmission($data);
            }

            $errors = [];
            foreach ($validator->messages()->toArray() as $fieldName => $fieldErrors) {
                $errors[$fieldName] = implode("\n", $fieldErrors);
            }

            // Send (multiple, field-based) error messages
            return Response::json([
                'success' => false,
                'errors' => $errors
            ]);
        }

        $this->setTemplateVars($data);

        // Validation past, so let check if the recaptcha response is valid
        if ($this->form->recaptchaEnabled()) {
            if (!$this->passesRecaptcha($data['g-recaptcha-response'])) {
                // TODO: Fails
                return;
            }
        }

        // Store the submission
        if ($this->form->savesData()) {
            $this->saveSubmission($data);
        }

        // Send notification
        if ($this->form->sendsNotifications()) {
            $this->sendNotification($data);
        }

        // Send auto reply
        if ($this->form->autoReply()) {
            $this->sendAutoReply($data);
        }
    }

    private function passesRecaptcha($response) {
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
        
        return $result->success;
    }

    /**
     * Caching facility to retrieve pre-rendered partial of the form
     * 
     * @return string
     */
    public function getRenderedPartial()
    {
        if ($this->form->enableCaching()) {
            return Cache::remember('abwebdevelopers_form_' . $this->form->code, $this->form->cacheLifetime(), function() {
                return $this->renderForm();
            });
        }

        return $this->renderForm();
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

        // If there are recipients
        if (!empty($to)) {
            $to = array_filter(explode(',', $to));

            // Check if there are no recipients
            if (empty($to)) {
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

            // Validate the list of email addresses
            if (!$validator->passes()) {
                // Return (a single) error
                return Response::json([
                    'success' => false,
                    'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.invalidNotificationRecipients')
                ]);
            }

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
            });

            return true;
        }

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

        if (empty($to_email)) {
            // Return (a single) error if the email field could not be resolved
            return Response::json([
                'success' => false,
                'error' => 'Auto-reply Email field could not be resolved'
            ]);
        }

        // Resolve the user's name using the configured field
        $to_name = $this->form->autoReplyNameField();
        $to_name = (!empty($to_name) && !empty($data[$to_name->code])) ? $data[$to_name->code] : null;

        if (empty($to_name)) {
            // Return (a single) error if the name field could not be resolved
            return Response::json([
                'success' => false,
                'error' => 'Auto-reply Name field could not be resolved'
            ]);
        }

        // If email and name were resolved successfully then continue
        $template = $this->form->autoReplyTemplate();

        // Only queue if configured to queue emails
        $method = Settings::get('queue_emails', true) ? 'queue' : 'send';

        // Send the auto reply
        Mail::{$method}($template, $this->templateVars, function($message) use ($to_email, $to_name) {
            $message->to($to_email, $to_name);
        });

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

        // Save it, in case any errors occur when sending emails
        return $this->submission = Submission::create($submissionData);
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

            // Add this field
            $fields[$key] = [
                'name' => $field->name,
                'type' => $field->type,
                'description' => $field->description,
                'value' => $value,
            ];
        }

        // Set the variables
        return $this->templateVars = [
            'fields' => $fields,
            'form' => $this->form->toArray(),
            'moreInfoLink' => ($this->submission) ? $this->submission->viewLink() : null
        ];
    }

    /**
     * Get a Settings value by $key
     */
    public function setting(string $key) {
        return Settings::get($key);
    }
    
}
