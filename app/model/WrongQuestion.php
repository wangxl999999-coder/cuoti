<?php
declare (strict_types = 1);

namespace app\model;

class WrongQuestion extends BaseModel
{
    protected $name = 'wrong_questions';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }
    
    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id', 'id');
    }
    
    public function questions()
    {
        return $this->hasMany(Question::class, 'wrong_question_id', 'id');
    }
    
    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class, 'wrong_question_id', 'id');
    }
    
    public function collections()
    {
        return $this->belongsToMany(QuestionCollection::class, 'collection_questions', 'collection_id', 'wrong_question_id');
    }
}
