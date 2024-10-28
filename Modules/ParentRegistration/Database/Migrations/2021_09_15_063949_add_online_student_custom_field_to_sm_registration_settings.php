<?php

use App\SmGeneralSettings;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\ParentRegistration\Entities\SmRegistrationSetting;

class AddOnlineStudentCustomFieldToSmRegistrationSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config = SmGeneralSettings::find(1);
        if (@$config->ParentRegistration == 1) {
        Schema::table('sm_student_registrations', function (Blueprint $table) {
            $table->string('caste', 200)->nullable();
            $table->string('id_number', 200)->nullable();
            $table->string('student_photo')->nullable();
            $table->string('fathers_name', 200)->nullable();
            $table->string('fathers_mobile', 200)->nullable();
            $table->string('fathers_occupation', 200)->nullable();
            $table->string('fathers_photo', 200)->nullable();
            $table->string('mothers_name', 200)->nullable();
            $table->string('mothers_mobile', 200)->nullable();
            $table->string('mothers_occupation', 200)->nullable();
            $table->string('mothers_photo', 200)->nullable();
            $table->string('relation', 200)->nullable();
            $table->string('guardians_occupation', 200)->nullable();
           
            $table->string('guardians_photo', 200)->nullable();
            $table->string('guardians_address', 200)->nullable();
            
            $table->string('height', 200)->nullable();
            $table->string('weight', 200)->nullable();
            $table->text('current_address', 500)->nullable();
            $table->text('permanent_address', 500)->nullable();

            $table->string('driver_id', 200)->nullable();
            $table->string('national_id_no', 200)->nullable();
            $table->string('local_id_no', 200)->nullable();
            $table->string('bank_account_no', 200)->nullable();
            $table->string('bank_name', 200)->nullable();
            $table->string('previous_school_details', 500)->nullable();
            $table->string('aditional_notes', 500)->nullable();
            $table->string('ifsc_code', 50)->nullable();
            $table->string('document_title_1', 200)->nullable();
            $table->string('document_file_1', 200)->nullable();
            $table->string('document_title_2', 200)->nullable();
            $table->string('document_file_2', 200)->nullable();
            $table->string('document_title_3', 200)->nullable();
            $table->string('document_file_3', 200)->nullable();
            $table->string('document_title_4', 200)->nullable();
            $table->string('document_file_4', 200)->nullable();


            $table->integer('bloodgroup_id')->nullable()->unsigned();
            $table->foreign('bloodgroup_id')->references('id')->on('sm_base_setups')->onDelete('cascade');

            $table->integer('religion_id')->nullable()->unsigned();
            $table->foreign('religion_id')->references('id')->on('sm_base_setups')->onDelete('cascade');

            $table->integer('route_list_id')->nullable()->unsigned();
            $table->foreign('route_list_id')->references('id')->on('sm_routes')->onDelete('cascade');

            $table->integer('dormitory_id')->nullable()->unsigned();
            $table->foreign('dormitory_id')->references('id')->on('sm_dormitory_lists')->onDelete('cascade');

            $table->integer('vechile_id')->nullable()->unsigned();
            $table->foreign('vechile_id')->references('id')->on('sm_vehicles')->onDelete('cascade');

            $table->integer('room_id')->nullable()->unsigned();
            $table->foreign('room_id')->references('id')->on('sm_room_lists')->onDelete('cascade');

            $table->integer('student_category_id')->nullable()->unsigned();
            $table->foreign('student_category_id')->references('id')->on('sm_student_categories')->onDelete('cascade');

            $table->integer('student_group_id')->nullable()->unsigned();
            $table->foreign('student_group_id')->references('id')->on('sm_student_groups')->onDelete('cascade');
            
            $table->text('custom_field')->nullable();
            $table->string('custom_field_form_name')->nullable();
        });
  
        Schema::table('sm_registration_settings', function (Blueprint $table) {
                $table->tinyInteger('footer_note_status');
                $table->text('footer_note_text');
                $table->timestamp('start_date')->nullable()->after('nocaptcha_secret');
                $table->text('before_start_msg')->nullable()->after('start_date');
                $table->timestamp('end_date')->nullable()->after('before_start_msg');
                $table->text('after_end_msg')->nullable()->after('end_date');
                $table->string('url', 191)->default('registration')->after('after_end_msg');
        });

        Schema::table('sm_custom_fields', function (Blueprint $table) {
            $table->tinyInteger('is_showing')->default(0);
         });


         $setting = SmRegistrationSetting::first();
         $setting->footer_note_status = 1;
         $setting->footer_note_text = "If you want to register your another child please contact with school.";
         $setting->before_start_msg = "Registration start on {START_DATE} and end on {END_DATE}";
         $setting->after_end_msg = "Registration date is over. Thank you for your query.";
         $setting->save();

         Schema::table('sm_registration_settings', function (Blueprint $table) {
            
        });
     }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

       
        Schema::table('sm_student_registrations', function (Blueprint $table) {
            $table->dropForeign(['bloodgroup_id']);
            $table->dropForeign(['religion_id']);
            $table->dropForeign(['route_list_id']);
            $table->dropForeign(['dormitory_id']);
            $table->dropForeign(['vechile_id']);
            $table->dropForeign(['room_id']);
            $table->dropForeign(['student_category_id']);
            $table->dropForeign(['student_group_id']);

            $registration_field=[
                'caste','student_photo','fathers_name',
                'fathers_mobile',
                'fathers_occupation',
                'fathers_photo',
                'mothers_name',
                'mothers_mobile',
                'mothers_occupation',
                'mothers_photo',
                'relation',
                'guardians_occupation',
                'guardians_photo',
                'guardians_address',
                'height',
                'weight',
                'current_address',
                'permanent_address',
                'driver_id',
                'national_id_no',
                'local_id_no',
                'bank_account_no',
                'bank_name',
                'previous_school_details',
                'aditional_notes',
                'ifsc_code',
                'document_title_1',
                'document_file_1', 
                'document_title_2',
                'document_file_2',
                'document_title_3',
                'document_file_3',
                'document_title_4',
                'document_file_4',
                'bloodgroup_id',
                'religion_id',
                'route_list_id',
                'dormitory_id',
                'vechile_id',
                'room_id',
                'student_category_id',
                'student_group_id',
                'custom_field',
                'custom_field_form_name'

            ];
            $table->dropColumn($registration_field);
            
        });

        Schema::table('sm_registration_settings', function (Blueprint $table) {
            $table->dropColumn(['footer_note_status', 'start_date', 'before_start_msg', 'end_date', 'after_end_msg', 'url', 'footer_note_text']);
        });

        Schema::table('sm_custom_fields', function (Blueprint $table) {
            $table->dropColumn('is_showing');
        });
        
    }
}
