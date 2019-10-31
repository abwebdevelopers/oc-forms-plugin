<?php namespace ABWebDevelopers\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;

class AddDefaultShowDescriptionAndAttributesToFieldsTable extends Migration
{
    public function up()
    {
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->boolean('show_description')->default(false);
            $table->text('default')->nullable()->default(null);
            $table->text('html_attributes')->nullable()->default(null);
        });
    }

    public function down()
    {
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->dropColumn(['show_description', 'default', 'html_attributes']);
        });
    }
}
