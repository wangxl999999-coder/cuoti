<?php
declare (strict_types = 1);

namespace app\controller\api;

use think\facade\Request;
use app\model\Checkin;
use app\service\PointsService;

class CheckinController extends BaseApiController
{
    public function today()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $today = date('Y-m-d');
        $checkin = Checkin::where('user_id', $this->userId)
            ->where('checkin_date', $today)
            ->find();
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayCheckin = Checkin::where('user_id', $this->userId)
            ->where('checkin_date', $yesterday)
            ->find();
        
        $continuousDays = 1;
        if ($yesterdayCheckin) {
            $continuousDays = $yesterdayCheckin->continuous_days + 1;
        }
        
        $monthStart = date('Y-m-01');
        $monthCheckins = Checkin::where('user_id', $this->userId)
            ->where('checkin_date', '>=', $monthStart)
            ->where('checkin_date', '<=', $today)
            ->count();
        
        $daysInMonth = date('t');
        
        return $this->success([
            'is_checked' => $checkin ? true : false,
            'checkin_date' => $today,
            'continuous_days' => $checkin ? $checkin->continuous_days : $continuousDays,
            'month_checkins' => $monthCheckins,
            'days_in_month' => $daysInMonth,
            'today_points' => $this->calculateTodayPoints($continuousDays)
        ]);
    }
    
    public function checkin()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $today = date('Y-m-d');
        $checkin = Checkin::where('user_id', $this->userId)
            ->where('checkin_date', $today)
            ->find();
        
        if ($checkin) {
            return $this->error('今天已经签到过了');
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayCheckin = Checkin::where('user_id', $this->userId)
            ->where('checkin_date', $yesterday)
            ->find();
        
        $continuousDays = 1;
        if ($yesterdayCheckin) {
            $continuousDays = $yesterdayCheckin->continuous_days + 1;
        }
        
        $pointsService = new PointsService();
        $pointsResult = $pointsService->addCheckinPoints($this->userId, $continuousDays);
        
        $checkin = new Checkin();
        $checkin->user_id = $this->userId;
        $checkin->checkin_date = $today;
        $checkin->points = $pointsResult['points_added'] ?? $this->calculateTodayPoints($continuousDays);
        $checkin->continuous_days = $continuousDays;
        $checkin->save();
        
        return $this->success([
            'checkin_date' => $today,
            'continuous_days' => $continuousDays,
            'points_earned' => $checkin->points,
            'new_balance' => $this->user->points + $checkin->points
        ], '签到成功！');
    }
    
    public function history()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $page = Request::get('page', 1, 'intval');
        $pageSize = Request::get('page_size', 10, 'intval');
        
        $query = Checkin::where('user_id', $this->userId);
        
        $total = $query->count();
        $list = $query->order('checkin_date', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize
        ]);
    }
    
    protected function calculateTodayPoints($continuousDays)
    {
        $basePoints = 5;
        $bonusPoints = min($continuousDays - 1, 7);
        return $basePoints + $bonusPoints;
    }
}
