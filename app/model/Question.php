<?php
declare (strict_types = 1);

namespace app\model;

class Question extends BaseModel
{
    protected $name = 'questions';
    
    public function wrongQuestion()
    {
        return $this->belongsTo(WrongQuestion::class, 'wrong_question_id', 'id');
    }
    
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }
    
    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id', 'id');
    }
    
    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class, 'question_id', 'id');
    }
}
