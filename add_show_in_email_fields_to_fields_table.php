<?php namespace ABWebDevelopers\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddShowInEmailFieldsToFieldsTable extends Migration
{
    public function up()
    {
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->boolean('show_in_email_autoreply')->default(true);
            $table->boolean('show_in_email_notification')->default(true);
        });
    }

    public function down()
    {
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->dropColumn(['show_in_email_autoreply', 'show_in_email_notification']);
        });
    }
}
