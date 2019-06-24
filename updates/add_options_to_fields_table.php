<?php namespace ABWebDevelopers\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;

class AddOptionsToFieldsTable extends Migration
{
    public function up()
    {
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->text('options')->nullable()->default(null);
        });
    }

    public function down()
    {
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->dropColumn('options');
        });
    }
}
