<?php
namespace app\controller\admin;

use app\BaseController;
use think\facade\View;
use think\facade\Session;
use app\model\Admin;

class Login extends BaseController
{
    public function index()
    {
        $adminId = Session::get('admin_id');
        if ($adminId) {
            return redirect('/admin');
        }
        return View::fetch('admin/login/index');
    }

    public function login()
    {
        $username = request()->post('username', '');
        $password = request()->post('password', '');

        if (empty($username) || empty($password)) {
            return json(['code' => 1, 'msg' => '请输入用户名和密码']);
        }

        $admin = Admin::where('username', $username)->find();
        if (!$admin) {
            return json(['code' => 1, 'msg' => '用户名或密码错误']);
        }

        if (!password_verify($password, $admin->password)) {
            return json(['code' => 1, 'msg' => '用户名或密码错误']);
        }

        if ($admin->status != 1) {
            return json(['code' => 1, 'msg' => '账号已被禁用']);
        }

        Session::set('admin_id', $admin->id);
        Session::set('admin_info', [
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname,
            'avatar' => $admin->avatar
        ]);

        $admin->last_login_time = time();
        $admin->last_login_ip = request()->ip();
        $admin->save();

        return json(['code' => 0, 'msg' => '登录成功']);
    }

    public function logout()
    {
        Session::delete('admin_id');
        Session::delete('admin_info');
        return redirect('/admin/login');
    }
}
