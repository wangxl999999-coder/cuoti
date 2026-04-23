<?php
declare (strict_types = 1);

namespace app\model;

class QuestionCollection extends BaseModel
{
    protected $name = 'question_collections';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function wrongQuestions()
    {
        return $this->belongsToMany(WrongQuestion::class, 'collection_questions', 'wrong_question_id', 'collection_id');
    }
}
