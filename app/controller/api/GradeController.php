<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Grade;

class GradeController extends BaseApiController
{
    public function list()
    {
        $list = Grade::where('status', 1)
            ->order('sort_order', 'asc')
            ->field('id, name')
            ->select()
            ->toArray();
        
        return $this->success($list);
    }
}
