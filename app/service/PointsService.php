<?php
declare (strict_types = 1);

namespace app\service;

use app\model\User;
use app\model\PointsLog;

class PointsService
{
    const POINTS_TYPE_CORRECT = 1;
    const POINTS_TYPE_CONTINUOUS = 2;
    const POINTS_TYPE_CHECKIN = 3;
    const POINTS_TYPE_OTHER = 4;
    
    public function addPoints($userId, $points, $type, $description = '', $relatedId = 0)
    {
        if ($points <= 0) {
            return false;
        }
        
        $user = User::find($userId);
        if (!$user) {
            return false;
        }
        
        $user->points += $points;
        $user->total_points += $points;
        $user->save();
        
        $log = new PointsLog();
        $log->user_id = $userId;
        $log->points = $points;
        $log->balance = $user->points;
        $log->type = $type;
        $log->description = $description ?: $this->getDefaultDescription($type, $points);
        $log->related_id = $relatedId;
        $log->save();
        
        return [
            'points_added' => $points,
            'new_balance' => $user->points,
            'total_points' => $user->total_points
        ];
    }
    
    public function addCorrectAnswerPoints($userId, $relatedId = 0)
    {
        $basePoints = 10;
        return $this->addPoints(
            $userId,
            $basePoints,
            self::POINTS_TYPE_CORRECT,
            '答题正确获得积分',
            $relatedId
        );
    }
    
    public function addContinuousCorrectPoints($userId, $continuousCount, $relatedId = 0)
    {
        if ($continuousCount < 3) {
            return false;
        }
        
        $bonusPoints = $continuousCount * 2;
        $maxBonus = 20;
        $bonusPoints = min($bonusPoints, $maxBonus);
        
        return $this->addPoints(
            $userId,
            $bonusPoints,
            self::POINTS_TYPE_CONTINUOUS,
            "连续答对{$continuousCount}题，额外获得{$bonusPoints}积分",
            $relatedId
        );
    }
    
    public function addCheckinPoints($userId, $continuousDays)
    {
        $basePoints = 5;
        $bonusPoints = min($continuousDays - 1, 7);
        $totalPoints = $basePoints + $bonusPoints;
        
        return $this->addPoints(
            $userId,
            $totalPoints,
            self::POINTS_TYPE_CHECKIN,
            $continuousDays > 1 ? "签到成功，连续签到{$continuousDays}天，获得{$totalPoints}积分" : '签到成功，获得5积分'
        );
    }
    
    protected function getDefaultDescription($type, $points)
    {
        $descriptions = [
            self::POINTS_TYPE_CORRECT => '答题正确获得积分',
            self::POINTS_TYPE_CONTINUOUS => '连续答对额外奖励',
            self::POINTS_TYPE_CHECKIN => '每日签到获得积分',
            self::POINTS_TYPE_OTHER => '其他积分'
        ];
        
        return $descriptions[$type] ?? '获得积分';
    }
}
