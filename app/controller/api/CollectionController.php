<?php
declare (strict_types = 1);

namespace app\controller\api;

use think\facade\Request;
use app\model\QuestionCollection;
use app\model\CollectionQuestion;
use app\model\WrongQuestion;

class CollectionController extends BaseApiController
{
    public function list()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $list = QuestionCollection::where('user_id', $this->userId)
            ->where('status', 1)
            ->order('is_default', 'desc')
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($list as $item) {
            $questionCount = CollectionQuestion::where('collection_id', $item['id'])->count();
            $result[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'question_count' => $questionCount,
                'cover_image' => $item['cover_image'],
                'is_default' => $item['is_default'],
                'create_time' => $item['create_time']
            ];
        }
        
        return $this->success($result);
    }
    
    public function create()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $name = Request::post('name', '');
        $description = Request::post('description', '');
        
        if (empty($name)) {
            return $this->error('请输入错题本名称');
        }
        
        $collection = new QuestionCollection();
        $collection->user_id = $this->userId;
        $collection->name = $name;
        $collection->description = $description;
        $collection->question_count = 0;
        $collection->is_default = 0;
        $collection->save();
        
        return $this->success([
            'id' => $collection->id,
            'name' => $collection->name
        ], '创建成功');
    }
    
    public function update()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $id = Request::post('id', 0, 'intval');
        $name = Request::post('name', '');
        $description = Request::post('description', '');
        
        if (empty($id)) {
            return $this->error('参数错误');
        }
        
        $collection = QuestionCollection::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$collection) {
            return $this->error('错题本不存在');
        }
        
        if (!empty($name)) {
            $collection->name = $name;
        }
        if ($description !== '') {
            $collection->description = $description;
        }
        
        $collection->save();
        
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
        
        $collection = QuestionCollection::where('id', $id)
            ->where('user_id', $this->userId)
            ->where('is_default', 0)
            ->find();
        
        if (!$collection) {
            return $this->error('错题本不存在或无法删除默认错题本');
        }
        
        $collection->status = 0;
        $collection->save();
        
        CollectionQuestion::where('collection_id', $id)->delete();
        
        return $this->success(null, '删除成功');
    }
    
    public function addQuestion()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $collectionId = Request::post('collection_id', 0, 'intval');
        $wrongQuestionId = Request::post('wrong_question_id', 0, 'intval');
        
        if (empty($collectionId) || empty($wrongQuestionId)) {
            return $this->error('参数错误');
        }
        
        $collection = QuestionCollection::where('id', $collectionId)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$collection) {
            return $this->error('错题本不存在');
        }
        
        $wrongQuestion = WrongQuestion::where('id', $wrongQuestionId)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$wrongQuestion) {
            return $this->error('错题不存在');
        }
        
        $exists = CollectionQuestion::where('collection_id', $collectionId)
            ->where('wrong_question_id', $wrongQuestionId)
            ->find();
        
        if ($exists) {
            return $this->error('该错题已在错题本中');
        }
        
        $cq = new CollectionQuestion();
        $cq->collection_id = $collectionId;
        $cq->wrong_question_id = $wrongQuestionId;
        $cq->user_id = $this->userId;
        $cq->save();
        
        $count = CollectionQuestion::where('collection_id', $collectionId)->count();
        $collection->question_count = $count;
        $collection->save();
        
        return $this->success(null, '添加成功');
    }
    
    public function removeQuestion()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $collectionId = Request::post('collection_id', 0, 'intval');
        $wrongQuestionId = Request::post('wrong_question_id', 0, 'intval');
        
        if (empty($collectionId) || empty($wrongQuestionId)) {
            return $this->error('参数错误');
        }
        
        $cq = CollectionQuestion::where('collection_id', $collectionId)
            ->where('wrong_question_id', $wrongQuestionId)
            ->where('user_id', $this->userId)
            ->find();
        
        if (!$cq) {
            return $this->error('记录不存在');
        }
        
        $cq->delete();
        
        $collection = QuestionCollection::find($collectionId);
        if ($collection) {
            $count = CollectionQuestion::where('collection_id', $collectionId)->count();
            $collection->question_count = $count;
            $collection->save();
        }
        
        return $this->success(null, '移除成功');
    }
    
    public function questions()
    {
        $loginResult = $this->requireLogin();
        if ($loginResult) {
            return $loginResult;
        }
        
        $collectionId = Request::get('collection_id', 0, 'intval');
        $page = Request::get('page', 1, 'intval');
        $pageSize = Request::get('page_size', 10, 'intval');
        
        if (empty($collectionId)) {
            return $this->error('参数错误');
        }
        
        $collection = QuestionCollection::where('id', $collectionId)
            ->where('user_id', $this->userId)
            ->where('status', 1)
            ->find();
        
        if (!$collection) {
            return $this->error('错题本不存在');
        }
        
        $query = CollectionQuestion::where('collection_id', $collectionId)
            ->with(['wrongQuestion' => function($query) {
                $query->with(['subject', 'grade']);
            }]);
        
        $total = $query->count();
        $list = $query->order('add_time', 'desc')
            ->page($page, $pageSize)
            ->select()
            ->toArray();
        
        $result = [];
        foreach ($list as $item) {
            $wq = $item['wrong_question'] ?? [];
            $result[] = [
                'id' => $wq['id'] ?? 0,
                'image_url' => $wq['image_url'] ?? '',
                'question_text' => mb_substr($wq['question_text'] ?? '', 0, 50) . '...',
                'subject_name' => $wq['subject']['name'] ?? '',
                'grade_name' => $wq['grade']['name'] ?? '',
                'is_mastered' => $wq['is_mastered'] ?? 0,
                'create_time' => $wq['create_time'] ?? ''
            ];
        }
        
        return $this->success([
            'collection' => [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description
            ],
            'list' => $result,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize
        ]);
    }
}
