<?php namespace ABWebDevelopers\Forms\Models;

use ABWebDevelopers\Forms\Models\Form;
use Model;
use Request;
use Backend;

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

    /**
     * Generate the backend URL link for viewing this submission
     */
    public function viewLink()
    {
        return Backend::url('abwebdevelopers/forms/submission', $this->id);
    }

    /**
     * Return the submissions from this IP in the last 24h for this form
     */
    public function scopeThrottleCheck($query, $formId)
    {
        return $query->where('form_id', $formId) // where form matches
                    ->where('ip', Request::ip()) // where IP matches
                    ->where('created_at', '>=', \Carbon\Carbon::now()->subDay()); // last 24h
    }

    /**
     * Render any given value of the submission
     *
     * @param string|array $value
     * @return string
     */
    public function renderValue($value): string
    {
        if (is_array($value)) {
            $value = implode("\n", $value);
        }

        return htmlspecialchars($value);
    }
}
