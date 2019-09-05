<?php namespace ABWebDevelopers\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;

class MigrateOptionsToNewRepeaterSystem extends Migration
{
    public function up()
    {
        foreach (Form::all() as $form) {
            foreach ($form->fields as $field) {
                if (is_string($field->options)) {
                    $newOptions = [];
                    foreach (explode(',', $field->options) as $part) {
                        $newOptions[$part] = $part;
                    }
                    $field->options = $newOptions;
                    $field->save();
                }
            }
        }
    }

    public function down()
    {
    }
}
