# Custom Forms

October Plugin for self created and styled forms, with a variety of customisations, emailing, storing submissions, etc

### Usage (October CMS)

Simple. Firstly you will need a form. Installing this plugin will automatically generate a basic contact form. This can be deleted or used as a reference - up to you.

##### Settings

There are 3 main levels of settings: `Site Wide Settings` > `Form Settings` > `Field Settings`.

Form and Field settings that override Site Wide and Form settings (respectively) are accompanied by an "override" checkbox, which when checked allows the respective setting be override the 'more global' version of the setting.

Certain settings are only available at global level (Google recaptcha keys, queue emails, etc), while some are only available at field level.

##### Creating a form

After configuring the global settings, head to the Custom Forms navigation item in the backend menu and click "Create".

Here you can enter the title of the form, and a code for it (which is used in layouts/the component for referencing the correct form).

**Please Note**
Deferred binding on fields for forms is not configured just yet, meaning you will need to save the form before adding any fields. Feel free to open a PR for the fix to this.

After saving the form, you can now add fields by clicking the "Create fields" button

Fairly straight forward process, each field has a comment explaining the fields' purpose a little.

Validation can be configured - accepts a string of rules, `|` (pipe) delimited, as per normal Laravel / October [Validation Rules](https://octobercms.com/docs/services/validation#available-validation-rules). Only supports a single message per field at the moment.

##### Adding a form to a layout

As you would with component, open the layout and insert the respective component. This component comes with one required property:

- **Use Form (formCode):** This references a `Form` via the `code` field. Make sure it's set correctly.

##### Events

Currently there are about 24 events which may fire (depending on your configurations, of course). If you feel like there's an important event missing, please open an issue or PR.

```php
// Runs at the beginning of "onRun" (when loading a page with a CustomForm)
Event::listen('abweb.forms.beforeRun', function (CustomForm $customForm) {
    // Do something...
    Log::debug('Loaded form: ' . $customForm->form->name);
});

// Runs at the end of "onRun" (when loading a page with a CustomForm)
Event::listen('abweb.forms.afterRun', function (CustomForm $customForm) {
    // Do something...
});

// Runs at the beginning of "onFormSubmit" (when submitting a CustomForm)
Event::listen('abweb.forms.beforeFormSubmit', function (CustomForm $customForm) {
    // Do something...
    Log::debug('User submitted form: ' . $customForm->form->name);
});

// Runs before validating the payload of a form. Can adjust data, rules, and messages
Event::listen('abweb.forms.beforeValidateForm', function (CustomForm $customForm, array &$data, array &$rules, array &$messages, Validator $validator) {
    // Do something...
});

// Runs if validating the payload of a form fails
Event::listen('abweb.forms.onValidateFormFail', function (CustomForm $customForm, array $data, array $rules, array $messages, Validator $validator) {
    // Do something...
});

// Runs if validating the payload of a form is successful
Event::listen('abweb.forms.afterValidateForm', function (CustomForm $customForm, array $data, array $rules, array $messages) {
    // Do something...
});

// Runs if validating the recaptcha response fails
Event::listen('abweb.forms.onRecaptchaFail', function (CustomForm $customForm, string $recaptchaResponse) {
    // Do something...
});

// Runs if validating the recaptcha response is successful
Event::listen('abweb.forms.onRecaptchaSuccess', function (CustomForm $customForm, array $data, array $rules, array $messages) {
    // Do something...
});

// Runs at the end of "onFormSubmit" (when submitting a CustomForm)
Event::listen('abweb.forms.afterFormSubmit', function (CustomForm $customForm, array $data, $response) {
    // Do something...
});

// Runs before rendering the form (or retrieving pre-rendered cache)
Event::listen('abweb.forms.beforeRenderPartial', function (CustomForm $customForm, bool $cachingEnabled) {
    // Do something...
    if ($cachingEnabled) {
        // Do something...
    }
});

// Runs after rendering the form (or retrieving pre-rendered cache). Can adjust HTML.
Event::listen('abweb.forms.afterRenderPartial', function (CustomForm $customForm, string &$html) {
    // Do something...
    $html .= '<script src="https://api.google.com/.../library.js"></script>';
});

// Runs before sending notification emails. Can adjust data and recipient
Event::listen('abweb.forms.beforeSendNotification', function (CustomForm $customForm, array &$data, array &$to) {
    // Do something...
});

// Runs before validating notification recipients. Can adjust data, recipient and rules
Event::listen('abweb.forms.beforeNotificationValidation', function (CustomForm $customForm, array $data, array &$to, array &$rules, Validator &$validator) {
    // Do something...
});

// Runs if validating notification recipients fails
Event::listen('abweb.forms.onNotificationValidationFail', function (CustomForm $customForm, array $data, array $to, array $rules, Validator $validator) {
    // Do something...
    Log::info($validator->messages()->toArray());
});

// Runs if validating notification recipients is successful
Event::listen('abweb.forms.onNotificationValidationSuccess', function (CustomForm $customForm, array $data, array $to, array $rules) {
    // Do something...
});

// Runs when configuring the $message to send a notification to recipients
Event::listen('abweb.forms.onSendNotification', function (CustomForm $customForm, &$message, $to) {
    // Do something...
    $message->replyTo('noreply@domain.com');
});

// Runs after sending (or queueing) notification to recipients
Event::listen('abweb.forms.afterSendNotification', function (CustomForm $customForm, array $data, bool $success) {
    // Do something...
    if (!$success) {
        Log::debug('Dammit whats wrong now?');
    }
});

// Runs before sending auto reply email. Can adjust data, recipient name and email
Event::listen('abweb.forms.beforeSendAutoReply', function (CustomForm $customForm, array &$data, &$toEmail, &$toName) {
    // Do something...
    $toEmail = 'new@example.org';
    $toName = 'Mr. Nobody';
});

// Runs if validating auto reply recipient fails.
Event::listen('abweb.forms.onAutoReplyValidationFail', function (CustomForm $customForm, array $data, $toEmail, $toName, string $failedOn) {
    // Do something...
    if ($failedOn === 'email') {
        Log::debug('Invalid auto-reply email');
    } else { // 'name'
        Log::debug('Invalid auto-reply name');
    }
});

// Runs when configuring the $message to send an automatic reply to the user
Event::listen('abweb.forms.onSendAutoReply', function (CustomForm $customForm, &$message, $to) {
    // Do something...
    $message->bcc('mwahahaha@domain.com');
});

// Runs after sending (or queueing) auto reply email
Event::listen('abweb.forms.afterSendAutoReply', function (CustomForm $customForm, array $data, bool $success) {
    // Do something...
    if (!$success) {
        Log::debug($data);
    }
});

// Runs before saving the submission in the database. Can adjust the Submission's data
Event::listen('abweb.forms.beforeSaveSubmission', function (CustomForm $customForm, array &$submissionData) {
    // Do something...
    $submissionData['extraField'] = 'Add this to the database please';
});

// Runs after saving the submission in the database
Event::listen('abweb.forms.afterSaveSubmission', function (CustomForm $customForm, Submission $submission) {
    // Do something...
    if ($submission->url == '/') {
        $submission->delete();
    }
});

// Runs before setting email template vars. Can adjust the variables
Event::listen('abweb.forms.beforeSetTemplateVars', function (CustomForm $customForm, array &$vars) {
    // Do something...
    $vars['date'] = \Carbon\Carbon::now()->format('jS F Y');
});
```

### Bugs and feature requests

We encourage open source, so if you find any bugs, typos, issues or think of some great features, please open an issue or PR in the GitHub repo.
