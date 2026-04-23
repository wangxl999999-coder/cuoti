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

class Index extends BaseAdminController
{
    public function index()
    {
        $adminInfo = Session::get('admin_info');
        
        $statistics = [
            'user_count' => User::count(),
            'today_user' => User::whereTime('create_time', 'today')->count(),
            'wrong_question_count' => WrongQuestion::count(),
            'exam_count' => ExamRecord::count(),
            'points_total' => PointsLog::where('points', '>', 0)->sum('points'),
            'question_count' => Question::count()
        ];

        $userTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} day"));
            $userTrend[] = [
                'date' => $date,
                'count' => User::whereTime('create_time', $date)->count()
            ];
        }

        $gradeUsers = Grade::withCount('users')->select();
        
        $subjectStats = Subject::withCount('wrongQuestions')->select();

        View::assign('adminInfo', $adminInfo);
        View::assign('statistics', $statistics);
        View::assign('userTrend', $userTrend);
        View::assign('gradeUsers', $gradeUsers);
        View::assign('subjectStats', $subjectStats);

        return View::fetch();
    }

    public function welcome()
    {
        return $this->index();
    }
}
