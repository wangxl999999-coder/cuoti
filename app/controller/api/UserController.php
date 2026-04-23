<?php
declare (strict_types = 1);

namespace app\controller\api;

use think\facade\Request;
use think\facade\Session;
use think\facade\Db;
use app\model\User;
use app\model\Grade;

class UserController extends BaseApiController
{
    public function register()
    {
        $phone = Request::post('phone', '');
        $password = Request::post('password', '');
        $gradeId = Request::post('grade_id', 0, 'intval');
        $nickname = Request::post('nickname', '');
        $openid = Request::post('openid', '');
        
        if (empty($phone) && empty($openid)) {
            return $this->error('手机号或微信openid不能为空');
        }
        
        if (!empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        if (empty($gradeId)) {
            return $this->error('请选择年级');
        }
        
        $grade = Grade::find($gradeId);
        if (!$grade) {
            return $this->error('年级不存在');
        }
        
        if (!empty($phone)) {
            $exists = User::where('phone', $phone)->find();
            if ($exists) {
                return $this->error('该手机号已注册');
            }
        }
        
        if (!empty($openid)) {
            $exists = User::where('openid', $openid)->find();
            if ($exists) {
                return $this->error('该微信账号已注册');
            }
        }
        
        $user = new User();
        $user->phone = $phone;
        $user->openid = !empty($openid) ? $openid : null;
        $user->grade_id = $gradeId;
        $user->nickname = $nickname ?: '用户' . substr($phone ?: ($openid ?: uniqid()), -4);
        $user->points = 0;
        $user->total_points = 0;
        $user->status = 1;
        
        if (!empty($password)) {
            $user->password = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $user->save();
        
        $token = $this->generateToken();
        Session::set('user_' . $token, [
            'user_id' => $user->id,
            'create_time' => time()
        ]);
        
        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'grade_id' => $user->grade_id,
                'grade_name' => $grade->name,
                'points' => $user->points
            ]
        ], '注册成功');
    }
    
    public function login()
    {
        $phone = Request::post('phone', '');
        $password = Request::post('password', '');
        $openid = Request::post('openid', '');
        
        if (empty($phone) && empty($openid)) {
            return $this->error('请输入手机号或使用微信登录');
        }
        
        $user = null;
        if (!empty($phone)) {
            $user = User::where('phone', $phone)->find();
            if (!$user) {
                return $this->error('该手机号未注册');
            }
            
            if (!empty($password)) {
                if (!password_verify($password, $user->password)) {
                    return $this->error('密码错误');
                }
            }
        }
        
        if (!empty($openid) && !$user) {
            $user = User::where('openid', $openid)->find();
            if (!$user) {
                return $this->error('该微信账号未注册', 400, ['need_register' => true]);
            }
        }
        
        if (!$user) {
            return $this->error('用户不存在');
        }
        
        if ($user->status != 1) {
            return $this->error('账号已被禁用');
        }
        
        $user->last_login_time = time();
        $user->last_login_ip = Request::ip();
        $user->save();
        
        $token = $this->generateToken();
        Session::set('user_' . $token, [
            'user_id' => $user->id,
            'create_time' => time()
        ]);
        
        $grade = Grade::find($user->grade_id);
        
        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone' => $user->phone,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'grade_id' => $user->grade_id,
                'grade_name' => $grade ? $grade->name : '',
                'points' => $user->points
            ]
        ], '登录成功');
    }
    
    public function logout()
    {
        $token = Request::header('token', '');
        if ($token) {
            Session::delete('user_' . $token);
        }
        return $this->success(null, '退出成功');
    }
    
    public function getInfo()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $grade = Grade::find($this->user->grade_id);
        
        return $this->success([
            'id' => $this->user->id,
            'phone' => $this->user->phone,
            'nickname' => $this->user->nickname,
            'avatar' => $this->user->avatar,
            'grade_id' => $this->user->grade_id,
            'grade_name' => $grade ? $grade->name : '',
            'points' => $this->user->points,
            'total_points' => $this->user->total_points
        ]);
    }
    
    public function updateInfo()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $nickname = Request::post('nickname', '');
        $gradeId = Request::post('grade_id', 0, 'intval');
        
        if (!empty($nickname)) {
            $this->user->nickname = $nickname;
        }
        
        if ($gradeId > 0) {
            $grade = Grade::find($gradeId);
            if (!$grade) {
                return $this->error('年级不存在');
            }
            $this->user->grade_id = $gradeId;
        }
        
        $this->user->save();
        
        $grade = Grade::find($this->user->grade_id);
        
        return $this->success([
            'id' => $this->user->id,
            'nickname' => $this->user->nickname,
            'avatar' => $this->user->avatar,
            'grade_id' => $this->user->grade_id,
            'grade_name' => $grade ? $grade->name : ''
        ], '修改成功');
    }
    
    public function updatePassword()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $oldPassword = Request::post('old_password', '');
        $newPassword = Request::post('new_password', '');
        
        if (empty($newPassword) || strlen($newPassword) < 6) {
            return $this->error('新密码至少6位');
        }
        
        if (!empty($this->user->password)) {
            if (!password_verify($oldPassword, $this->user->password)) {
                return $this->error('原密码错误');
            }
        }
        
        $this->user->password = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->user->save();
        
        return $this->success(null, '密码修改成功');
    }
    
    private function generateToken()
    {
        return md5(uniqid(mt_rand(), true));
    }
}
