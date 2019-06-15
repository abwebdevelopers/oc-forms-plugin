# Custom Forms

October Plugin for self created and styled forms, with a variety of customisations, emailing, storing submissions, etc

## Please Note: This plugin is still in development so will likely have bugs. Check back within a week for a working version

### Usage (October CMS)

Simple. Firstly you will need a form. Installing this plugin will automatically generate a basic contact form. This can be deleted or used as a reference - up to you.

#### Global Settings

I would recommend highly looking at the global settings first. Fill these out and each form will inherit these settings. Each form can opt-in to override certain sections of the configuration (for example, one form has a "Send" button not a "Submit" button so you can opt in to override the styling and buttons section).

Certain settings are available at field-level as well, giving it 3 tiers: `Site Wide Settings` > `Form Settings` > `Field Settings`

Certain settings are only available at global level (Google recaptcha keys, queue emails, etc).

#### Creating a form

After configuring the global settings, head to the Custom Forms navigation item in the backend. Click "Create".

Here you can enter the title of the form, and a code for it, which is used in layouts/the component for referencing the correct form.

**Please Note**
Deferred binding on fields for forms is not configured just yet, meaning you will need to save the form before adding any fields. Feel free to open a PR for the fix to this.

After saving the form, you can now add fields by clicking the "Create fields" button

Fairly straight forward process, each field has a comment explaining the fields' purpose a little.

Validation can be configured - accepts a string of rules, `|` (pipe) delimited, as per normal Laravel / October validation rules. Only supports a single message per field at the moment.

**Overriding Settings**

If you are to override a section (such as styling, privacy, antispam, etc), all fields in that section will be now used AS THEY APPEAR. It has been done this way to allow for inheritance of a setting, and allowing it to be be "blank and override its global counterpart".

For example, you may have the global settings for the "Form Class" set to `form`, but for this one form you don't want any class on it. Leaving this field blank (in the form settings) means it is interpreted as having no class. Unfortunately, there isn't any clean way of allowing inheritance and allowing blank values to override, without creating per-setting toggles to say "activate this", or relying on the user to ensure if they want a blank class, to enter some non-existant class.

So this means if you only want to adjust one styling setting, you will need to fill the rest. A better more rigid solution is coming soon.


### Adding a form to a layout

As you would with component, open the layout and insert the respective component. This component comes with 3 properties:

- **Use Form (formCode):** This references a form via the `code` field. Make sure it's set correctly.
- **Cache the Form (cacheView):** Whether or not to cache the form.
- **Cache Lifetime (cacheLifetime):** The amount of minutes the cache should last for


### Bugs and feature requests

We encourage open source, so if you find any bugs or think of some great features, please open an issue in the GitHub repo.