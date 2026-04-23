<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

class BaseModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
    
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
