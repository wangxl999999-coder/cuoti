<?php
declare (strict_types = 1);

namespace app\controller\api;

use think\facade\Request;
use app\model\Question;
use app\model\WrongQuestion;
use app\model\ExamRecord;
use app\model\Subject;
use app\service\AiService;
use app\service\PointsService;

class QuestionController extends BaseApiController
{
    public function generateFromWrong()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $wrongQuestionId = Request::post('wrong_question_id', 0, 'intval');
        $count = Request::post('count', 3, 'intval');
        
        if (empty($wrongQuestionId)) {
            return $this->error('参数错误');
        }
        
        $wrongQuestion = WrongQuestion::where('id', $wrongQuestionId)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$wrongQuestion) {
            return $this->error('错题不存在');
        }
        
        $existingQuestions = Question::where('wrong_question_id', $wrongQuestionId)
            ->where('status', 1)
            ->select();
        
        if ($existingQuestions->count() > 0) {
            $result = [];
            foreach ($existingQuestions as $q) {
                $result[] = $this->formatQuestion($q);
            }
            return $this->success($result);
        }
        
        $aiService = new AiService();
        $generatedQuestions = $aiService->generateSimilarQuestions($wrongQuestion, $count);
        
        if (empty($generatedQuestions)) {
            return $this->error('生成试题失败，请稍后重试');
        }
        
        $result = [];
        foreach ($generatedQuestions as $qData) {
            $question = new Question();
            $question->wrong_question_id = $wrongQuestionId;
            $question->subject_id = $wrongQuestion->subject_id;
            $question->grade_id = $wrongQuestion->grade_id;
            $question->question_text = $qData['question_text'] ?? '';
            $question->option_a = $qData['option_a'] ?? '';
            $question->option_b = $qData['option_b'] ?? '';
            $question->option_c = $qData['option_c'] ?? '';
            $question->option_d = $qData['option_d'] ?? '';
            $question->correct_answer = $qData['correct_answer'] ?? '';
            $question->answer_text = $qData['answer_text'] ?? '';
            $question->knowledge_points = $wrongQuestion->knowledge_points;
            $question->difficulty = $wrongQuestion->difficulty;
            $question->question_type = $qData['question_type'] ?? 1;
            $question->is_ai_generated = 1;
            $question->save();
            
            $result[] = $this->formatQuestion($question);
        }
        
        return $this->success($result, '试题生成成功');
    }
    
    public function getRandom()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $subjectId = Request::get('subject_id', 0, 'intval');
        $count = Request::get('count', 5, 'intval');
        
        $query = Question::where('status', 1);
        
        if ($subjectId > 0) {
            $query->where('subject_id', $subjectId);
        }
        
        $total = $query->count();
        
        if ($total == 0) {
            $wrongQuestions = WrongQuestion::where('user_id', $this->userId)
                ->where('status', 1)
                ->limit(3)
                ->select();
            
            $aiService = new AiService();
            $allQuestions = [];
            
            foreach ($wrongQuestions as $wq) {
                $generated = $aiService->generateSimilarQuestions($wq, 2);
                foreach ($generated as $qData) {
                    $question = new Question();
                    $question->wrong_question_id = $wq->id;
                    $question->subject_id = $wq->subject_id;
                    $question->grade_id = $wq->grade_id;
                    $question->question_text = $qData['question_text'] ?? '';
                    $question->option_a = $qData['option_a'] ?? '';
                    $question->option_b = $qData['option_b'] ?? '';
                    $question->option_c = $qData['option_c'] ?? '';
                    $question->option_d = $qData['option_d'] ?? '';
                    $question->correct_answer = $qData['correct_answer'] ?? '';
                    $question->answer_text = $qData['answer_text'] ?? '';
                    $question->knowledge_points = $wq->knowledge_points;
                    $question->difficulty = $wq->difficulty;
                    $question->question_type = $qData['question_type'] ?? 1;
                    $question->is_ai_generated = 1;
                    $question->save();
                    
                    $allQuestions[] = $this->formatQuestion($question);
                }
            }
            
            return $this->success(array_slice($allQuestions, 0, $count));
        }
        
        $questions = $query->orderRaw('RAND()')
            ->limit($count)
            ->with(['subject', 'grade'])
            ->select();
        
        $result = [];
        foreach ($questions as $q) {
            $result[] = $this->formatQuestion($q);
        }
        
        return $this->success($result);
    }
    
    public function submit()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $questionId = Request::post('question_id', 0, 'intval');
        $userAnswer = Request::post('user_answer', '');
        $usedTime = Request::post('used_time', 0, 'intval');
        
        if (empty($questionId) || $userAnswer === '') {
            return $this->error('参数错误');
        }
        
        $question = Question::where('id', $questionId)
            ->where('status', 1)
            ->find();
        
        if (!$question) {
            return $this->error('试题不存在');
        }
        
        $isCorrect = $this->checkAnswer($userAnswer, $question->correct_answer, $question->question_type);
        
        $examRecord = new ExamRecord();
        $examRecord->user_id = $this->userId;
        $examRecord->question_id = $questionId;
        $examRecord->wrong_question_id = $question->wrong_question_id;
        $examRecord->user_answer = $userAnswer;
        $examRecord->correct_answer = $question->correct_answer;
        $examRecord->is_correct = $isCorrect ? 1 : 0;
        $examRecord->used_time = $usedTime;
        $examRecord->save();
        
        $pointsService = new PointsService();
        $pointsEarned = 0;
        $totalPoints = 0;
        
        if ($isCorrect) {
            $pointsResult = $pointsService->addCorrectAnswerPoints($this->userId, $examRecord->id);
            if ($pointsResult) {
                $pointsEarned = $pointsResult['points_added'];
                $totalPoints = $pointsResult['new_balance'];
            }
            
            $continuousCount = $this->getContinuousCorrectCount();
            if ($continuousCount >= 3 && $continuousCount % 3 == 0) {
                $bonusResult = $pointsService->addContinuousCorrectPoints($this->userId, $continuousCount, $examRecord->id);
                if ($bonusResult) {
                    $pointsEarned += $bonusResult['points_added'];
                    $totalPoints = $bonusResult['new_balance'];
                }
            }
        }
        
        $examRecord->points_earned = $pointsEarned;
        $examRecord->save();
        
        return $this->success([
            'is_correct' => $isCorrect,
            'correct_answer' => $question->correct_answer,
            'answer_text' => $question->answer_text,
            'points_earned' => $pointsEarned,
            'total_points' => $totalPoints,
            'question_type' => $question->question_type
        ], $isCorrect ? '回答正确！' : '回答错误');
    }
    
    public function history()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $page = Request::get('page', 1, 'intval');
        $pageSize = Request::get('page_size', 10, 'intval');
        
        $query = ExamRecord::where('user_id', $this->userId);
        
        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $pageSize)
            ->with(['question'])
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item['id'],
                'question_id' => $item['question_id'],
                'question_text' => mb_substr($item['question']['question_text'] ?? '', 0, 30) . '...',
                'user_answer' => $item['user_answer'],
                'correct_answer' => $item['correct_answer'],
                'is_correct' => $item['is_correct'],
                'points_earned' => $item['points_earned'],
                'used_time' => $item['used_time'],
                'create_time' => $item['create_time']
            ];
        }
        
        $correctCount = ExamRecord::where('user_id', $this->userId)
            ->where('is_correct', 1)
            ->count();
        
        $totalQuestions = ExamRecord::where('user_id', $this->userId)->count();
        
        return $this->success([
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'statistics' => [
                'total_questions' => $totalQuestions,
                'correct_count' => $correctCount,
                'accuracy' => $totalQuestions > 0 ? round($correctCount / $totalQuestions * 100, 1) : 0
            ]
        ]);
    }
    
    protected function checkAnswer($userAnswer, $correctAnswer, $questionType)
    {
        $userAnswer = trim($userAnswer);
        $correctAnswer = trim($correctAnswer);
        
        if ($questionType == 2) {
            $userOptions = array_map('trim', explode(',', $userAnswer));
            $correctOptions = array_map('trim', explode(',', $correctAnswer));
            sort($userOptions);
            sort($correctOptions);
            return $userOptions == $correctOptions;
        }
        
        if ($questionType == 3 || $questionType == 4) {
            similar_text($userAnswer, $correctAnswer, $percent);
            return $percent >= 70;
        }
        
        return strtoupper($userAnswer) == strtoupper($correctAnswer);
    }
    
    protected function getContinuousCorrectCount()
    {
        $records = ExamRecord::where('user_id', $this->userId)
            ->order('create_time', 'desc')
            ->limit(10)
            ->select();
        
        $count = 0;
        foreach ($records as $record) {
            if ($record->is_correct) {
                $count++;
            } else {
                break;
            }
        }
        
        return $count;
    }
    
    protected function formatQuestion($question)
    {
        return [
            'id' => $question->id,
            'wrong_question_id' => $question->wrong_question_id,
            'subject_id' => $question->subject_id,
            'subject_name' => $question->subject->name ?? '',
            'question_text' => $question->question_text,
            'option_a' => $question->option_a,
            'option_b' => $question->option_b,
            'option_c' => $question->option_c,
            'option_d' => $question->option_d,
            'question_type' => $question->question_type,
            'difficulty' => $question->difficulty,
            'knowledge_points' => $question->knowledge_points
        ];
    }
}
