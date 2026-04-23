<?php
declare (strict_types = 1);

namespace app\model;

class PointsLog extends BaseModel
{
    protected $name = 'points_logs';
    protected $updateTime = false;
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
