<?php
namespace app\controller\admin;

use think\facade\View;
use think\facade\Db;
use app\model\WrongQuestion;
use app\model\User;
use app\model\Subject;
use app\model\Grade;
use app\model\ExamRecord;

class WrongQuestionController extends BaseAdminController
{
    public function index()
    {
        $subjects = Subject::select();
        $grades = Grade::select();
        View::assign('subjects', $subjects);
        View::assign('grades', $grades);
        return View::fetch();
    }

    public function list()
    {
        $page = request()->get('page', 1);
        $limit = request()->get('limit', 10);
        $keyword = request()->get('keyword', '');
        $subjectId = request()->get('subject_id', 0);
        $gradeId = request()->get('grade_id', 0);
        $isMastered = request()->get('is_mastered', -1);

        $query = WrongQuestion::with(['user', 'subject', 'grade']);
        
        if ($keyword) {
            $query->where('question_text', 'like', "%{$keyword}%");
        }

        if ($subjectId > 0) {
            $query->where('subject_id', $subjectId);
        }

        if ($gradeId > 0) {
            $query->where('grade_id', $gradeId);
        }

        if ($isMastered >= 0) {
            $query->where('is_mastered', $isMastered);
        }

        $total = $query->count();
        $list = $query->order('id', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['user_name'] = $item['user'] ? $item['user']['nickname'] : '未知用户';
            $item['subject_name'] = $item['subject'] ? $item['subject']['name'] : '';
            $item['grade_name'] = $item['grade'] ? $item['grade']['name'] : '';
            $item['mastered_text'] = $item['is_mastered'] ? '已掌握' : '待掌握';
            $item['difficulty_stars'] = str_repeat('★', $item['difficulty']) . str_repeat('☆', 3 - $item['difficulty']);
            unset($item['user'], $item['subject'], $item['grade']);
        }

        return $this->layuiTable($list, $total);
    }

    public function detail()
    {
        $id = request()->get('id', 0);
        $question = WrongQuestion::with(['user', 'subject', 'grade'])->find($id);
        
        if (!$question) {
            return $this->error('错题不存在');
        }

        $examRecords = ExamRecord::where('wrong_question_id', $id)
            ->with('question')
            ->order('id', 'desc')
            ->limit(20)
            ->select();

        View::assign('question', $question);
        View::assign('examRecords', $examRecords);
        View::assign('userName', $question->user ? $question->user->nickname : '未知用户');
        View::assign('subjectName', $question->subject ? $question->subject->name : '');
        View::assign('gradeName', $question->grade ? $question->grade->name : '');

        return View::fetch();
    }

    public function delete()
    {
        $id = request()->post('id', 0);
        $ids = request()->post('ids', []);

        if ($ids) {
            WrongQuestion::destroy($ids);
        } elseif ($id) {
            WrongQuestion::destroy($id);
        }

        return $this->success();
    }
}
