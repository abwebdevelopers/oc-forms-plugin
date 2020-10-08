<?php

namespace ABWebDevelopers\Forms\Controllers;

use ABWebDevelopers\Forms\Models\Field;
use Backend\Classes\Controller;
use BackendMenu;
use October\Rain\Support\Facades\Flash;

class Form extends Controller
{
    public $implement = [
        'Backend\Behaviors\ListController',
        'Backend\Behaviors\FormController',
        'Backend\Behaviors\RelationController',
    ];

    public $listConfig = 'config_list.yaml';
    public $formConfig = 'config_form.yaml';
    public $relationConfig = 'config_relation.yaml';

    public function __construct()
    {
        parent::__construct();
        BackendMenu::setContext('ABWebDevelopers.Forms', 'bradie-forms', 'forms-forms');
    }

    public function onMoveFieldUp()
    {
        $field = Field::findOrFail(post('field_id'));

        $fieldBefore = $field->form->fields()
            ->where('id', '<>', $field->id)
            ->where('sort_order', '<', $field->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if (empty($fieldBefore)) {
            return; // already first
        }

        $this->swapFieldOrder($field, $fieldBefore);

        Flash::success(trans('abwebdevelopers.forms::lang.models.all.sort_order.successful_up'));

        return $this->refreshFieldsRelation($field->form);
    }

    public function onMoveFieldDown()
    {
        $field = Field::findOrFail(post('field_id'));

        $fieldAfter = $field->form->fields()
            ->where('id', '<>', $field->id)
            ->where('sort_order', '>', $field->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if (empty($fieldAfter)) {
            return; // already last
        }

        $this->swapFieldOrder($field, $fieldAfter);

        Flash::success(trans('abwebdevelopers.forms::lang.models.all.sort_order.successful_down'));

        return $this->refreshFieldsRelation($field->form);
    }

    private function refreshFieldsRelation(\ABWebDevelopers\Forms\Models\Form $model)
    {
        $this->initForm($model);
        $this->initRelation($model, 'fields');

        return $this->relationRefresh('fields');
    }

    private function swapFieldOrder(Field $current, Field $other)
    {
        $current->setSortableOrder(
            [$current->id, $other->id],
            [$other->sort_order, $current->sort_order]
        );
    }
}
