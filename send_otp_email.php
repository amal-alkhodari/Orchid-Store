
<?php

function send_verification_otp_email(
    string $toEmail,
    string $toName,
    string $otpCode,
    bool $isArabic = true
): bool {

    try {

        // تحميل مكتبة PHPMailer
        require_once __DIR__ . '/vendor/autoload.php';

        // تحميل إعدادات البريد
        $configPath = __DIR__ . '/mail_config.php';

        if (!file_exists($configPath)) {
            error_log('Orchid: mail_config.php not found');
            return false;
        }

        $config = require $configPath;

        $username = trim($config['username'] ?? '');
        $password = preg_replace(
            '/\s+/',
            '',
            (string) ($config['password'] ?? '')
        );

        $fromEmail = trim(
            $config['from_email'] ?? $username
        );

        $fromName = $config['from_name'] ?? 'Orchid Boutique';

        if ($username === '' || $password === '' || $fromEmail === '') {
            error_log('Orchid: SMTP configuration missing');
            return false;
        }

        // إنشاء رسالة البريد
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $config['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;

        $mail->SMTPSecure =
            \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port = (int) ($config['port'] ?? 587);

        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Timeout = 20;
        $mail->SMTPDebug = 0;

        // التحقق من شهادة الاتصال بشكل آمن
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ];

        // بيانات المرسل والمستقبل
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($fromEmail, $fromName);

        $mail->isHTML(true);

        $safeName = htmlspecialchars(
            $toName,
            ENT_QUOTES,
            'UTF-8'
        );

        $safeCode = htmlspecialchars(
            $otpCode,
            ENT_QUOTES,
            'UTF-8'
        );

        // عنوان الرسالة
        $mail->Subject = $isArabic
            ? 'رمز التحقق لإنشاء حسابك - Orchid Boutique'
            : 'Your Account Verification Code - Orchid Boutique';

        // محتوى الرسالة العربية
        if ($isArabic) {

            $mail->Body = "
            <!DOCTYPE html>
            <html lang='ar' dir='rtl'>
            <head>
                <meta charset='UTF-8'>
            </head>
            <body style='margin:0;padding:25px;background:#F4F1EF;
                         font-family:Arial,sans-serif;'>

                <div style='max-width:520px;margin:auto;
                            background:#ffffff;padding:35px;
                            border-radius:14px;text-align:center;'>

                    <h2 style='color:#652e70;'>
                        Orchid Boutique
                    </h2>

                    <h3 style='color:#46383B;'>
                        تأكيد إنشاء الحساب
                    </h3>

                    <p style='color:#555;font-size:16px;'>
                        مرحباً {$safeName}
                    </p>

                    <p style='color:#555;font-size:15px;'>
                        يسعدنا انضمامك إلى Orchid Boutique.
                        استخدمي رمز التحقق التالي لإكمال إنشاء حسابك:
                    </p>

                    <div style='margin:25px auto;padding:18px;
                                background:#F8F4F2;
                                border-radius:10px;'>

                        <h1 style='color:#652e70;
                                   letter-spacing:8px;
                                   font-size:34px;margin:0;'>
                            {$safeCode}
                        </h1>

                    </div>

                    <p style='color:#777;font-size:14px;'>
                        صلاحية الرمز 10 دقائق فقط.
                    </p>

                    <hr style='border:none;
                               border-top:1px solid #eee;
                               margin:25px 0;'>

                    <p style='color:#999;font-size:12px;'>
                        إذا لم تطلبي إنشاء حساب، يمكنك تجاهل هذه الرسالة.
                    </p>

                    <p style='color:#652e70;font-size:13px;'>
                        Orchid Boutique
                    </p>

                </div>
            </body>
            </html>
            ";

            $mail->AltBody =
                "مرحباً {$toName}، رمز التحقق لإنشاء حسابك في Orchid Boutique هو: {$otpCode}. صالح لمدة 10 دقائق.";

        } else {

            $mail->Body = "
            <!DOCTYPE html>
            <html lang='en' dir='ltr'>
            <head>
                <meta charset='UTF-8'>
            </head>
            <body style='margin:0;padding:25px;background:#F4F1EF;
                         font-family:Arial,sans-serif;'>

                <div style='max-width:520px;margin:auto;
                            background:#ffffff;padding:35px;
                            border-radius:14px;text-align:center;'>

                    <h2 style='color:#652e70;'>
                        Orchid Boutique
                    </h2>

                    <h3 style='color:#46383B;'>
                        Account Verification
                    </h3>

                    <p style='color:#555;font-size:16px;'>
                        Hello {$safeName}
                    </p>

                    <p style='color:#555;font-size:15px;'>
                        Welcome to Orchid Boutique.
                        Use the following code to complete your registration:
                    </p>

                    <div style='margin:25px auto;padding:18px;
                                background:#F8F4F2;
                                border-radius:10px;'>

                        <h1 style='color:#652e70;
                                   letter-spacing:8px;
                                   font-size:34px;margin:0;'>
                            {$safeCode}
                        </h1>

                    </div>

                    <p style='color:#777;font-size:14px;'>
                        Your code expires in 10 minutes.
                    </p>

                    <hr style='border:none;
                               border-top:1px solid #eee;
                               margin:25px 0;'>

                    <p style='color:#999;font-size:12px;'>
                        If you did not request an account,
                        you can ignore this email.
                    </p>

                    <p style='color:#652e70;font-size:13px;'>
                        Orchid Boutique
                    </p>

                </div>
            </body>
            </html>
            ";

            $mail->AltBody =
                "Hello {$toName}, your Orchid Boutique verification code is: {$otpCode}. Valid for 10 minutes.";
        }

        // إرسال الرسالة
        return $mail->send();

    } catch (\Throwable $e) {

        error_log(
            'Orchid OTP email error: ' . $e->getMessage()
        );

        return false;
    }
}
