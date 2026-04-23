<?php
declare (strict_types = 1);

namespace app\model;

class User extends BaseModel
{
    protected $name = 'users';
    
    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id', 'id');
    }
    
    public function pointsLogs()
    {
        return $this->hasMany(PointsLog::class, 'user_id', 'id');
    }
    
    public function wrongQuestions()
    {
        return $this->hasMany(WrongQuestion::class, 'user_id', 'id');
    }
    
    public function examRecords()
    {
        return $this->hasMany(ExamRecord::class, 'user_id', 'id');
    }
    
    public function collections()
    {
        return $this->hasMany(QuestionCollection::class, 'user_id', 'id');
    }
    
    public function checkins()
    {
        return $this->hasMany(Checkin::class, 'user_id', 'id');
    }
}
