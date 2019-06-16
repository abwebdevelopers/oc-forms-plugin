# Custom Forms

October Plugin for self created and styled forms, with a variety of customisations, emailing, storing submissions, etc

## Please Note: This plugin is still in development so will likely have bugs. Check back within a week for a working version

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

Validation can be configured - accepts a string of rules, `|` (pipe) delimited, as per normal Laravel / October validation rules. Only supports a single message per field at the moment.

##### Adding a form to a layout

As you would with component, open the layout and insert the respective component. This component comes with one required property:

- **Use Form (formCode):** This references a `Form` via the `code` field. Make sure it's set correctly.

### Bugs and feature requests

We encourage open source, so if you find any bugs or think of some great features, please open an issue in the GitHub repo.