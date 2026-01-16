<?php
// 简单数字图形验证码
// 使用与主系统相同的 Session 名称，保证验证码可在登录/注册页面校验
if (session_status() === PHP_SESSION_NONE) {
    session_name('SSJIZHANGSESSID');
    session_start();
}

$scene = $_GET['scene'] ?? 'default';
$scene = in_array($scene, ['login', 'register'], true) ? $scene : 'default';

$length = random_int(4, 6);
$code = '';
for ($i = 0; $i < $length; $i++) {
    $code .= (string)random_int(0, 9);
}

// 不同场景使用不同的 Session 键，避免串用
$key = 'captcha_code_' . $scene;
$_SESSION[$key] = $code;

// 创建图片
$width = 110;
$height = 40;
$image = imagecreatetruecolor($width, $height);

$bgColor = imagecolorallocate($image, 245, 247, 250);
$borderColor = imagecolorallocate($image, 200, 210, 220);
$textColor = imagecolorallocate($image, 50, 50, 50);

imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);

// 简单干扰线
for ($i = 0; $i < 4; $i++) {
    $noiseColor = imagecolorallocate($image, random_int(180, 220), random_int(190, 230), random_int(200, 240));
    imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $noiseColor);
}

// 写入验证码文本
$fontSize = 5; // 内置字体
$charWidth = imagefontwidth($fontSize);
$charHeight = imagefontheight($fontSize);
$totalTextWidth = $charWidth * strlen($code);
$x = (int)(($width - $totalTextWidth) / 2);
$y = (int)(($height - $charHeight) / 2);

for ($i = 0; $i < strlen($code); $i++) {
    $offsetY = random_int(-2, 2);
    imagestring($image, $fontSize, $x + $i * $charWidth, $y + $offsetY, $code[$i], $textColor);
}

header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

imagepng($image);
imagedestroy($image);
