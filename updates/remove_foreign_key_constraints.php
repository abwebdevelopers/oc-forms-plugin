<?php

namespace ABWebDevelopers\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class RemoveForeignKeyConstraints extends Migration
{
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        try {
            Schema::table('abwebdevelopers_forms_forms', function ($table) {
                // Add foreign keys
                $table->dropForeign(['auto_reply_name_field_id']);
                $table->dropForeign(['auto_reply_email_field_id']);
            });

            Schema::table('abwebdevelopers_forms_fields', function ($table) {
                // Add foreign keys
                $table->dropForeign(['form_id']);
            });

            Schema::table('abwebdevelopers_forms_submissions', function ($table) {
                // Add foreign keys
                $table->dropForeign(['form_id']);
            });
        } catch (\Exception $e) {
            // FKs have been removed from historic migrations, so the FKs may or may not exist at this point
        }
    }

    public function down()
    {
        // We don't want to regenerate foreign keys if reversed.
    }
}
