<?php
namespace app\controller\admin;

use think\facade\View;
use think\facade\Db;
use app\model\PointsLog;
use app\model\User;
use app\model\ExamRecord;
use app\model\Checkin;

class PointsController extends BaseAdminController
{
    public function index()
    {
        $typeList = [
            ['value' => 0, 'name' => '全部'],
            ['value' => 1, 'name' => '答题奖励'],
            ['value' => 2, 'name' => '连续答题奖励'],
            ['value' => 3, 'name' => '签到奖励'],
            ['value' => 4, 'name' => '连续签到奖励'],
            ['value' => 5, 'name' => '掌握奖励'],
            ['value' => 6, 'name' => '新用户奖励'],
        ];
        View::assign('typeList', $typeList);
        return View::fetch();
    }

    public function list()
    {
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 10);
        $keyword = request()->get('keyword', '');
        $type = request()->get('type', 0);

        $query = PointsLog::with('user');
        
        if ($keyword) {
            $userIds = User::where('phone', 'like', "%{$keyword}%")
                ->whereOr('nickname', 'like', "%{$keyword}%")
                ->column('id');
            if ($userIds) {
                $query->whereIn('user_id', $userIds);
            }
        }

        if ($type > 0) {
            $query->where('type', $type);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        $typeNames = [
            1 => '答题奖励',
            2 => '连续答题奖励',
            3 => '签到奖励',
            4 => '连续签到奖励',
            5 => '掌握奖励',
            6 => '新用户奖励',
        ];

        foreach ($list as &$item) {
            $item['user_name'] = $item['user'] ? $item['user']['nickname'] : '未知用户';
            $item['user_phone'] = $item['user'] ? $item['user']['phone'] : '';
            $item['type_name'] = $typeNames[$item['type']] ?? '其他';
            $item['points_text'] = $item['points'] > 0 ? '+' . $item['points'] : $item['points'];
            unset($item['user']);
        }

        return $this->layuiTable($list, $total);
    }

    public function statistics()
    {
        $statistics = [
            'total_earned' => PointsLog::where('points', '>', 0)->sum('points'),
            'total_spent' => abs(PointsLog::where('points', '<', 0)->sum('points')),
            'user_count' => PointsLog::distinct('user_id')->count(),
            'today_earned' => PointsLog::where('points', '>', 0)
                ->whereTime('create_time', 'today')
                ->sum('points'),
            'checkin_count' => Checkin::count(),
            'today_checkin' => Checkin::whereTime('checkin_date', 'today')->count(),
        ];

        $typeStats = PointsLog::field('type, SUM(points) as total, COUNT(*) as count')
            ->group('type')
            ->select();

        View::assign('statistics', $statistics);
        View::assign('typeStats', $typeStats);

        return View::fetch();
    }

    public function examRecords()
    {
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 10);
        $keyword = request()->get('keyword', '');
        $isCorrect = request()->get('is_correct', -1);

        $query = ExamRecord::with(['user', 'wrongQuestion']);
        
        if ($keyword) {
            $userIds = User::where('phone', 'like', "%{$keyword}%")
                ->whereOr('nickname', 'like', "%{$keyword}%")
                ->column('id');
            if ($userIds) {
                $query->whereIn('user_id', $userIds);
            }
        }

        if ($isCorrect >= 0) {
            $query->where('is_correct', $isCorrect);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['user_name'] = $item['user'] ? $item['user']['nickname'] : '未知用户';
            $item['question_text'] = $item['wrong_question'] ? mb_substr($item['wrong_question']['question_text'], 0, 30) . '...' : '';
            $item['correct_text'] = $item['is_correct'] ? '正确' : '错误';
            unset($item['user'], $item['wrong_question']);
        }

        return $this->layuiTable($list, $total);
    }
}
