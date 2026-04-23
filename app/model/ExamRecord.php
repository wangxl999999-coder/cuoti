<?php
declare (strict_types = 1);

namespace app\model;

class ExamRecord extends BaseModel
{
    protected $name = 'exam_records';
    protected $updateTime = false;
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id', 'id');
    }
    
    public function wrongQuestion()
    {
        return $this->belongsTo(WrongQuestion::class, 'wrong_question_id', 'id');
    }
}
