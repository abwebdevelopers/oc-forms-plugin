<?php namespace ABWebDevelopers\Forms\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;

class InitialiseDatabase extends Migration
{
    public function up()
    {
        Schema::create('abwebdevelopers_forms_forms', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('title', 191)->nullable();
            $table->string('code', 80);
            $table->string('description', 191)->default('');

            // Caching
            $table->boolean('enable_caching')->nullable()->default(null);
            $table->integer('cache_lifetime')->unsigned()->nullable()->default(null);

            // Action
            $table->string('on_success')->nullable()->default(null);
            $table->string('on_success_message')->nullable()->default(null);
            $table->string('on_success_redirect')->nullable()->default(null);

            // Styling
            $table->string('form_class')->nullable()->default(null);
            $table->string('field_class')->nullable()->default(null);
            $table->string('row_class')->nullable()->default(null);
            $table->string('group_class')->nullable()->default(null);
            $table->string('label_class')->nullable()->default(null);
            $table->string('submit_class')->nullable()->default(null);
            $table->string('submit_text')->nullable()->default(null);
            // ---
            $table->boolean('enable_cancel')->nullable()->default(null);
            $table->string('cancel_class')->nullable()->default(null);
            $table->string('cancel_text')->nullable()->default(null);

            // Anti-spam
            $table->boolean('enable_recaptcha')->nullable()->default(null);
            $table->boolean('enable_ip_restriction')->nullable()->default(null);
            $table->integer('max_requests_per_day')->nullable()->default(null);
            $table->string('throttle_message')->nullable()->default(null);

            // Privacy
            $table->boolean('saves_data')->nullable()->default(null);

            // Emailing
            $table->boolean('auto_reply')->nullable()->default(null);
            $table->integer('auto_reply_name_field_id')->unsigned()->nullable()->default(null);
            $table->integer('auto_reply_email_field_id')->unsigned()->nullable()->default(null);
            $table->string('auto_reply_template')->nullable()->default(null);
            // ---
            $table->boolean('send_notifications')->nullable()->default(null);
            $table->string('notification_template')->nullable()->default(null);
            $table->string('notification_recipients')->nullable()->default(null);

            // Add timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('abwebdevelopers_forms_fields', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('form_id')->unsigned();
            $table->string('name', 191);
            $table->string('code', 80);
            $table->string('type', 191);
            $table->string('description', 191)->nullable()->default(null);
            $table->integer('sort_order')->unsigned()->default(1);
            $table->boolean('required')->default(0);
            $table->string('placeholder', 191)->nullable()->default(null);
            $table->string('validation_rules')->nullable()->default(null);
            $table->string('validation_message')->nullable()->default(null);
            $table->string('row_class')->nullable()->default(null);
            $table->string('group_class')->nullable()->default(null);
            $table->string('label_class')->nullable()->default(null);
            $table->string('field_class')->nullable()->default(null);

            // Add timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('abwebdevelopers_forms_submissions', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('form_id')->unsigned();
            $table->string('ip', 40)->nullable();
            $table->string('url', 191);
            $table->text('data');

            // Add timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::table('abwebdevelopers_forms_forms', function ($table) {
            // Add indexes
            $table->index('auto_reply_name_field_id');
            $table->index('auto_reply_email_field_id');

            // Add foreign keys
            $table->foreign('auto_reply_name_field_id')->references('id')->on('abwebdevelopers_forms_fields')->onDelete('cascade');
            $table->foreign('auto_reply_email_field_id')->references('id')->on('abwebdevelopers_forms_fields')->onDelete('cascade');
        });

        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            // Add indexes
            $table->index('form_id');

            // Add foreign keys
            $table->foreign('form_id')->references('id')->on('abwebdevelopers_forms_forms')->onDelete('cascade');
        });

        Schema::table('abwebdevelopers_forms_submissions', function ($table) {
            // Add indexes
            $table->index('form_id');

            // Add foreign keys
            $table->foreign('form_id')->references('id')->on('abwebdevelopers_forms_forms')->onDelete('cascade');
        });

        // Bootstrap a basic contact form with 3 typical fields
        $form = Form::create([
            'title' => 'Contact Form',
            'code' => 'contact_form',
            'description' => 'Blah',
            'auto_reply' => true
        ]);
        $name = Field::create([
            'form_id' => $form->id,
            'name' => 'Name',
            'type' => 'text',
            'code' => 'name',
            'description' => 'Full Name',
            'sort_order' => 1
        ]);
        $email = Field::create([
            'form_id' => $form->id,
            'name' => 'Email',
            'type' => 'email',
            'code' => 'email',
            'description' => 'Email Address',
            'sort_order' => 2
        ]);
        $comment = Field::create([
            'form_id' => $form->id,
            'name' => 'Comment',
            'type' => 'textarea',
            'code' => 'comment',
            'description' => 'User\'s Comments',
            'sort_order' => 3
        ]);
        
        $form->auto_reply_email_field_id = $email->id;
        $form->auto_reply_name_field_id = $name->id;
        $form->save();
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('abwebdevelopers_forms_forms');
        Schema::dropIfExists('abwebdevelopers_forms_fields');
        Schema::dropIfExists('abwebdevelopers_forms_submissions');

        Schema::enableForeignKeyConstraints();
    }
}
