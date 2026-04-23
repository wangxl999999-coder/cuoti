<?php
declare (strict_types = 1);

namespace app\controller\api;

use app\BaseController;
use think\facade\Request;
use think\facade\Session;
use app\model\User;

class BaseApiController extends BaseController
{
    protected $userId = 0;
    protected $user = null;
    
    protected function initialize()
    {
        parent::initialize();
        
        $token = Request::header('token', '');
        if ($token) {
            $this->checkToken($token);
        }
    }
    
    protected function checkToken($token)
    {
        $sessionData = Session::get('user_' . $token);
        if ($sessionData && isset($sessionData['user_id'])) {
            $this->userId = $sessionData['user_id'];
            $this->user = User::find($this->userId);
        }
    }
    
    protected function requireLogin()
    {
        if (!$this->userId || !$this->user) {
            return $this->error('请先登录', 401);
        }
        return null;
    }
    
    protected function success($data = null, $msg = '操作成功', $code = 200)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }
    
    protected function error($msg = '操作失败', $code = 400, $data = null)
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }
}
