<?php
declare (strict_types = 1);

namespace app\controller\api;

use think\facade\Request;
use think\facade\Filesystem;
use think\file\UploadedFile;
use app\model\WrongQuestion;
use app\model\Subject;
use app\model\Grade;
use app\service\AiService;

class WrongQuestionController extends BaseApiController
{
    public function upload()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $file = Request::file('image');
        if (!$file) {
            return $this->error('请上传图片');
        }
        
        if (!$file->checkImg() || !in_array($file->getOriginalExtension(), ['jpg', 'jpeg', 'png', 'gif'])) {
            return $this->error('请上传有效的图片文件');
        }
        
        $saveName = Filesystem::disk('public')->putFile('questions', $file);
        if (!$saveName) {
            return $this->error('图片上传失败');
        }
        
        $imageUrl = '/storage/' . $saveName;
        
        $subjectId = Request::post('subject_id', 0, 'intval');
        $gradeId = Request::post('grade_id', $this->user->grade_id, 'intval');
        
        $questionText = Request::post('question_text', '');
        $answerText = Request::post('answer_text', '');
        $analysis = Request::post('analysis', '');
        $knowledgePoints = Request::post('knowledge_points', '');
        $difficulty = Request::post('difficulty', 1, 'intval');
        $source = Request::post('source', '');
        
        $wrongQuestion = new WrongQuestion();
        $wrongQuestion->user_id = $this->userId;
        $wrongQuestion->subject_id = $subjectId;
        $wrongQuestion->grade_id = $gradeId;
        $wrongQuestion->image_url = $imageUrl;
        $wrongQuestion->question_text = $questionText;
        $wrongQuestion->answer_text = $answerText;
        $wrongQuestion->analysis = $analysis;
        $wrongQuestion->knowledge_points = $knowledgePoints;
        $wrongQuestion->difficulty = $difficulty;
        $wrongQuestion->source = $source;
        $wrongQuestion->error_count = 1;
        $wrongQuestion->is_mastered = 0;
        $wrongQuestion->save();
        
        if (empty($questionText) && !empty($imageUrl)) {
            $aiService = new AiService();
            $ocrResult = $aiService->ocrRecognize($imageUrl);
            if ($ocrResult) {
                $wrongQuestion->question_text = $ocrResult['question'] ?? '';
                $wrongQuestion->answer_text = $ocrResult['answer'] ?? '';
                $wrongQuestion->analysis = $ocrResult['analysis'] ?? '';
                $wrongQuestion->knowledge_points = $ocrResult['knowledge_points'] ?? '';
                $wrongQuestion->save();
            }
        }
        
        $subject = Subject::find($subjectId);
        $grade = Grade::find($gradeId);
        
