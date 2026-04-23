<?php
declare (strict_types = 1);

namespace app\service;

use think\facade\Config;

class AiService
{
    protected $apiKey;
    protected $apiUrl;
    protected $ocrApiKey;
    
    public function __construct()
    {
        $this->apiKey = Config::get('app.ai_api_key', '');
        $this->apiUrl = Config::get('app.ai_api_url', 'https://api.openai.com/v1/chat/completions');
        $this->ocrApiKey = Config::get('app.ocr_api_key', '');
    }
    
    public function ocrRecognize($imagePath)
    {
        $fullImagePath = public_path() . ltrim($imagePath, '/');
        
        if (!file_exists($fullImagePath)) {
            return false;
        }
        
        $imageData = file_get_contents($fullImagePath);
        $base64Image = base64_encode($imageData);
        
        $prompt = "请分析这张错题图片，提取以下信息：
1. 题目内容（question）
2. 正确答案（answer）
3. 详细解析（analysis）
4. 相关知识点（knowledge_points，用逗号分隔）

请以JSON格式返回，格式如下：
{
    \"question\": \"题目内容\",
    \"answer\": \"答案内容\",
    \"analysis\": \"解析内容\",
    \"knowledge_points\": \"知识点1,知识点2\"
}";

        $result = $this->callVisionApi($base64Image, $prompt);
        
        if ($result) {
            $jsonData = $this->extractJson($result);
            if ($jsonData) {
                return $jsonData;
            }
        }
        
        return false;
    }
    
    public function generateSimilarQuestions($wrongQuestion, $count = 3)
    {
        $prompt = "你是一位资深的中小学教师，请根据以下错题，生成{$count}道同类型的练习题。

错题信息：
题目：{$wrongQuestion->question_text}
答案：{$wrongQuestion->answer_text}
解析：{$wrongQuestion->analysis}
知识点：{$wrongQuestion->knowledge_points}

请生成{$count}道同类型、同难度的练习题。每道题需要包含：
1. 题目内容（question_text）
2. 选项（如果是选择题，包括A、B、C、D选项）
3. 正确答案（correct_answer）
4. 详细解析（answer_text）
5. 题型（question_type：1单选 2多选 3填空 4解答）

请以JSON数组格式返回，格式如下：
[
    {
        \"question_text\": \"题目内容\",
        \"option_a\": \"选项A\",
        \"option_b\": \"选项B\",
        \"option_c\": \"选项C\",
        \"option_d\": \"选项D\",
        \"correct_answer\": \"A\",
        \"answer_text\": \"详细解析\",
        \"question_type\": 1
    }
]";

        $result = $this->callChatApi($prompt);
        
        if ($result) {
            $jsonData = $this->extractJson($result);
            if ($jsonData && is_array($jsonData)) {
                return $jsonData;
            }
        }
        
        return [];
    }
    
    public function analyzeAnswer($question, $userAnswer)
    {
        $prompt = "请分析学生的答题情况。

题目信息：
题目：{$question->question_text}
正确答案：{$question->correct_answer}
解析：{$question->answer_text}

学生答案：{$userAnswer}

请判断学生答案是否正确，并给出详细的分析。以JSON格式返回：
{
    \"is_correct\": true,
    \"analysis\": \"详细分析内容\",
    \"suggestion\": \"学习建议\"
}";

        $result = $this->callChatApi($prompt);
        
        if ($result) {
            $jsonData = $this->extractJson($result);
            if ($jsonData) {
                return $jsonData;
            }
        }
        
        return [
            'is_correct' => false,
            'analysis' => 'AI分析暂时不可用',
            'suggestion' => '请对照答案自行分析'
        ];
    }
    
    protected function callChatApi($prompt)
    {
        if (empty($this->apiKey)) {
            return false;
        }
        
        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system', 'content' => '你是一位专业的中小学教育助手，擅长出题和分析题目。'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? false;
        }
        
        return false;
    }
    
    protected function callVisionApi($base64Image, $prompt)
    {
        if (empty($this->apiKey)) {
            return false;
        }
        
        $data = [
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $base64Image
                            ]
                        ]
                    ]
                ]
            ],
            'max_tokens' => 2000
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $response) {
            $result = json_decode($response, true);
            return $result['choices'][0]['message']['content'] ?? false;
        }
        
        return false;
    }
    
    protected function extractJson($text)
    {
        $pattern = '/\{[\s\S]*\}|\[[\s\S]*\]/';
        if (preg_match($pattern, $text, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        return null;
    }
}
