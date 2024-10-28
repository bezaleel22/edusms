<?php

namespace App\Http\Controllers\api;

use Validator;
use App\YearCheck;
use App\SmMarksGrade;
use App\SmResultStore;
use App\ApiBaseMethod;
use App\SmExamSetup;
use App\SmMarkStore;
use App\SmStudent;
use App\SmExamType;
use App\SmAssignClassTeacher;
use App\Models\StudentRecord;
use App\SmStudentTimeline;
use App\SmAssignSubject;
use App\SmGeneralSettings;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiSmMarksGradeController extends Controller
{
    public function __construct()
    {
        $this->middleware('PM');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            // Validate request parameters
            $request->validate([
                'id' => 'required|integer|exists:sm_students,id',
            ]);

            if ($request->title != '') {
                $title = urldecode($request->title);
                $exam_type = SmExamType::where('school_id', Auth::user()->school_id)
                    ->where('title', $title)
                    ->where('academic_id', getAcademicId())
                    ->select('id', 'academic_id', 'school_id')
                    ->first();

                if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                    return ApiBaseMethod::sendResponse($exam_type, null);
                }
            }

            // Get result data
            $result_data = SmStudent::getResultData($request->id);

            // Check if records exist
            if (empty($result_data)) {
                return ApiBaseMethod::sendError('No records found for the given student and exam.');
            }

            foreach ($result_data->records as $record) {
                $total_marks = $record->results->sum('total_marks');
                $record['avarage'] = floor($total_marks / $record->results->count());
            }

            $ids = $result_data->records->pluck('student_id', 'avarage');
            $min = $result_data->records->min('avarage');
            $min_average = (object)[
                'student_id' => $ids[$min] ?? null,
                'value' => $min,
            ];

            $max = $result_data->records->max('avarage');
            $max_average = (object)[
                'student_id' => $ids[$max] ?? null,
                'value' => $max,
            ];

            $student_data = $result_data->student_data ?? null;
            if (!$student_data) {
                return ApiBaseMethod::sendError('Student data not found.');
            }

            $academic = $student_data['academic'] ?? null;
            if (!$academic) {
                return ApiBaseMethod::sendError('Academic data not found.');
            }

            $custom_field = $student_data['custom_field'] ?? [];
            $school_data = $student_data['school'] ?? null;
            $result = $student_data['result'] ?? [];

            // Validate necessary fields in student data
            if (empty($student_data->full_name) || empty($school_data->school_name)) {
                return ApiBaseMethod::sendError('Incomplete student or school information.');
            }

            $student = (object) [
                'id' => $student_data->id,
                'full_name' => $student_data->full_name,
                'gender'=>(isset($student_data->gender) && $student_data->gender == 'F') ? 'Female' : 'Male',
                'term' => $this->removeDate($custom_field['exam_type'] ?? ''),
                'exam_type_id' => $student_data->exam_type_id,
                'type' => 'GRADERS',
                'class_name' => $student_data->class_name ?? 'N/A',
                'section_name' => $student_data->section_name ?? 'N/A',
                'admin_no' => $student_data->admission_no ?? 'N/A',
                'session_year' => $academic->title ?? 'N/A',
                'opened' => $custom_field['days_school_opened'] ?? 0,
                'absent' => $custom_field['days_absent'] ?? 0,
                'present' => $custom_field['days_present'] ?? 0,
                'student_photo' => $student_data->student_photo ?? '', // Placeholder for the photo path
            ];

            $address = $this->parseAddress($school_data->address ?? '');
            $school = (object) [
                'name' => $school_data->school_name ?? 'N/A',
                'city' => $address->city ?? 'N/A',
                'state' => $address->state ?? 'N/A',
                'title' => explode(' ', $custom_field['exam_type'] ?? '')[4] ?? 'N/A',
                'vacation_date' => 'December 25, 2024',
            ];

            $rateMapping = [
                '5' => ['remark' => 'Excellent', 'color' => 'range-success'],
                '4' => ['remark' => 'Good', 'color' => 'range-error'],
                '3' => ['remark' => 'Average', 'color' => 'range-info'],
                '2' => ['remark' => 'Below Average', 'color' => 'range-accent'],
                '1' => ['remark' => 'Poor', 'color' => 'range-warning'],
            ];

            $ratingData = array_filter($custom_field, function ($key) {
                return in_array($key, [
                    "adherent_and_independent",
                    "self_control_and_interaction",
                    "flexibility_and_creativity",
                    "meticulous",
                    "neatness",
                    "overall_progress"
                ]);
            }, ARRAY_FILTER_USE_KEY);

            $ratings = [];
            foreach ($ratingData as $key => $value) {
                if (isset($rateMapping[$value])) {
                    $mappedRate = $rateMapping[$value];
                    $ratings[] = (object) [
                        'attribute' => ucfirst(str_replace('_', ' ', $key)),
                        'rate' => $value / 5 * 100,
                        'color' => $mappedRate['color'],
                        'remark' => $mappedRate['remark'],
                    ];
                }
            }

            $records = [];
            $remark = '';
            $over_all = 0;

            foreach ($result as $subject_id => $marks_data) {
                if ($marks_data->isNotEmpty()) {
                    if ($subject_id == 20) {
                        $remark = $marks_data[0]->teacher_remarks ?? '';
                    }

                    $sum = $marks_data->sum('total_marks');
                    $over_all += $sum;
                    $marks = $marks_data->pluck('total_marks')->toArray();
                    $grade = $this->getGrade($sum, 'GRADERS');

                    $records[] = [
                        'subject' => $marks_data[0]->subject_name ?? 'N/A',
                        'marks' => $marks,
                        'total_score' => $sum,
                        'grade' => $grade->grade,
                        'color' => $grade->color
                    ];
                }
            }

            $score = (object) [
                'total' => $over_all,
                'average' => $records ? floor($over_all / count($records)) : 0,
                'min_average' => $min_average ?? null,
                'max_average' => $max_average ?? null,
            ];

            $remark = (object) [
                'name' => 'Teachers Remark',
                'comment' => $remark,
            ];

            $exam_type = SmExamType::where('school_id', Auth::user()->school_id)
                ->where('academic_id', getAcademicId())
                ->select('id', 'title', 'percentage')
                ->get();

            $data = [
                'exam_type' => $exam_type,
                'school' => $school,
                'student' => $student,
                'records' => $records,
                'score' => $score,
                'ratings' => $ratings,
                'remark' => $remark,
            ];

            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                return ApiBaseMethod::sendResponse($data, null);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching student results: ' . $e->getMessage(), [
                'request' => $request->all(),
                'stack' => $e->getTraceAsString(),
            ]);
            // return ApiBaseMethod::sendError($e->getMessage());
            return ApiBaseMethod::sendError('An error occurred while fetching results. Please try again later.');
        }
    }

    private function removeDate($string)
    {
        $pattern = '/\s*-\s*[A-Za-z]{3}\/\d{4}/';
        return preg_replace($pattern, '', $string);
    }

    public function getGrade($score, $arm)
    {
        $eyfs = [
            ['min' => 0, 'max' => 80, 'grade' => 'EMERGING', 'color' => 'bg-purple-200'],
            ['min' => 81, 'max' => 90, 'grade' => 'EXPECTED', 'color' => 'bg-blue-200'],
            ['min' => 91, 'max' => 100, 'grade' => 'EXCEEDING', 'color' => 'bg-red-200'],
        ];

        $graders = [
            ['min' => 0, 'max' => 69, 'grade' => 'E', 'color' => 'bg-red-200'],
            ['min' => 70, 'max' => 76, 'grade' => 'D', 'color' => 'bg-orange-200'],
            ['min' => 77, 'max' => 85, 'grade' => 'C', 'color' => 'bg-yellow-200'],
            ['min' => 86, 'max' => 93, 'grade' => 'B', 'color' => 'bg-blue-200'],
            ['min' => 94, 'max' => 100, 'grade' => 'A', 'color' => 'bg-purple-200'],
        ];

        $grades = $arm === "GRADERS" ? $graders : $eyfs;
        foreach ($grades as $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return (object) ['grade' => $range['grade'], 'color' => $range['color']];
            }
        }

        return ["Outstanding", "bg-red-200"];
    }

    private function parseAddress($address)
    {
        $addressComponents = [
            'street_number' => null,
            'street_name' => null,
            'city' => null,
            'state' => null,
        ];

        $parts = array_map('trim', explode(',', $address));
        $addressComponents['state'] = array_pop($parts);
        $addressComponents['city'] = array_pop($parts);

        $streetAddress = implode(', ', $parts);

        $regex = '/No\.\s*(\d+)\s*(.+)/i';
        $matches = [];

        if (preg_match($regex, $streetAddress, $matches)) {
            $addressComponents['street_number'] = $matches[1]; // First capture group (street number)
            $addressComponents['street_name'] = $matches[2];   // Second capture group (street name)
        } else {
            $addressComponents['street_name'] = $streetAddress;
        }

        return (object) $addressComponents;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        try {
            $student_detail = SmStudent::where('active_status', 1)->where('admission_no', $request->id)->first();
            $timelines = SmStudentTimeline::where('staff_student_id', $student_detail->id)
                ->where('type', 'stu')->where('academic_id', getAcademicId())
                ->where('school_id', Auth::user()->school_id)
                ->get();

            $student_data = SmStudent::getResultData($request->id);

            if (count($timelines) === 0) {
                $url = 'https://pdf.llacademy.ng';
                $student = SmStudent::getResultData($student_detail->admission_no);

                $data['data'] = $student_data;
                $response = Http::post($url, $data);
                if ($response->successful()) {
                    if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                        $msg['message'] = 'The Student Result is Successfully Sent for Processing, Go to Timeline to Download Or Refresh the Page to Check Again';
                        return ApiBaseMethod::sendResponse($msg, null);
                    }
                } else {
                    if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                        $msg['message'] = 'Student Result not yet Uploaded, Check Later';
                        return ApiBaseMethod::sendResponse($msg, null);
                    }
                }
            }
        } catch (\Exception $e) {
            return ApiBaseMethod::sendError('Error.', $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        try {

            if ($request->title != "") {
                $document_photo = "";
                $uplaoded = false;
                if ($request->file('document_file') != "") {
                    $maxFileSize = SmGeneralSettings::first('file_size')->file_size;
                    $file = $request->file('document_file');
                    $fileSize =  filesize($file);
                    // return ApiBaseMethod::sendResponse($maxFileSize, null);
                    $fileSizeKb = ($fileSize / 1000000);
                    if ($fileSizeKb >= $maxFileSize) {
                        if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                            return ApiBaseMethod::sendError('Error.', 'Max upload file size ' . $maxFileSize . ' Mb is set in system', 'Failed');
                        }
                    }
                    $file = $request->file('document_file');
                    $document_photo = 'stu-' . $request->student_id . md5($file->getClientOriginalName() . time()) . "." . $file->getClientOriginalExtension();
                    $file->move('public/uploads/student/timeline/', $document_photo);
                    // return ApiBaseMethod::sendResponse($data, null);
                    $document_photo =  'public/uploads/student/timeline/' . $document_photo;
                    $uplaoded = true;
                }

                if ($uplaoded === true) {
                    $timeline = new SmStudentTimeline();
                    $timeline->staff_student_id = $request->student_id;
                    $timeline->type = 'stu';
                    $timeline->title = $request->title;
                    $timeline->date = date('Y-m-d', strtotime($request->date));
                    $timeline->description = $request->description;

                    if (isset($request->visible_to_student)) {
                        $timeline->visible_to_student = $request->visible_to_student;
                    }
                    $timeline->file = $document_photo;
                    $timeline->school_id = Auth::user()->school_id;
                    $timeline->academic_id = getAcademicId();
                    $timeline->save();
                    if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                        $data['uploaded'] = $uplaoded;
                        return ApiBaseMethod::sendResponse($data, null);
                    }
                }
                // $uplaoded = $request->hasFile('document_file');
                if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                    $data['uploaded'] = $uplaoded;
                    return ApiBaseMethod::sendResponse($data, null);
                }
            }
        } catch (\Exception $e) {
            return ApiBaseMethod::sendError('Error.', $e->getMessage());
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $marks_grade = SmMarksGrade::find($id);
            $marks_grades = SmMarksGrade::where('academic_id', getAcademicId())->get();

            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                $data = [];
                $data['marks_grade'] = $marks_grade->toArray();
                $data['marks_grades'] = $marks_grades->toArray();
                return ApiBaseMethod::sendResponse($data, null);
            }
            return view('backEnd.examination.marks_grade', compact('marks_grade', 'marks_grades'));
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'grade_name' => "required|max:50",
            'gpa' => "required|max:4",
            'percent_from' => "required|integer||min:0",
            'percent_upto' => "required|integer|min:" . @$request->percent_from,
        ]);

        if ($validator->fails()) {
            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                return ApiBaseMethod::sendError('Validation Error.', $validator->errors());
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $marks_grade = SmMarksGrade::find($request->id);
            $marks_grade->grade_name = $request->grade_name;
            $marks_grade->gpa = $request->gpa;
            $marks_grade->percent_from = $request->percent_from;
            $marks_grade->percent_upto = $request->percent_upto;
            $marks_grade->description = $request->description;
            $result = $marks_grade->save();

            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                if ($result) {
                    return ApiBaseMethod::sendResponse(null, 'Grade has been updated successfully');
                } else {
                    return ApiBaseMethod::sendError('Something went wrong, please try again.');
                }
            } else {
                if ($result) {
                    Toastr::success('Operation successful', 'Success');
                    return redirect('marks-grade');
                } else {
                    Toastr::error('Operation Failed', 'Failed');
                    return redirect()->back();
                }
            }
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {

        try {
            $marks_grade = SmMarksGrade::destroy($id);

            if (ApiBaseMethod::checkUrl($request->fullUrl())) {
                if ($marks_grade) {
                    return ApiBaseMethod::sendResponse(null, 'Grdae has been deleted successfully');
                } else {
                    return ApiBaseMethod::sendError('Something went wrong, please try again.');
                }
            } else {
                if ($marks_grade) {
                    Toastr::success('Operation successful', 'Success');
                    return redirect('marks-grade');
                } else {
                    Toastr::error('Operation Failed', 'Failed');
                    return redirect()->back();
                }
            }
        } catch (\Exception $e) {
            Toastr::error('Operation Failed', 'Failed');
            return redirect()->back();
        }
    }
}
