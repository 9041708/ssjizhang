<?php
namespace App\Service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

class Mailer
{
    public static function send(string $toEmail, string $toName, string $subject, string $html): bool
    {
        $driver = Config::get('mail.driver', 'mail');
        $fromEmail = Config::get('mail.from_email');
        $fromName = Config::get('mail.from_name');

        // 1) 默认使用 PHP 内置 mail()，类似你企业协作平台里的写法
        if ($driver === 'mail') {
            if (empty($toEmail)) {
                return false;
            }

            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers = [
                'From: =?UTF-8?B?' . base64_encode((string)$fromName) . '?= <' . $fromEmail . '>',
                'Reply-To: ' . $fromEmail,
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'MIME-Version: 1.0',
                'X-Mailer: PHP/' . phpversion(),
            ];

            return @mail($toEmail, $encodedSubject, $html, implode("\r\n", $headers));
        }

        // 2) 需要使用外部 SMTP 时，且已安装 PHPMailer
        if (!class_exists(PHPMailer::class)) {
            return false;
        }

        $mail = new PHPMailer(true);
        try {
            $host = Config::get('mail.host');
            $port = (int) Config::get('mail.port');
            $encryption = Config::get('mail.encryption');
            $username = Config::get('mail.username');
            $password = Config::get('mail.password');

            $mail->isSMTP();
            $mail->Host = $host;
            $mail->Port = $port;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->CharSet = 'UTF-8';

            if ($encryption === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($encryption === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom($fromEmail, (string)$fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;

            $mail->send();
            return true;
        } catch (MailException $e) {
            return false;
        }
    }
}
