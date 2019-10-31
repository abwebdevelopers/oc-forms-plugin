<?php namespace ABWebDevelopers\Forms\Updates;
use Schema;
use October\Rain\Database\Updates\Migration;
use ABWebDevelopers\Forms\Models\Form;
use ABWebDevelopers\Forms\Models\Field;
class AddPagesSupport extends Migration
{
    public function up()
    {
        Schema::create('abwebdevelopers_forms_pages', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('tab_name', 191);
            $table->integer('form_id')->unsigned();
            $table->string('description', 191)->nullable()->default(null);
            $table->boolean('show_description')->default(false);
            $table->integer('sort_order')->unsigned()->default(1);
            $table->string('row_class')->nullable()->default(null);
            $table->string('group_class')->nullable()->default(null);
            $table->string('label_class')->nullable()->default(null);
            $table->string('field_class')->nullable()->default(null);
            // Add timestamps
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::table('abwebdevelopers_forms_fields', function ($table) {
            $table->text('description')->nullable()->default(null)->change();
            $table->integer('page_id')->nullable()->default(0);
        });
        Schema::table('abwebdevelopers_forms_pages', function ($table) {
            // Add indexes
            $table->index('form_id');
            // Add foreign keys
            $table->foreign('form_id')->references('id')->on('abwebdevelopers_forms_forms')->onDelete('cascade');
        });
    }
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('abwebdevelopers_forms_pages');
        Schema::enableForeignKeyConstraints();
    }
}