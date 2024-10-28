<?php

namespace App;

use App\Models\StudentRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\StatusAcademicSchoolScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmSubject extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new StatusAcademicSchoolScope);
    }

    public function marks()
    {
        $record = $this->hasOne(StudentRecord::class, 'student_id')->where('academic_id', getAcademicId())->where('school_id', Auth::user()->school_id)->where('is_promote', 0)->first();
        return $this->hasMany(SmMarkStore::class, 'subject_id', 'id')
        ->where('exam_term_id', 4)
        ->groupBy('section_id');
    }

}
