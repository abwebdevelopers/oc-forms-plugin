<?php namespace ABWebDevelopers\Forms\Models;

use Model;
use ABWebDevelopers\Forms\Models\Form;

class Submission extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'abwebdevelopers_forms_submissions';

    /**
     * @var array JSONable fields
     */
    public $jsonable = [
        'data'
    ];

    /**
     * @var array Whitelist of fields allowing mass assignment
     */
    public $fillable = [
        'url',
        'data',
        'ip',
        'form_id',
    ];

    /**
     * @var array Belongs to relations
     */
    public $belongsTo = [
        'form' => Form::class
    ];
}
