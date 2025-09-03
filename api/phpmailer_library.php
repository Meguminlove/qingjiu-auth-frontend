<?php
// api/phpmailer_library.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

// --- [FIX] 增强文件存在性检查，防止白屏 ---
$required_files = [
    __DIR__ . '/Exception.php',
    __DIR__ . '/PHPMailer.php',
    __DIR__ . '/SMTP.php',
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        // Directly output a user-friendly error message instead of causing a fatal error (white screen).
        header('Content-Type: text/html; charset=utf-8');
        die("错误：邮件功能核心文件缺失。<br>请确认文件 '<b>" . basename($file) . "</b>' 已被正确上传到网站的 <b>/api/</b> 目录下。");
    }
    require_once $file;
}

// Conditionally include POP3 if it exists, as requested.
if (file_exists(__DIR__ . '/POP3.php')) {
    require_once __DIR__ . '/POP3.php';
}

// Use the 'use' keyword to import PHPMailer classes for any script that includes this loader.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\SMTP;

