<?php
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');

Route::group('api', function () {
    Route::post('user/register', 'api.UserController/register');
    Route::post('user/login', 'api.UserController/login');
    Route::post('user/logout', 'api.UserController/logout');
    Route::get('user/info', 'api.UserController/getInfo');
    Route::post('user/update', 'api.UserController/updateInfo');
    Route::post('user/password', 'api.UserController/updatePassword');
    
    Route::get('grade/list', 'api.GradeController/list');
    
    Route::get('subject/list', 'api.SubjectController/list');
    
    Route::post('wrong-question/upload', 'api.WrongQuestionController/upload');
    Route::get('wrong-question/list', 'api.WrongQuestionController/list');
    Route::get('wrong-question/detail', 'api.WrongQuestionController/detail');
    Route::post('wrong-question/update', 'api.WrongQuestionController/update');
    Route::post('wrong-question/delete', 'api.WrongQuestionController/delete');
    Route::post('wrong-question/mastered', 'api.WrongQuestionController/setMastered');
    Route::get('wrong-question/statistics', 'api.WrongQuestionController/statistics');
    
    Route::post('question/generate', 'api.QuestionController/generateFromWrong');
    Route::get('question/random', 'api.QuestionController/getRandom');
    Route::post('question/submit', 'api.QuestionController/submit');
    Route::get('question/history', 'api.QuestionController/history');
    
    Route::get('points/info', 'api.PointsController/info');
    Route::get('points/logs', 'api.PointsController/logs');
    Route::get('points/statistics', 'api.PointsController/statistics');
    
    Route::get('checkin/today', 'api.CheckinController/today');
    Route::post('checkin/checkin', 'api.CheckinController/checkin');
    Route::get('checkin/history', 'api.CheckinController/history');
    
    Route::get('collection/list', 'api.CollectionController/list');
    Route::post('collection/create', 'api.CollectionController/create');
    Route::post('collection/update', 'api.CollectionController/update');
    Route::post('collection/delete', 'api.CollectionController/delete');
    Route::post('collection/add', 'api.CollectionController/addQuestion');
    Route::post('collection/remove', 'api.CollectionController/removeQuestion');
    Route::get('collection/questions', 'api.CollectionController/questions');
    Route::get('collection/detail', 'api.CollectionController/detail');
    Route::post('collection/add-questions', 'api.CollectionController/addQuestions');
    Route::post('collection/remove-question', 'api.CollectionController/removeQuestionFromCollection');
});

Route::group('admin', function () {
    Route::get('login', 'admin.Login/index');
    Route::post('login/login', 'admin.Login/login');
    Route::get('login/logout', 'admin.Login/logout');
    
    Route::get('/', 'admin.Layout/index');
    Route::get('index', 'admin.Index/index');
    Route::get('welcome', 'admin.Index/welcome');
    
    Route::get('user', 'admin.UserController/index');
    Route::get('user/list', 'admin.UserController/list');
    Route::get('user/detail', 'admin.UserController/detail');
    Route::post('user/update-status', 'admin.UserController/updateStatus');
    Route::post('user/reset-password', 'admin.UserController/resetPassword');
    
    Route::get('wrong-question', 'admin.WrongQuestionController/index');
    Route::get('wrong-question/list', 'admin.WrongQuestionController/list');
    Route::get('wrong-question/detail', 'admin.WrongQuestionController/detail');
    Route::post('wrong-question/delete', 'admin.WrongQuestionController/delete');
    
    Route::get('points', 'admin.PointsController/index');
    Route::get('points/list', 'admin.PointsController/list');
    Route::get('points/statistics', 'admin.PointsController/statistics');
});
