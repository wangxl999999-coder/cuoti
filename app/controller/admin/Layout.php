<?php
namespace app\controller\admin;

use think\facade\View;
use think\facade\Session;
use think\facade\Db;
use app\model\User;
use app\model\WrongQuestion;
use app\model\ExamRecord;
use app\model\PointsLog;
use app\model\Question;
use app\model\Grade;
use app\model\Subject;
use app\model\Admin;

class Layout extends BaseAdminController
{
    public function index()
    {
        $adminInfo = Session::get('admin_info');
        View::assign('adminInfo', $adminInfo);
        return View::fetch('admin/layout');
    }
}