        return $this->success([
            'id' => $wrongQuestion->id,
            'image_url' => $imageUrl,
            'question_text' => $wrongQuestion->question_text,
            'answer_text' => $wrongQuestion->answer_text,
            'analysis' => $wrongQuestion->analysis,
            'knowledge_points' => $wrongQuestion->knowledge_points,
            'subject_name' => $subject ? $subject->name : '',
            'grade_name' => $grade ? $grade->name : '',
            'create_time' => $wrongQuestion->create_time
        ], '上传成功');
    }
    
    public function list()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $page = Request::get('page', 1, 'intval');
        $pageSize = Request::get('page_size', 10, 'intval');
        $subjectId = Request::get('subject_id', 0, 'intval');
        $isMastered = Request::get('is_mastered', -1, 'intval');
        $keyword = Request::get('keyword', '');
        
        $query = WrongQuestion::where('user_id', $this->userId)
            ->where('status', 1);
        
        if ($subjectId > 0) {
            $query->where('subject_id', $subjectId);
        }
        
        if ($isMastered >= 0) {
            $query->where('is_mastered', $isMastered);
        }
        
        if (!empty($keyword)) {
            $query->where('question_text', 'like', '%' . $keyword . '%');
        }
        
        $total = $query->count();
        $list = $query->order('create_time', 'desc')
            ->page($page, $pageSize)
            ->with(['subject', 'grade'])
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'id' => $item['id'],
                'image_url' => $item['image_url'],
                'question_text' => mb_substr($item['question_text'], 0, 50) . (mb_strlen($item['question_text']) > 50 ? '...' : ''),
                'subject_id' => $item['subject_id'],
                'subject_name' => $item['subject']['name'] ?? '',
                'grade_name' => $item['grade']['name'] ?? '',
                'error_count' => $item['error_count'],
                'is_mastered' => $item['is_mastered'],
                'difficulty' => $item['difficulty'],
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
    
    public function detail()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $id = Request::get('id', 0, 'intval');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $wrongQuestion = WrongQuestion::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->with(['subject', 'grade'])
            ->find();
        
        if (!$wrongQuestion) {
            return $this->error('错题不存在');
        }
        
        return $this->success([
            'id' => $wrongQuestion->id,
            'image_url' => $wrongQuestion->image_url,
            'question_text' => $wrongQuestion->question_text,
            'answer_text' => $wrongQuestion->answer_text,
            'analysis' => $wrongQuestion->analysis,
            'knowledge_points' => $wrongQuestion->knowledge_points,
            'subject_id' => $wrongQuestion->subject_id,
            'subject_name' => $wrongQuestion->subject->name ?? '',
            'grade_name' => $wrongQuestion->grade->name ?? '',
            'error_count' => $wrongQuestion->error_count,
            'is_mastered' => $wrongQuestion->is_mastered,
            'difficulty' => $wrongQuestion->difficulty,
            'source' => $wrongQuestion->source,
            'create_time' => $wrongQuestion->create_time
        ]);
    }
    
    public function update()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $id = Request::post('id', 0, 'intval');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $wrongQuestion = WrongQuestion::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$wrongQuestion) {
            return $this->error('错题不存在');
        }
        
        $subjectId = Request::post('subject_id', 0, 'intval');
        $questionText = Request::post('question_text', '');
        $answerText = Request::post('answer_text', '');
        $analysis = Request::post('analysis', '');
        $knowledgePoints = Request::post('knowledge_points', '');
        $difficulty = Request::post('difficulty', 1, 'intval');
        $source = Request::post('source', '');
        
        if ($subjectId > 0) {
            $wrongQuestion->subject_id = $subjectId;
        }
        if ($questionText !== '') {
            $wrongQuestion->question_text = $questionText;
        }
        if ($answerText !== '') {
            $wrongQuestion->answer_text = $answerText;
        }
        if ($analysis !== '') {
            $wrongQuestion->analysis = $analysis;
        }
        if ($knowledgePoints !== '') {
            $wrongQuestion->knowledge_points = $knowledgePoints;
        }
        if ($difficulty > 0) {
            $wrongQuestion->difficulty = $difficulty;
        }
        if ($source !== '') {
            $wrongQuestion->source = $source;
        }
        
        $wrongQuestion->save();
        
        return $this->success(null, '修改成功');
    }
    
    public function delete()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $id = Request::post('id', 0, 'intval');
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $wrongQuestion = WrongQuestion::where('id', $id)
            ->where('user_id', $this->userId)
            ->find();
        
        if (!$wrongQuestion) {
            return $this->error('错题不存在');
        }
        
        $wrongQuestion->status = 0;
        $wrongQuestion->save();
        
        return $this->success(null, '删除成功');
    }
    
    public function setMastered()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $id = Request::post('id', 0, 'intval');
        $isMastered = Request::post('is_mastered', 1, 'intval');
        
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $wrongQuestion = WrongQuestion::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$wrongQuestion) {
            return $this->error('错题不存在');
        }
        
        $wrongQuestion->is_mastered = $isMastered;
        $wrongQuestion->save();
        
        return $this->success([
            'is_mastered' => $isMastered
        ], $isMastered ? '已标记为掌握' : '已取消掌握标记');
    }
    
    public function statistics()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $total = WrongQuestion::where('user_id', $this->userId)
            ->where('status', 1)
            ->count();
        
        $mastered = WrongQuestion::where('user_id', $this->userId)
            ->where('status', 1)
            ->where('is_mastered', 1)
            ->count();
        
        $unMastered = $total - $mastered;
        
        $subjectStats = WrongQuestion::where('user_id', $this->userId)
            ->where('status', 1)
            ->with('subject')
            ->select()
            ->toArray();
        
        $subjectCount = [];
        foreach ($subjectStats as $item) {
            $subjectName = $item['subject']['name'] ?? '其他';
            if (!isset($subjectCount[$subjectName])) {
                $subjectCount[$subjectName] = 0;
            }
            $subjectCount[$subjectName]++;
        }
        
        $subjectList = [];
        foreach ($subjectCount as $name => $count) {
            $subjectList[] = [
                'name' => $name,
                'count' => $count
            ];
        }
        
        return $this->success([
            'total' => $total,
            'mastered' => $mastered,
            'unmastered' => $unMastered,
            'mastered_rate' => $total > 0 ? round($mastered / $total * 100, 1) : 0,
            'subject_list' => $subjectList
        ]);
    }
}
