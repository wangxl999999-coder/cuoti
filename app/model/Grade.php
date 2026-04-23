<?php
declare (strict_types = 1);

namespace app\model;

class Grade extends BaseModel
{
    protected $name = 'grades';
    
    public function users()
    {
        return $this->hasMany(User::class, 'grade_id', 'id');
    }
    
    public function wrongQuestions()
    {
        return $this->hasMany(WrongQuestion::class, 'grade_id', 'id');
    }
    
    public function questions()
    {
        return $this->hasMany(Question::class, 'grade_id', 'id');
    }
}
