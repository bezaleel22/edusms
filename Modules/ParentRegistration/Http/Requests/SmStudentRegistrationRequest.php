<?php

namespace Modules\ParentRegistration\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ParentRegistration\Entities\SmRegistrationSetting;
use Modules\ParentRegistration\Entities\SmStudentField;

class SmStudentRegistrationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $school = app('school');
        $reg_setting = SmRegistrationSetting::where('school_id', $school->id)->first();
        $maxFileSize =generalSetting()->file_size*1024;
        $fields = SmStudentField::where('school_id', $school->id)->where('is_required', 1)->get()->pluck('field_name')->toArray();
        $rules= [
            'class' => in_array('class', $fields) ? 'required' : 'nullable',
            'academic_year' => in_array('session', $fields) ? 'required' : 'nullable',
            'first_name' => in_array('first_name', $fields) ? 'required' : 'nullable',
            'last_name' => in_array('last_name', $fields) ? 'required' : 'nullable',
            'gender' => "sometimes|nullable",
            'date_of_birth' => in_array('date_of_birth', $fields) ? 'required' : 'nullable',
            'relationButton' => in_array('relation', $fields) ? 'required' : 'nullable',
            'guardian_email' => (in_array('guardians_email', $fields) ? 'required' : 'nullable').'|different:student_email',
            'guardian_mobile' => ['nullable', 'different:phone_number'],
            'phone_number'=> in_array('phone_number', $fields) ? 'required' : 'nullable',
            'section' => 'sometimes',
            'student_email' => 'sometimes|email|nullable',
            'blood_group'=>'sometimes|nullable',
            'religion'=>'sometimes|nullable',
            'caste'=>'sometimes',
            'student_category_id'=>'sometimes|nullable',
            'student_group_id' => 'sometimes|nullable',
            'height'=>'sometimes',
            'weight'=>'sometimes',
            'photo' => "sometimes|nullable|mimes:jpg,jpeg,png|max:".$maxFileSize,
            'fathers_name'=>'sometimes|max:100',
            'fathers_occupation'=>'sometimes|max:100',
            'fathers_phone'=>'sometimes|max:100',
            'fathers_photo'=>'sometimes|nullable|mimes:jpg,jpeg,png|max:'.$maxFileSize,
            'mothers_name'=>'sometimes|max:100',
            'mothers_occupation'=>'sometimes|max:100',
            'mothers_phone'=>'sometimes|max:100',
            'mothers_photo'=>'sometimes|nullable|mimes:jpg,jpeg,png',
            'guardian_name' =>'sometimes|max:100',
            'guardians_photo' => 'sometimes|nullable|mimes:jpg,jpeg,png|max:'.$maxFileSize,
            'guardians_occupation'=>'sometimes|max:100',
            'guardians_address' => 'sometimes|max:200',
            'current_address' => 'sometimes|max:200',
            'permanent_address' => 'sometimes|max:200',
            'route'=>'sometimes|nullable',
            'vehicle' =>'sometimes|nullable',
            'dormitory_name'=>'sometimes|nullable',
            'room_number' =>'sometimes|nullable',
            'national_id_number'=>'sometimes',
            'local_id_number'=>'sometimes',
            'bank_account_number'=>'sometimes',
            'bank_name'=>'sometimes',
            'previous_school_details'=>'sometimes',
            'additional_notes'=>'sometimes',
            'ifsc_code'=>'sometimes',
            'document_file_1' => "sometimes|nullable|mimes:pdf,doc,docx,jpg,jpeg,png,txt|max:".$maxFileSize,
            'document_file_2' => "sometimes|nullable|mimes:pdf,doc,docx,jpg,jpeg,png,txt|max:".$maxFileSize,
            'document_file_3' => "sometimes|nullable|mimes:pdf,doc,docx,jpg,jpeg,png,txt|max:".$maxFileSize,
            'document_file_4' => "sometimes|nullable|mimes:pdf,doc,docx,jpg,jpeg,png,txt|max:".$maxFileSize,

        ];


        if($reg_setting->recaptcha == 1){
            config(['captcha.secret' => $reg_setting->nocaptcha_secret, 'captcha.sitekey' => $reg_setting->nocaptcha_sitekey]);
            $rules +=[
                'g-recaptcha-response' => 'sometimes|required|captcha',
            ];
        }

        return $rules;


    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
