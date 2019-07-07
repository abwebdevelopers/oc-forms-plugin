<?php

namespace ABWebDevelopers\Forms\Classes;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
use YeTii\HtmlElement\Elements\Div as HtmlDiv;
use YeTii\HtmlElement\Elements\Span as HtmlSpan;
use YeTii\HtmlElement\Elements\Form as HtmlForm;
use YeTii\HtmlElement\Elements\Input as HtmlInput;
use YeTii\HtmlElement\Elements\Select as HtmlSelect;
use YeTii\HtmlElement\Elements\Textarea as HtmlTextarea;
use YeTii\HtmlElement\Elements\Label as HtmlLabel;

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
        
        $htmlForm = new HtmlForm([
            'id' => 'form_' . $form->code,
            'nodes' => [
                $loadingIndicator,
                $fields,
            ],
        ]);

        return $htmlForm;
    }

    public function generateField($form, $field)
    {
        $classes = [
            'custom-form-input',
            'abweb-form-' . $field->type,
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

        return new HtmlDiv([
            'class' => implode(' ', $classes),
            'nodes' => [
                $label,
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
            ]);
            foreach ($options as $i => $option) {
                $el->addChild(new HtmlLabel([
                    'for' => $fieldId . '_' . $i,
                    'class' => 'abweb-form-option',
                    'nodes' => [
                        new HtmlInput([
                            'type' => $field->type,
                            'name' => $field->code . '[' . $i . ']',
                            'id' => $fieldId . '_' . $i,
                            'value' => $option,
                            'required' => $field->required, // TODO: remove?
                        ]),
                        new HtmlSpan([
                            'node' => $option,
                        ]),
                    ]
                ]));
            }
        } else {
            $el = new HtmlInput([
                'type' => $field->type,
            ]);

            $el->set($this->resolveTypeGlobal($form, $field));
        }

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
        $el = new HtmlInput([
            'type' => 'text',
        ]);

        $el->set($this->resolveTypeGlobal($form, $field));

        $el->addChild(new HtmlOption([
            'value' => '',
            'selected' => true,
            'disabled' => $field->required,
            'node' => $field->placeholder,
        ]));

        foreach ($field->getOptions() as $option) {
            $el->addChild(new HtmlOption([
                'value' => $option,
                'node' => $option,
            ]));
        }

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

        return $el;
    }
}