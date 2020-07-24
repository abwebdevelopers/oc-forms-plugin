<?php

namespace ABWebDevelopers\Forms\Classes;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use ABWebDevelopers\Forms\Models\Settings;
use YeTii\HtmlElement\Element;
use YeTii\HtmlElement\Elements\HtmlDiv;
use YeTii\HtmlElement\Elements\HtmlSpan;
use YeTii\HtmlElement\Elements\HtmlForm;
use YeTii\HtmlElement\Elements\HtmlInput;
use YeTii\HtmlElement\Elements\HtmlOption;
use YeTii\HtmlElement\Elements\HtmlOptgroup;
use YeTii\HtmlElement\Elements\HtmlSelect;
use YeTii\HtmlElement\Elements\HtmlTextarea;
use YeTii\HtmlElement\Elements\HtmlLabel;
use YeTii\HtmlElement\Elements\HtmlButton;
use YeTii\HtmlElement\Elements\HtmlP;

class HtmlGenerator
{
    public function generateForm(Form $form)
    {
        $loadingIndicator = new HtmlDiv([
            'class' => 'loading-indicator',
            'nodes' => [
                new HtmlDiv([
                    'nodes' => [
                        new HtmlSpan([
                            'node' => '.',
                        ]),
                        new HtmlSpan([
                            'node' => '.',
                        ]),
                        new HtmlSpan([
                            'node' => '.',
                        ]),
                    ]
                ])
            ]
        ]);

        $fields = [];

        foreach ($form->fields as $field) {
            $fields[] = $this->generateField($form, $field);
        }

        $fields = new HtmlDiv([
            'class' => $form->rowClass(),
            'nodes' => $fields,
        ]);

        $recaptcha = '';
        if ($form->recaptchaEnabled()) {
            $recaptcha = new HtmlDiv([
                'class' => $form->rowClass(),
                'node' => new HtmlDiv([
                    'class' => $form->groupClass(),
                    'nodes' => [
                        new HtmlDiv([
                            'class' => 'g-recaptcha',
                            'data-sitekey' => Settings::get('recaptcha_public_key'),
                        ]),
                        new HtmlDiv([
                            'class' => 'form-field-error-message text-danger',
                            'style' => 'display:none'
                        ]),
                    ]
                ])
            ]);
        }

        $cancel = '';
        if ($form->enableCancel()) {
            $cancel = new HtmlButton([
                'class' => $form->cancelClass(),
                'node' => $form->cancelText(),
            ]);
        }

        $buttons = new HtmlDiv([
            'class' => $form->rowClass(),
            'node' => [
                new HtmlDiv([
                    'class' => $form->groupClass(),
                    'nodes' => [
                        $cancel,
                        new HtmlButton([
                            'type' => 'submit',
                            'class' => $form->submitClass(),
                            'node' => $form->submitText(),
                        ]),
                    ]
                ])
            ]
        ]);

        $dataClasses = [
            'data-label-success-class' => $form->labelSuccessClass(),
            'data-label-error-class' => $form->labelErrorClass(),
            'data-field-success-class' => $form->fieldSuccessClass(),
            'data-field-error-class' => $form->fieldErrorClass(),
            'data-form-success-class' => $form->formSuccessClass(),
            'data-form-error-class' => $form->formErrorClass(),
        ];

        $htmlForm = new HtmlForm([
            'id' => 'form_' . $form->code,
            'class' => 'custom-form',
            'nodes' => [
                $loadingIndicator,
                $fields,
                $recaptcha,
                $buttons
            ],
        ]);

        if ($form->hasFileField()) {
            $htmlForm->set([
                'data-request-files' => true,
            ]);
        }

        $htmlForm->set($dataClasses);

        return $htmlForm;
    }

    public function generateField($form, $field)
    {
        $classes = [
            'custom-form-input',
            'abf-' . $field->type,
            $form->groupClass($field),
        ];

        $fieldId = $field->getId($form);

        $label = new HtmlLabel([
            'for' => $fieldId,
            'class' => $form->labelClass($field),
            'node' => $field->name
        ]);

        if ($field->required) {
            $label->addChild(new HtmlSpan([
                'class' => 'required'
            ]));
        }

        $element = $this->generateElement($form, $field);

        $error = new HtmlDiv([
            'class' => 'form-field-error-message text-danger',
            'style' => 'display:none;'
        ]);

        $description = '';
        if ($field->show_description) {
            $description = new HtmlP([
                'class' => 'field-description',
                'node' => $field->description,
            ]);
        }

        return new HtmlDiv([
            'class' => implode(' ', $classes),
            'nodes' => [
                $label,
                $description,
                $element,
                $error
            ]
        ]);
    }

    /**
     * Generate a Form Element
     *
     * @param Form  $form
     * @param Field $field
     * @return HtmlElement
     */
    public function generateElement(Form $form, Field $field)
    {
        $method = 'resolveType' . ucfirst($field->type);

        return $this->{$method}($form, $field);
    }

    /**
     * Return an array of arrtributes for all form field types
     *
     * @param Form $form
     * @param Field $field
     * @return array
     */
    public function resolveTypeGlobal(Form $form, Field $field)
    {
        return [
            'name' => $field->code,
            'id' => $field->getId($form),
            'class' => $form->fieldClass($field),
            'required' => $field->required,
        ];
    }

