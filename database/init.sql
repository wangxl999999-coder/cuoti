-- 错题记录系统数据库设计
-- 数据库: cuoti
-- 字符集: utf8mb4

-- 创建数据库
CREATE DATABASE IF NOT EXISTS cuoti DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE cuoti;

-- 年级表
CREATE TABLE IF NOT EXISTS grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT '年级名称',
    sort_order TINYINT UNSIGNED DEFAULT 0 COMMENT '排序',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1启用 0禁用',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='年级表';

-- 初始化年级数据
INSERT INTO grades (name, sort_order, status, create_time) VALUES
('一年级', 1, 1, UNIX_TIMESTAMP()),
('二年级', 2, 1, UNIX_TIMESTAMP()),
('三年级', 3, 1, UNIX_TIMESTAMP()),
('四年级', 4, 1, UNIX_TIMESTAMP()),
('五年级', 5, 1, UNIX_TIMESTAMP()),
('六年级', 6, 1, UNIX_TIMESTAMP()),
('初一', 7, 1, UNIX_TIMESTAMP()),
('初二', 8, 1, UNIX_TIMESTAMP()),
('初三', 9, 1, UNIX_TIMESTAMP()),
('高一', 10, 1, UNIX_TIMESTAMP()),
('高二', 11, 1, UNIX_TIMESTAMP()),
('高三', 12, 1, UNIX_TIMESTAMP());

-- 科目表
CREATE TABLE IF NOT EXISTS subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL COMMENT '科目名称',
    icon VARCHAR(255) DEFAULT '' COMMENT '图标',
    sort_order TINYINT UNSIGNED DEFAULT 0 COMMENT '排序',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1启用 0禁用',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='科目表';

