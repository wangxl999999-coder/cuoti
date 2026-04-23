<?php
declare (strict_types = 1);

namespace app\model;

class Subject extends BaseModel
{
    protected $name = 'subjects';
    
    public function wrongQuestions()
    {
        return $this->hasMany(WrongQuestion::class, 'subject_id', 'id');
    }
    
    public function questions()
    {
        return $this->hasMany(Question::class, 'subject_id', 'id');
    }
}
