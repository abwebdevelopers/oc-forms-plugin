<?php namespace ABWebDevelopers\Forms\Models;
use Model;
use ABWebDevelopers\Forms\Models\ValidationRule;
use October\Rain\Database\Traits\Validation;
use October\Rain\Database\Traits\Sortable;
use ABWebDevelopers\Forms\Models\Form;
class Page extends Model
{
    use Validation, Sortable;
    const SORT_ORDER = 'sort_order';
    /**
     * @var string The database table used by the model.
     */
    public $table = 'abwebdevelopers_forms_pages';
    /**
     * @var array Validation rules
     */
    public $rules = [
        'tab_name' => 'required|string|min:1|max:191',
    ];
    public $attachOne = [
        'form' => 'ABWebDevelopers\Forms\Models\Form'
    ];
    /**
     * @var array Whitelist of fields allowing mass assignment
     */
    public $fillable = [
        'name',
        'tab_name',
        'form_id',
        'description',
        'field_class',
        'row_class',
        'group_class',
        'label_class',
        'order',
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
}