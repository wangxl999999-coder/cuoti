<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\View;
use think\facade\Session;
use app\model\Admin;
use app\model\User;
use app\model\WrongQuestion;
use app\model\ExamRecord;
use app\model\PointsLog;

class BaseAdminController extends BaseController
{
    protected $adminId = 0;
    protected $admin = null;

    protected function initialize()
    {
        parent::initialize();
        
        $adminId = Session::get('admin_id');
        if (!$adminId) {
            if (request()->isAjax()) {
                return json(['code' => 401, 'msg' => '请先登录']);
            }
            return redirect('/admin/login');
        }
        
        $this->adminId = $adminId;
        $this->admin = Admin::find($adminId);
        
        if (!$this->admin) {
            Session::delete('admin_id');
            return redirect('/admin/login');
        }
    }

    protected function success($data = null, $msg = '操作成功', $code = 0)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'count' => is_array($data) ? count($data) : 0
        ]);
    }

    protected function error($msg = '操作失败', $code = 1, $data = null)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    protected function layuiTable($list, $total)
    {
        return json([
            'code' => 0,
            'msg' => '',
            'data' => $list,
            'count' => $total
        ]);
    }
}
