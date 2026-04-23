<?php
declare (strict_types = 1);

namespace app\model;

class Checkin extends BaseModel
{
    protected $name = 'checkins';
    protected $updateTime = false;
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