-- 初始化科目数据
INSERT INTO subjects (name, sort_order, status, create_time) VALUES
('语文', 1, 1, UNIX_TIMESTAMP()),
('数学', 2, 1, UNIX_TIMESTAMP()),
('英语', 3, 1, UNIX_TIMESTAMP()),
('物理', 4, 1, UNIX_TIMESTAMP()),
('化学', 5, 1, UNIX_TIMESTAMP()),
('生物', 6, 1, UNIX_TIMESTAMP()),
('历史', 7, 1, UNIX_TIMESTAMP()),
('地理', 8, 1, UNIX_TIMESTAMP()),
('政治', 9, 1, UNIX_TIMESTAMP());

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    openid VARCHAR(100) DEFAULT '' COMMENT '微信openid',
    unionid VARCHAR(100) DEFAULT '' COMMENT '微信unionid',
    phone VARCHAR(20) DEFAULT '' COMMENT '手机号',
    nickname VARCHAR(100) DEFAULT '' COMMENT '昵称',
    avatar VARCHAR(255) DEFAULT '' COMMENT '头像',
    grade_id INT UNSIGNED DEFAULT 0 COMMENT '年级ID',
    points INT UNSIGNED DEFAULT 0 COMMENT '积分',
    total_points INT UNSIGNED DEFAULT 0 COMMENT '累计积分',
    password VARCHAR(255) DEFAULT '' COMMENT '密码（加密后）',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1正常 0禁用',
    last_login_time INT UNSIGNED DEFAULT 0 COMMENT '最后登录时间',
    last_login_ip VARCHAR(50) DEFAULT '' COMMENT '最后登录IP',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    UNIQUE KEY uk_openid (openid),
    UNIQUE KEY uk_phone (phone),
    INDEX idx_grade (grade_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 错题表
CREATE TABLE IF NOT EXISTS wrong_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    subject_id INT UNSIGNED DEFAULT 0 COMMENT '科目ID',
    grade_id INT UNSIGNED DEFAULT 0 COMMENT '年级ID',
    image_url VARCHAR(500) NOT NULL COMMENT '错题图片URL',
    question_text TEXT COMMENT '题目内容（OCR识别后）',
    answer_text TEXT COMMENT '答案内容',
    analysis TEXT COMMENT '解析',
    knowledge_points VARCHAR(500) DEFAULT '' COMMENT '知识点（逗号分隔）',
    difficulty TINYINT UNSIGNED DEFAULT 1 COMMENT '难度 1简单 2中等 3困难',
    source VARCHAR(100) DEFAULT '' COMMENT '题目来源',
    error_count INT UNSIGNED DEFAULT 1 COMMENT '做错次数',
    is_mastered TINYINT UNSIGNED DEFAULT 0 COMMENT '是否掌握 1是 0否',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1正常 0删除',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_user (user_id),
    INDEX idx_subject (subject_id),
    INDEX idx_grade (grade_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='错题表';

-- 试题表（AI生成的举一反三试题）
CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wrong_question_id INT UNSIGNED DEFAULT 0 COMMENT '关联错题ID（0表示通用试题）',
    subject_id INT UNSIGNED DEFAULT 0 COMMENT '科目ID',
    grade_id INT UNSIGNED DEFAULT 0 COMMENT '年级ID',
    question_text TEXT NOT NULL COMMENT '题目内容',
    option_a VARCHAR(500) DEFAULT '' COMMENT '选项A',
    option_b VARCHAR(500) DEFAULT '' COMMENT '选项B',
    option_c VARCHAR(500) DEFAULT '' COMMENT '选项C',
    option_d VARCHAR(500) DEFAULT '' COMMENT '选项D',
    correct_answer CHAR(10) DEFAULT '' COMMENT '正确答案（多选用逗号分隔）',
    answer_text TEXT COMMENT '答案解析',
    knowledge_points VARCHAR(500) DEFAULT '' COMMENT '知识点（逗号分隔）',
    difficulty TINYINT UNSIGNED DEFAULT 1 COMMENT '难度 1简单 2中等 3困难',
    question_type TINYINT UNSIGNED DEFAULT 1 COMMENT '题型 1单选 2多选 3填空 4解答',
    is_ai_generated TINYINT UNSIGNED DEFAULT 1 COMMENT '是否AI生成 1是 0否',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1正常 0删除',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_wrong_question (wrong_question_id),
    INDEX idx_subject (subject_id),
    INDEX idx_grade (grade_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='试题表';

-- 检测记录表
CREATE TABLE IF NOT EXISTS exam_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    question_id INT UNSIGNED NOT NULL COMMENT '试题ID',
    wrong_question_id INT UNSIGNED DEFAULT 0 COMMENT '关联错题ID',
    user_answer VARCHAR(500) DEFAULT '' COMMENT '用户答案',
    correct_answer VARCHAR(500) DEFAULT '' COMMENT '正确答案',
    is_correct TINYINT UNSIGNED DEFAULT 0 COMMENT '是否正确 1是 0否',
    points_earned INT UNSIGNED DEFAULT 0 COMMENT '获得积分',
    used_time INT UNSIGNED DEFAULT 0 COMMENT '用时（秒）',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    INDEX idx_user (user_id),
    INDEX idx_question (question_id),
    INDEX idx_wrong_question (wrong_question_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='检测记录表';

-- 积分记录表
CREATE TABLE IF NOT EXISTS points_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    points INT NOT NULL COMMENT '积分变动（正为加，负为减）',
    balance INT UNSIGNED NOT NULL COMMENT '变动后余额',
    type TINYINT UNSIGNED DEFAULT 1 COMMENT '类型 1答题正确 2连续答对 3签到 4其他',
    description VARCHAR(255) DEFAULT '' COMMENT '描述',
    related_id INT UNSIGNED DEFAULT 0 COMMENT '关联ID（如检测记录ID）',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_create_time (create_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='积分记录表';

-- 错题本表（用户收藏的错题集合）
CREATE TABLE IF NOT EXISTS question_collections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    name VARCHAR(100) NOT NULL COMMENT '错题本名称',
    description VARCHAR(255) DEFAULT '' COMMENT '描述',
    question_count INT UNSIGNED DEFAULT 0 COMMENT '题目数量',
    cover_image VARCHAR(255) DEFAULT '' COMMENT '封面图片',
    is_default TINYINT UNSIGNED DEFAULT 0 COMMENT '是否默认错题本 1是 0否',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1正常 0删除',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='错题本表';

-- 错题本关联表
CREATE TABLE IF NOT EXISTS collection_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    collection_id INT UNSIGNED NOT NULL COMMENT '错题本ID',
    wrong_question_id INT UNSIGNED NOT NULL COMMENT '错题ID',
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    add_time INT UNSIGNED DEFAULT 0 COMMENT '添加时间',
    UNIQUE KEY uk_collection_question (collection_id, wrong_question_id),
    INDEX idx_collection (collection_id),
    INDEX idx_wrong_question (wrong_question_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='错题本关联表';

-- 管理员表
CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL COMMENT '用户名',
    password VARCHAR(255) NOT NULL COMMENT '密码（加密后）',
    nickname VARCHAR(50) DEFAULT '' COMMENT '昵称',
    avatar VARCHAR(255) DEFAULT '' COMMENT '头像',
    role TINYINT UNSIGNED DEFAULT 1 COMMENT '角色 1超级管理员 2普通管理员',
    status TINYINT UNSIGNED DEFAULT 1 COMMENT '状态 1正常 0禁用',
    last_login_time INT UNSIGNED DEFAULT 0 COMMENT '最后登录时间',
    last_login_ip VARCHAR(50) DEFAULT '' COMMENT '最后登录IP',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    update_time INT UNSIGNED DEFAULT 0 COMMENT '更新时间',
    UNIQUE KEY uk_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 初始化管理员账号（密码: admin123，需使用password_hash加密）
INSERT INTO admins (username, password, nickname, role, status, create_time) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '超级管理员', 1, 1, UNIX_TIMESTAMP());

-- 签到记录表
CREATE TABLE IF NOT EXISTS checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL COMMENT '用户ID',
    checkin_date DATE NOT NULL COMMENT '签到日期',
    points INT UNSIGNED DEFAULT 0 COMMENT '获得积分',
    continuous_days INT UNSIGNED DEFAULT 1 COMMENT '连续签到天数',
    create_time INT UNSIGNED DEFAULT 0 COMMENT '创建时间',
    UNIQUE KEY uk_user_date (user_id, checkin_date),
    INDEX idx_user (user_id),
    INDEX idx_date (checkin_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='签到记录表';
