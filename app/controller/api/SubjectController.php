<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\model\Subject;

class SubjectController extends BaseApiController
{
    public function list()
    {
        $list = Subject::where('status', 1)
            ->order('sort_order', 'asc')
            ->field('id, name, icon')
            ->select()
            ->toArray();
        
        return $this->success($list);
    }
}
