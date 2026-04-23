<?php
declare (strict_types = 1);

namespace app\controller\api;

use think\facade\Request;
use app\model\PointsLog;
use app\service\PointsService;

class PointsController extends BaseApiController
{
    public function info()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        return $this->success([
            'points' => $this->user->points,
            'total_points' => $this->user->total_points
        ]);
    }
    
    public function logs()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $page = Request::get('page', 1, 'intval');
        $pageSize = Request::get('page_size', 10, 'intval');
        $type = Request::get('type', 0, 'intval');
        
        $query = PointsLog::where('user_id', $this->userId);
        
        if ($type > 0) {
            $query->where('type', $type);
        }
        
        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item['id'],
                'points' => $item['points'],
                'balance' => $item['balance'],
                'type' => $item['type'],
                'type_name' => $this->getTypeName($item['type']),
                'description' => $item['description'],
                'create_time' => $item['create_time']
            ];
        }
        
        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize
        ]);
    }
    
    public function statistics()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $totalEarned = PointsLog::where('user_id', $this->userId)
            ->where('points', '>', 0)
            ->sum('points');
        
        $correctCount = PointsLog::where('user_id', $this->userId)
            ->where('type', PointsService::POINTS_TYPE_CORRECT)
            ->count();
        
        $checkinCount = PointsLog::where('user_id', $this->userId)
            ->where('type', PointsService::POINTS_TYPE_CHECKIN)
            ->count();
        
        return $this->success([
            'current_points' => $this->user->points,
            'total_earned' => $totalEarned,
            'correct_count' => $correctCount,
            'checkin_count' => $checkinCount
        ]);
    }
    
    protected function getTypeName($type)
    {
        $types = [
            PointsService::POINTS_TYPE_CORRECT => '答题奖励',
            PointsService::POINTS_TYPE_CONTINUOUS => '连续答对奖励',
            PointsService::POINTS_TYPE_CHECKIN => '签到奖励',
            PointsService::POINTS_TYPE_OTHER => '其他'
        ];
        
        return $types[$type] ?? '其他';
    }
}
