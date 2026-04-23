<?php
namespace app\controller\admin;

use think\facade\View;
use think\facade\Db;
use app\model\User;
use app\model\Grade;
use app\model\PointsLog;

class UserController extends BaseAdminController
{
    public function index()
    {
        $grades = Grade::select();
        View::assign('grades', $grades);
        return View::fetch();
    }

    public function list()
    {
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 10);
        $keyword = request()->get('keyword', '');
        $gradeId = request()->get('grade_id', 0);
        $status = request()->get('status', -1);

        $query = User::with('grade');
        
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('phone', 'like', "%{$keyword}%")
                  ->whereOr('nickname', 'like', "%{$keyword}%");
            });
        }

        if ($gradeId > 0) {
            $query->where('grade_id', $gradeId);
        }

        if ($status >= 0) {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['grade_name'] = $item['grade'] ? $item['grade']['name'] : '未设置';
            $item['status_text'] = $item['status'] == 1 ? '正常' : '禁用';
            unset($item['grade']);
        }

        return $this->layuiTable($list, $total);
    }

    public function detail()
    {
        $id = request()->get('id', 0);
        $user = User::with('grade')->find($id);
        
        if (!$user) {
            return $this->error('用户不存在');
        }

        $pointsLogs = PointsLog::where('user_id', $id)
            ->order('id', 'desc')
            ->limit(10)
            ->select();

        View::assign('user', $user);
        View::assign('pointsLogs', $pointsLogs);
        View::assign('gradeName', $user->grade ? $user->grade->name : '未设置');

        return View::fetch();
    }

    public function updateStatus()
    {
        $id = request()->post('id', 0);
        $status = request()->post('status', 0);

        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $user->status = $status;
        $user->save();

        return $this->success();
    }

    public function resetPassword()
    {
        $id = request()->post('id', 0);
        $password = request()->post('password', '123456');

        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return $this->success();
    }
}