    /**
     * Resolve HtmlElement for checkbox fields. Used for radio fields too.
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeCheckbox(Form $form, Field $field)
    {
        $fieldId = $field->getId($form);

        $options = $field->getOptions();
        if (!empty($options)) {
            $el = new HtmlDiv([
                'id' => $fieldId . '_options',
                'class' => 'abf-' . $field->type . '-options',
            ]);

            foreach ($options as $option) {
                if (!empty($option->is_optgroup)) {
                    $label = new HtmlLabel([
                        'class' => 'abf-' . $field->type . '-optlabel',
                        'node' => $option->option_label,
                    ]);

                    $optgroup = new HtmlDiv([
                        'id' => $fieldId . '_option_group',
                        'class' => 'abf-' . $field->type . '-optgroup',
                        'nodes' => [
                            $label,
                        ],
                    ]);

                    foreach ($option->options as $option2) {
                        $input = new HtmlInput([
                            'type' => $field->type,
                            'name' => $field->code . ($field->type === 'radio' ? '' : '[]'),
                            'id' => $fieldId . '_' . $option2->option_code,
                            'value' => $option2->option_code,
                            'node' => $option2->option_label
                        ]);

                        if ($field->required && $field->type === 'radio') {
                            $input->set([
                                'required' => true
                            ]);
                        } elseif ($field->required) {
                            $input->set([
                                'data-required-checkbox' => true,
                            ]);
                        }

                        $this->addCustomAttributes($input, $field);

                        $optgroup->addChild(new HtmlLabel([
                            'for' => $fieldId . '_' . $option2->option_code,
                            'class' => 'abf-option',
                            'nodes' => [
                                $input,
                                new HtmlSpan([
                                    'node' => $option2->option_label,
                                ]),
                            ]
                        ]));
                    }

                    $el->addChild($optgroup);

                    continue;
                }

                $input = new HtmlInput([
                    'type' => $field->type,
                    'name' => $field->code . ($field->type === 'radio' ? '' : '[]'),
                    'id' => $fieldId . '_' . $option->option_code,
                    'value' => $option->option_code,
                    'node' => $option->option_label
                ]);

                if ($field->required && $field->type === 'radio') {
                    $input->set([
                        'required' => true
                    ]);
                } elseif ($field->required) {
                    $input->set([
                        'data-required-checkbox' => true,
                    ]);
                }

                $this->addCustomAttributes($input, $field);

                $el->addChild(new HtmlLabel([
                    'for' => $fieldId . '_' . $option->option_code,
                    'class' => 'abf-option',
                    'nodes' => [
                        $input,
                        new HtmlSpan([
                            'node' => $option->option_label,
                        ]),
                    ]
                ]));
            }
        } else {
            // Single Input = typically just a true or false, like "yes I accept"
            $el = new HtmlInput([
                'type' => $field->type,
                'value' => 1, // True
            ]);

            if ($field->required) {
                $el->set([
                    'required' => true
                ]);
            }

            $el->set($this->resolveTypeGlobal($form, $field));

            $this->addCustomAttributes($el, $field);
        }

        return $el;
    }

    /**
     * Resolve HtmlElement for color fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeColor(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'color'
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for date fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeDate(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'date',
            'placeholder' => $field->placeholder,
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for email fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeEmail(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'email',
            'placeholder' => $field->placeholder,
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for file fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeFile(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'file'
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for image fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeImage(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'file',
            'accept' => 'image/*',
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for number fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeNumber(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'number',
            'placeholder' => $field->placeholder,
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for password fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypePassword(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'password'
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for radio fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeRadio(Form $form, Field $field)
    {
        $el = $this->resolveTypeCheckbox($form, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for select fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeSelect(Form $form, Field $field)
    {
        $el = new HtmlSelect([
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $el->addChild(new HtmlOption([
            'value' => '',
            'selected' => true,
            'disabled' => $field->required,
            'node' => $field->placeholder,
        ]));

        foreach ($field->getOptions() as $option) {
            if ($option->is_optgroup) {
                $optgroup = new HtmlOptgroup([
                    'label' => $option->option_label,
                ]);

                foreach ($option->options as $option) {
                    $optgroup->addChild(new HtmlOption([
                        'value' => $option->option_code,
                        'node' => $option->option_label,
                    ]));
                }

                $el->addChild($optgroup);

                continue;
            }

            $el->addChild(new HtmlOption([
                'value' => $option->option_code,
                'node' => $option->option_label,
            ]));
        }

        return $el;
    }

    /**
     * Resolve HtmlElement for tel fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeTel(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'tel'
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for text fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeText(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'text',
            'placeholder' => $field->placeholder,
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for textarea fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeTextarea(Form $form, Field $field)
    {
        $el = new HtmlTextarea();

        $el->set($this->resolveTypeGlobal($form, $field));
        
        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Resolve HtmlElement for url fields
     *
     * @param Form $form
     * @param Field $field
     * @return HtmlElement
     */
    public function resolveTypeUrl(Form $form, Field $field)
    {
        $el = new HtmlInput([
            'type' => 'url'
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $this->addCustomAttributes($el, $field);

        return $el;
    }

    /**
     * Add any custom HTML Attributes to a field
     *
     * @param Element $element
     * @param Field $field
     * @return Element
     */
    public function addCustomAttributes(Element $element, Field $field)
    {
        if (empty($field->html_attributes)) {
            return $element;
        }

        $attributes = [];
        foreach ($field->html_attributes as $attribute) {
            $attributes[$attribute['attribute_name']] = $attribute['attribute_value'];
        }

        $element->set($attributes);

        return $element;
    }
}
