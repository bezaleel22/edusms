<?php

namespace App\Console\Commands;

use App\Models\StudentRecord;
use App\SmAssignClassTeacher;
use App\SmAssignSubject;
use App\SmClass;
use App\SmClassTeacher;
use App\SmDesignation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\SmStudent;
use App\SmExamType;
use App\SmHumanDepartment;
use App\SmParent;
use App\SmSection;
use App\SmStaff;
use App\SmStudentCategory;
use App\SmStudentTimeline;
use App\SmSubject;
use App\User;
use Illuminate\Support\Facades\DB;

class MigrateStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate student data to the latest version of the app using API requests';

    /**
     * Path to the JSON file where students will be saved.
     * 
     * @var string
     */
    protected $jsonFilePath = 'school_data.json';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // $staffs = SmStaff::whereNotIn('role_id', [1, 2, 3])
        //     ->where('sm_staffs.is_saas', 0)  // Add table prefix to is_saas
        //     ->where('sm_staffs.active_status', 1)
        //     ->with('staff_user', 'roles', 'subjects', 'departments', 'designations', 'genders')
        //     ->where('sm_staffs.school_id', 1)
        //     ->join('sm_class_teachers', 'sm_class_teachers.teacher_id', '=', 'sm_staffs.id')
        //     ->select('sm_staffs.*', 'sm_class_teachers.assign_class_teacher_id')
        //     ->orderBy('id', 'ASC')
        //     ->get();

        // $assign_teacher = SmAssignClassTeacher::where('sm_assign_class_teachers.academic_id', getAcademicId())
        //     ->where('sm_assign_class_teachers.school_id', 1)
        //     ->join('sm_sections', 'sm_sections.id', '=', 'sm_assign_class_teachers.section_id')
        //     ->join('sm_classes', 'sm_classes.id', '=', 'sm_assign_class_teachers.class_id')
        //     ->leftJoin('sm_class_teachers', 'sm_class_teachers.assign_class_teacher_id', '=', 'sm_assign_class_teachers.id')
        //     ->select('sm_assign_class_teachers.*', 'sm_classes.class_name', 'sm_sections.section_name', 'sm_class_teachers.assign_class_teacher_id', 'sm_class_teachers.teacher_id')
        //     ->orderBy('id', 'ASC')
        //     ->get();

        // Storage::put($this->jsonFilePath, json_encode($staffs, JSON_PRETTY_PRINT));
        // return;

        // Fetch all students from the current database
        // $students = SmStudent::where('sm_students.active_status', 1)
        //     ->join('student_records', 'student_records.student_id', '=', 'sm_students.id')
        //     ->join('sm_classes', 'sm_classes.id', '=', 'student_records.class_id')
        //     ->join('sm_sections', 'sm_sections.id', '=', 'student_records.section_id')
        //     ->select('student_records.is_default', 'student_records.is_promote', 'student_records.roll_no', 'sm_students.id', 'sm_students.first_name', 'sm_students.last_name', 'sm_students.full_name', 'sm_students.student_photo', 'sm_students.admission_no', 'sm_students.custom_field', 'sm_students.parent_id', 'sm_students.student_category_id', 'student_records.class_id', 'student_records.section_id', 'student_records.school_id', 'sm_classes.class_name', 'sm_sections.section_name', 'student_records.academic_id', 'student_records.id As record_id', 'sm_students.gender_id', 'sm_students.date_of_birth', 'sm_students.email', 'sm_students.mobile', 'sm_students.student_photo', 'sm_students.student_group_id', 'sm_students.admission_date', 'sm_students.bloodgroup_id', 'sm_students.religion_id', 'sm_students.current_address', 'sm_students.permanent_address', 'sm_students.active_status')
        //     ->with('school', 'academic', 'category')
        //     ->where('student_records.academic_id', getAcademicId())
        //     ->with(array('timeline' => function ($query) {
        //         $query->where('type', 'exam');
        //     }))
        //     ->with(array('parents' => function ($query) {
        //         $query->select('id', 'fathers_name', 'fathers_mobile', 'mothers_name', 'mothers_mobile', 'guardians_email', 'guardians_mobile', 'guardians_name', 'is_guardian', 'relation', 'guardians_relation');
        //     }))
        //     ->orderBy('id', 'ASC')
        //     ->get();

        $academicId = getAcademicId(); // Store the academic ID for reuse

        // Fetching data with consistent ordering
        $classes = SmClass::where('active_status', 1)
            ->where('academic_id', $academicId)
            ->orderBy('id')
            ->get(['class_name']);

        $sections = SmSection::where('active_status', 1)
            ->where('academic_id', $academicId)
            ->orderBy('id')
            ->get(['section_name']);

        $subjects = SmSubject::where('active_status', 1)
            ->where('academic_id', $academicId)
            ->orderBy('id')
            ->get(['id','subject_name', 'subject_code', 'subject_type']);

        $departments = SmHumanDepartment::where('active_status', 1)
            ->orderBy('id')
            ->get(['id','name']);

        $designations = SmDesignation::where('active_status', 1)
            ->orderBy('id')
            ->get(['id','title']);

        $categories = SmStudentCategory::where('school_id', 1)
            ->get(['category_name']);

        $exam_types = SmExamType::where('school_id', 1)
            ->where('academic_id', $academicId)
            ->orderBy('id')
            ->get(['id', 'title']);

        $staff_data = SmStaff::where('active_status', 1)
            ->whereNotIn('role_id', [1, 2, 3])
            ->where('is_saas', 0)
            ->with(['designations', 'departments', 'staff_user'])
            ->orderBy('id')
            ->get();

        $assign_subjects = SmAssignSubject::where('active_status', 1)
            ->with(['subject', 'class', 'section', 'teacher'])
            ->where('academic_id', $academicId)
            ->whereIn('teacher_id', $staff_data->pluck('id'))
            ->orderBy('id')
            ->get();

        $class_teachers = SmClassTeacher::where('sm_class_teachers.active_status', 1)
            ->where('sm_class_teachers.academic_id', $academicId)
            ->join('sm_assign_class_teachers', 'sm_assign_class_teachers.id', '=', 'sm_class_teachers.assign_class_teacher_id')
            ->join('sm_classes', 'sm_classes.id', '=', 'sm_assign_class_teachers.class_id')
            ->join('sm_sections', 'sm_sections.id', '=', 'sm_assign_class_teachers.section_id') // Assuming `section_id` is correct
            ->join('sm_staffs', 'sm_staffs.id', '=', 'sm_class_teachers.teacher_id')
            ->select('sm_class_teachers.*', 'sm_classes.class_name', 'sm_sections.section_name', 'sm_staffs.staff_no')
            ->orderBy('sm_class_teachers.id')
            ->get();

        $students = SmStudent::where('active_status', 1)
            ->with(['gender', 'category', 'user', 'parents'])
            ->orderBy('id')
            ->get();

        $student_records = StudentRecord::with(['student', 'class', 'section'])
            ->whereIn('student_id', $students->pluck('id'))
            ->where('is_default', 1)
            ->orderBy('id')
            ->get();

        $parents = SmParent::where('active_status', 1)
            ->whereIn('id', $students->pluck('parent_id'))
            ->with(['parent_user'])
            ->orderBy('id')
            ->get();

        $user_ids = $students->pluck('user_id')
            ->merge($parents->pluck('user_id'))
            ->merge($staff_data->pluck('user_id'))
            ->flatten()
            ->unique();

        $users = User::where('school_id', 1)
            ->whereIn('id', $user_ids)
            ->orderBy('id')
            ->get();

        $timelines = SmStudentTimeline::where('type', 'exam')
            ->where('academic_id', $academicId)
            ->whereIn('staff_student_id', $students->pluck('id'))
            ->orderBy('id')
            ->get();

        Storage::put($this->jsonFilePath, json_encode([
            'classes' => $classes,
            'sections' => $sections,
            'subjects' => $subjects,
            'departments' => $departments,
            'designations' => $designations,
            'categories' => $categories,
            'exam_types' => $exam_types,
            'users' => $users,
            'parents' => $parents,
            'students' => $students,
            'class_teachers' => $class_teachers,
            'assign_subjects' => $assign_subjects,
            'staff_data' => $staff_data,
            'student_records' => $student_records,
            'timelines' => $timelines, // Corrected spelling
        ], JSON_PRETTY_PRINT));

        $this->info('Export completed.');

        return Command::SUCCESS;
    }
}
