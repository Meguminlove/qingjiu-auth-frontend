<?php
// api/mailer_functions.php
// 版权所有：小奏 (https://blog.mofuc.cn/)
// 本软件是小奏独立开发的开源项目，二次开发请务必保留原作者的版权信息。
// 博客: https://blog.mofuc.cn/
// B站: https://space.bilibili.com/63216596
// GitHub: https://github.com/Meguminlove/qingjiu-auth-frontend

require_once __DIR__ . '/phpmailer_library.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function send_smtp_email($settings, $to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        // $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_SERVER; // Enable verbose debug output for testing
        $mail->isSMTP();
        $mail->Host       = $settings['smtp_host'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $settings['smtp_user'] ?? '';
        $mail->Password   = $settings['smtp_pass'] ?? '';
        $mail->SMTPSecure = $settings['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $settings['smtp_port'] ?? 587;
        $mail->CharSet    = PHPMailer::CHARSET_UTF8;

        // Recipients
        $from_email = $settings['smtp_from_email'] ?? $settings['smtp_user'];
        $from_name = $settings['smtp_from_name'] ?? 'Mailer';
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($from_email, $from_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        // Return detailed error message
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

