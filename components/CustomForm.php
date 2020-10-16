<?php

namespace ABWebDevelopers\Forms\Components;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Settings;
use ABWebDevelopers\Forms\Models\Submission;
use ABWebDevelopers\Forms\Classes\HtmlGenerator;
use Cms\Classes\ComponentBase;
use System\Models\File;
use Backend;
use Cache;
use Event;
use Input;
use Lang;
use Mail;
use Request;
use Response;
use Validator;
use Illuminate\Http\JsonResponse;

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
     * @var array<string,Field> List of Fields (values) accessible by Field codes (keys)
     */
    protected $fields = [];

    /**
     * @var Submission The current form submission entity
     */
    private $submission;

    /**
     * @var HtmlGenerator The HTML generator instance
     */
    private $htmlGenerator;

    /**
     * @var array All variables that will be available in the email templates
     */
    protected $templateVars = [];

    /**
     * @var array All uploaded files
     */
    protected $uploadedFiles = [];

    /**
     * Component details definition
     *
     * @return array
     */
    public function componentDetails(): array
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
    public function defineProperties(): array
    {
        return [
            'formCode' => [
                'title'             => 'abwebdevelopers.forms::lang.customForm.formCode.title',
                'description'       => 'abwebdevelopers.forms::lang.customForm.formCode.description',
                'default'           => '',
                'type'              => 'dropdown',
                'required'          => true,
            ],
        ];
    }

    /**
     * Get Form Code Options for Component property
     *
     * @return array
     */
    public function getFormCodeOptions(): array
    {
        $options = [];

        foreach (Form::all() as $form) {
            $options[$form->code] = $form->title;
        }

        return $options;
    }

    /**
     * Get this component's form
     *
     * @return Form
     */
    public function form(): Form
    {
        return $this->form;
    }

    /**
     * Autoload the form using the form code with fields
     *
     * @return Form
     */
    public function loadForm(): Form
    {
        return $this->form = Form::with([
            'fields' => function ($query) {
                return $query->orderBy('sort_order', 'asc');
            }
        ])->where('code', $this->property('formCode'))->firstOrFail();
    }

    /**
     * On Run Handler - The GET action
     *
     * @return void
     */
    public function onRun(): void
    {
        // Fire beforeRun event
        Event::fire(self::EVENTS_PREFIX . 'beforeRun', [$this]);

        // Autoload the form
        $this->loadForm();

        // Load required CSS
        $this->addCss('/plugins/abwebdevelopers/forms/assets/custom-form.css');
        $this->addJs('/plugins/abwebdevelopers/forms/assets/custom-form.js');

        // Fire afterRun event
        Event::fire(self::EVENTS_PREFIX . 'afterRun', [$this]);

        $this->getRenderedPartial();
    }

    /**
     * Validate a field via AJAX
     *
     * @return JsonResponse
     */
    public function onFieldValidate(): JsonResponse
    {
        // Autoload the form
        $this->loadForm();

        $field = str_replace('[]', '', Input::get('__field'));
        $value = Input::get('__value');

        // Get all form fields, rules and messages
        list($fields, $rules, $messages) = $this->getFormValidation();

        // Get only what we asked for
        $data = $this->getInputFields($fields);

        // Validate the form (all fields, to allow for required_if, required_unless, etc)
        $validator = Validator::make($data, $rules, $messages);

        // Get the actual field object
        $field = $this->fields[$field];

        // Fire beforeValidateField event
        Event::fire(self::EVENTS_PREFIX . 'beforeValidateField', [$this, $field, &$value, &$data, &$rules, &$messages, &$validator]);

        if (!$validator->passes()) {
            $errors = $validator->messages()->toArray();

            // Only fails if this speciifc field fails
            if (!empty($errors[$field->code])) {
                // Fire onValidateFieldFail event
                Event::fire(self::EVENTS_PREFIX . 'onValidateFieldFail', [$this, $field, $value, $data, $rules, $messages, $validator]);

                $errors = $errors[$field->code];

                return Response::json([
                    'success' => false,
                    'error' => implode(" \n", $errors),
                ], 400);
            }
        }

        // Fire onValidateFieldPass event
        Event::fire(self::EVENTS_PREFIX . 'onValidateFieldPass', [$this, $field, $value, $data, $rules, $messages, $validator]);

        return Response::json([
            'success' => true,
        ], 200);
    }

    /**
     * On Form Submit Handler - The (ajax) POST action
     *
     * @return JsonResponse
     */
    public function onFormSubmit(): JsonResponse
    {
        // Fire beforeFormSubmit event
        Event::fire(self::EVENTS_PREFIX . 'beforeFormSubmit', [$this]);

        // Autoload the form
        $this->loadForm();

        // Get all form fields, rules and messages
        list($fields, $rules, $messages) = $this->getFormValidation();

        // If Google recaptcha enabled, add validation for it
        if ($this->form->recaptchaEnabled()) {
            $fields[] = 'g-recaptcha-response';
            $rules['g-recaptcha-response'] = 'required|string';
            $messages['g-recaptcha-response.*'] = Lang::get('abwebdevelopers.forms::lang.customForm.validation.recaptchaFailed');
        }

        // Get only what we asked for
        $data = $this->getInputFields($fields);

        $files = [];
        if ($this->form->hasFileField()) {
            foreach ($this->form->fields as $field) {
                if ($field->type === 'file' || $field->type === 'image') {
                    $files[] = $field->code;
                }
            }
        }

        // If no data was supplied, reject the request
        if (empty($data)) {
            return Response::json([
                'success' => false,
                'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.noData')
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

        // Upload the files
        $this->uploadedFiles = [];
        if (!empty($files)) {
            foreach ($files as $key) {
                if (!empty($data[$key])) {
                    $this->uploadedFiles[$key] = (new File())->fromPost($data[$key]);
                }
            }
        }
        unset($files);

        // Set the email template vars
        $this->setTemplateVars($data);

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
     * Retrieve all input fields
     *
     * @param array $fields
     * @return array
     */
    public function getInputFields(array $fields = null): array
    {
        $data = Input::only($fields);

        // Ensure checkboxes, radios and selects are dealt with as arrays (even if only accepting one value)
        foreach ($data as $code => $value) {
            if (!empty($this->fields[$code])) {
                $field = $this->fields[$code];
                if (in_array($field->type, ['checkbox', 'radio', 'select'])) {
                    $data[$code] = (array) $value;
                }
            }
        }

        return $data;
    }

    /**
     * Get all form fields, their rules, and their validation messages
     *
     * @return array
     */
    public function getFormValidation(): array
    {
        $fields = [];
        $rules = [];
        $messages = [];

        // Get a list of all fields, any validation rules and messages
        foreach ($this->form->fields as $field) {
            $fields[] = $field->code;

            // Create an easy-to-access array of fields
            $this->fields[$field->code] = $field;

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

        return [$fields, $rules, $messages];
    }

    /**
     * Verify if a given response is a valid recaptcha response by cross referencing it
     * with Google's recaptcha verify API
     *
     * @param string $response
     * @return bool
     */
    private function passesRecaptcha(string $response)
    {
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
            $form = Cache::remember('abwebdevelopers_form_' . $this->form->code, $this->form->cacheLifetime(), function () {
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
     * Get and set the HtmlGenerator instance for generating this form
     *
     * @return HtmlGenerator
     */
    public function getHtmlGenerator()
    {
        $this->htmlGenerator = new HtmlGenerator();

        return $this->htmlGenerator->generateForm($this->form);
    }

    /**
     * Render the form!
     *
     * @return string;
     */
    public function renderForm()
    {
        if (empty($this->form)) {
            return '<!-- Invalid form -->';
        }

        return $this->getHtmlGenerator()->render();
    }

    /**
     * Return the setting for the ReCAPTCHA Public Key
     */
    public function recpatchaPublicKey()
    {
        return Settings::get('recaptcha_public_key');
    }

    /**
     * Send the notificaiton to the administrator(s) which is either
     * defined at a global level, or per form.
     *
     * @param array $data
     * @return Response|bool
     */
    private function sendNotification(array $data)
    {
        // Get notification recipients from form, or global settings if not set
        $to = $this->form->notificationRecipients();

        // Get notification reply-to email from form
        $replytoEmailField = $this->form->notifReplytoEmailField();
        $replytoEmail = (!empty($replytoEmailField) && !empty($data[$replytoEmailField->code])) ? $data[$replytoEmailField->code] : null;

        // Get notification reply-to name from form
        $replytoNameField = $this->form->notifReplytoNameField();
        $replytoName = (!empty($replytoNameField) && !empty($data[$replytoNameField->code])) ? $data[$replytoNameField->code] : null;

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
            $method = Settings::get('queue_emails', false) ? 'queue' : 'send';

            // Get attachments and vars for email
            $attachments = $this->getAttachments();
            $vars = $this->getTemplateVars('notification');

            // Send the notification
            Mail::{$method}($template, $vars, function ($message) use ($to, $attachments, $replytoEmail, $replytoName) {
                if (count($to) === 1) {
                    $message->to(current($to), 'Admin');
                } else {
                    $main = array_shift($to);
                    $message->to($main, 'Admin');
                    foreach ($to as $recipient) {
                        $message->cc($recipient, 'Admin');
                    }
                }

                foreach ($attachments as $key => $attachment) {
                    $message->attach($attachment, [ 'as' => $key ]);
                }

                if ($this->form->notifReplyto() && !empty($replytoEmail)) {
                    $message->replyTo($replytoEmail, $replytoName);
                }
                
                Event::fire(self::EVENTS_PREFIX . 'onSendNotification', [$this, &$message, $to, $attachments]);
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
    private function sendAutoReply($data)
    {
        // Resolve the user's email address using the configured field
        $toEmailField = $this->form->autoReplyEmailField();
        $toEmail = (!empty($toEmailField) && !empty($data[$toEmailField->code])) ? $data[$toEmailField->code] : null;

        // Resolve the user's name using the configured field
        $toNameField = $this->form->autoReplyNameField();
        $toName = (!empty($toNameField) && !empty($data[$toNameField->code])) ? $data[$toNameField->code] : null;

        // If the email wasn't provided
        if (empty($toEmail) || empty($toName)) {
            // Fire onAutoReplyValidationFail event
            Event::fire(self::EVENTS_PREFIX . 'onAutoReplyEmailNotProvided', [$this, $data, $toEmail, $toName]);
            
            return false;
        }

        // Fire beforeSendAutoReply event
        Event::fire(self::EVENTS_PREFIX . 'beforeSendAutoReply', [$this, &$data, &$toEmail, &$toName]);

        if (empty($toEmailField)) {
            // Fire onAutoReplyValidationFail event
            Event::fire(self::EVENTS_PREFIX . 'onAutoReplyValidationFail', [$this, $data, $toEmail, $toName, 'email']);

            // Return (a single) error if the email field could not be resolved
            return Response::json([
                'success' => false,
                'error' => Lang::get('abwebdevelopers.forms::lang.customForm.validation.noAutoReplyEmailField'),
            ], 501);
        }

        if (empty($toNameField)) {
            // Fire onAutoReplyValidationFail event
            Event::fire(self::EVENTS_PREFIX . 'onAutoReplyValidationFail', [$this, $data, $toEmail, $toName, 'name']);

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

        // Get attachments and vars for email
        $attachments = $this->getAttachments();
        $vars = $this->getTemplateVars('autoreply');

        // Send the auto reply
        Mail::{$method}($template, $vars, function ($message) use ($toEmail, $toName, $attachments) {
            $message->to($toEmail, $toName);

            foreach ($attachments as $key => $attachment) {
                $message->attach($attachment, [ 'as' => $key ]);
            }

            Event::fire(self::EVENTS_PREFIX . 'onSendAutoReply', [$this, &$message, $toEmail, $toName, $attachments]);
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
    private function saveSubmission($data)
    {
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
     * Retrieve all relevant email template variables for the $email provided.
     *
     * @param string $email Either 'notification' or 'autoreply' or whichever custom ones are used
     * @return array
     */
    public function getTemplateVars(string $email): array
    {
        $vars = $this->templateVars;
        $vars['fields'] = collect($vars['fields'])->filter(function ($field) use ($email) {
            return $field['show_in_email_' . $email] ?? false;
        })->toArray();

        return $vars;
    }

    /**
     * Set template vars for email templates, including the form, the fields with
     * their respective values, and a moreInfo link (to submission, if saved)
     *
     * @param array $data
     * @return array
     */
    public function setTemplateVars(array $data)
    {
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

            if ($field->type === 'checkbox') {
                if (empty($field->options)) {
                    $value = 'Checked';
                }
            }

            if (is_array($value)) {
                $options = $field->options;

                if (count($value) === 0) {
                    $value = 'N/A';
                } elseif (count($value) === 1) {
                    $value = current($value);
                    $value = $field->getOption($value);
                } else {
                    $array = [];
                    foreach ($value as $val) {
                        $val = $field->getOption($val);

                        if ($val !== null) {
                            $array[] = $val;
                        }
                    }
                    $value = implode(', ', $array);
                }
            }

            $raw = false;

            if ($field->type === 'file' || $field->type === 'image') {
                $filename = $this->getSafeFileName($this->uploadedFiles[$field->code]);

                $value = 'See Attached: <code>' . $filename . '</code>';

                $raw = true;
            }

            // Add this field
            $fields[$key] = [
                'name' => $field->name,
                'type' => $field->type,
                'description' => $field->description,
                'value' => $value,
                'raw' => $raw,
                'show_in_email_autoreply' => $field->show_in_email_autoreply,
                'show_in_email_notification' => $field->show_in_email_notification,
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
    public function setting(string $key)
    {
        return Settings::get($key);
    }

    /**
     * Retrieve the submission from the component
     */
    public function getSubmission()
    {
        return $this->submission;
    }

    /**
     * Retrieve a list of file paths from the uploaded files
     *
     * @return array
     */
    public function getAttachments()
    {
        $attachments = [];

        foreach ($this->uploadedFiles as $key => $file) {
            $filename = $this->getSafeFileName($file);
            $attachments[$filename] = $file->getLocalPath();
        }

        return $attachments;
    }

    /**
     * Retrieve safe file name from a file. Filename will start with alphanumeric characters
     * only, followed by alphanumeric or basic, safe, ascii symbols
     *
     * @param File $file
     * @return string
     */
    public function getSafeFileName(File $file): string
    {
        $filename = $this->afterLast($file->getFileName(), '/');
        $filename = preg_replace('/(^[^a-z0-9_])|[^a-z0-9_\.\_\-\(\)\[\]]/i', '', $filename);

        return $filename;
    }

    /**
     * Retrieve a substring from after the occurrence $needle in a $haystack
     *
     * @param string $haystack
     * @param string $needle
     * @return string
     */
    public function afterLast(string $haystack, string $needle): string
    {
        $pos = strrpos($haystack, $needle);

        return $pos === false ? $haystack : substr($haystack, $pos + 1);
    }
}
