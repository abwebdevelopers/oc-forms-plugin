<?php namespace ABWebDevelopers\Forms\Models;

use Model;
use ABWebDevelopers\Forms\Models\ValidationRule;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;

class Field extends Model
{
    use Validation, Sortable;

    const SORT_ORDER = 'sort_order';

    /**
     * @var string The database table used by the model.
     */
    public $table = 'abwebdevelopers_forms_fields';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required|string|min:1|max:191',
        'code' => 'required|string|min:1|max:80|regex:/^[a-z_]+[a-z0-9]*$/',
    ];

    /**
     * @var array Whitelist of fields allowing mass assignment
     */
    public $fillable = [
        'name',
        'form_id',
        'code',
        'type',
        'description',
        'placeholder',
        'required',
        'validation_rules',
        'validation_message',
        'field_class',
        'row_class',
        'group_class',
        'label_class',
        'options',
    ];

    /**
     * @var array List of fields which have an occumpanied "Override" checkbox configuration
     */
    public $overrides = [
        'field_class',
        'row_class',
        'group_class',
        'label_class',
    ];

    public $jsonable = [
        'options',
        'html_attributes',
    ];

    public $belongsTo = [
        'form' => Form::class
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
                $this->{$override} = ($this->{$field} !== null);
            }
        }
    }

    /**
     * Before the Field's Save event.
     * Dynamically set the required field if "required" is in the validation rules
     * Remove override_{field} Fields
     *
     * @return void
     */
    public function beforeSave(): void
    {
        if (!empty($this->overrides)) {
            // Convert inherited values to null
            foreach ($this->overrides as $field) {
                $override = 'override_' . $field;
                if (!$this->{$override}) {
                    $this->{$field} = null;
                }
                unset($this->{$override});
            }
        }

        // If validation rules are present, but the field is not required..
        if (!empty($this->validation_rules) && !$this->required) {
            // Set required to whether or not required is in the validation rules
            $this->required = (bool) in_array('required', explode('|', $this->validation_rules));
        }
    }

    /**
     * Get the field's validation rules, and add dynamic rules based on the field
     * type, required flag, etc.
     *
     * @return string
     */
    public function getCompiledRulesAttribute(): string
    {
        // Get array of validation rules
        $fieldRules = (!empty($this->validation_rules)) ? explode('|', $this->validation_rules) : [];

        // If no 'email' rule, but field is email, add it
        if (!in_array('email', $fieldRules) && $this->type === 'email') {
            $fieldRules[] = 'email';
        }

        // If no 'numeric' rule, but field is numeric, add it
        if (!in_array('numeric', $fieldRules) && $this->type === 'number') {
            $fieldRules[] = 'numeric';
        }

        // If no 'required' rule, but field is required, add it
        if (!in_array('required', $fieldRules) && $this->required) {
            $fieldRules[] = 'required';
        }

        // Return compiled list of rules
        return implode('|', $fieldRules);
    }

    /**
     * Retrieve the option rules for validation (i.e value must be in a list of keys)
     *
     * @return string
     */
    public function getOptionRulesAttribute(): string
    {
        $fieldRules = [];

        if (in_array($this->type, ['checkbox','radio','select'])) {
            $keys = $this->getOptionKeys();

            if (empty($keys)) {
                $fieldRules[] = 'accepted';
            } else {
                $fieldRules[] = 'in:' . implode(',', $this->getOptionKeys());
            }
        }

        // Return compiled list of rules
        return implode('|', $fieldRules);
    }

    /**
     * Get the 'type' field's dropdown options
     *
     * @return array
     */
    public function getTypeOptions(): array
    {
        return [
            'text' => 'Text',
            'email' => 'Email',
            'number' => 'Number',
            'date' => 'Date',
            'textarea' => 'Textarea',
            'select' => 'Select',
            'checkbox' => 'Checkbox',
            'radio' => 'Radio',
            'url' => 'URL',
            'tel' => 'Tel',
            'file' => 'File',
            'image' => 'Image',
            'password' => 'Password',
            'color' => 'Color',
        ];
    }

    /**
     * Get the Field's unique ID
     *
     * @param Form $form
     * @return string
     */
    public function getId(Form $form): string
    {
        return 'form_' . $form->code . '_' . $this->code;
    }

    /**
     * Retrieve the list of available options (in object form, not assoc array)
     *
     * @return array
     */
    public function getOptions(): array
    {
        return (array) json_decode(json_encode($this->options));
    }

    /**
     * Retrieve the list of option keys (for validation rule `in:`)
     *
     * @return array
     */
    public function getOptionKeys(): array
    {
        $keys = [];

        $options = $this->getOptions();
        foreach ($options as $option) {
            if (!empty($option->is_optgroup)) {
                foreach ($option->options as $option2) {
                    $keys[] = $option2->option_code;
                }
            } else {
                $keys[] = $option->option_code;
            }
        }

        return $keys;
    }

    /**
     * Retrieve an option label by code
     *
     * @param string $key
     * @return string|null The option label
     */
    public function getOption(string $key): ?string
    {
        foreach ($this->options as $option) {
            if ($option['is_optgroup'] ?? false) {
                foreach ($option['options'] ?? [] as $opt) {
                    if ($opt['option_code'] === $key) {
                        return $opt['option_label'];
                    }
                }
            }

            if ($option['option_code'] === $key) {
                return $option['option_label'];
            }
        }

        return null;
    }
}
