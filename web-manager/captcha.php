<?php
/**
 * 验证码生成器
 */
session_start();

// 验证码配置
$length = 4;
$width = 120;
$height = 40;
$fontSize = 20;

// 生成随机验证码
$code = '';
$chars = '0123456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // 去掉容易混淆的字符
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[rand(0, strlen($chars) - 1)];
}

// 保存到会话
$_SESSION['captcha_code'] = strtoupper($code);
$_SESSION['captcha_time'] = time();

// 创建图片
$image = imagecreatetruecolor($width, $height);

// 设置颜色
$bgColor = imagecolorallocate($image, 255, 255, 255);
$textColor = imagecolorallocate($image, 0, 0, 0);
$noiseColor = imagecolorallocate($image, 180, 180, 180);

// 填充背景
imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

// 添加噪点
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
}

// 添加干扰线
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), 
              rand(0, $width), rand(0, $height), $noiseColor);
}

// 绘制验证码文字
$x = ($width - $fontSize * $length) / 2;
$y = ($height + $fontSize) / 2;

for ($i = 0; $i < $length; $i++) {
    $angle = rand(-15, 15);
    imagettftext($image, $fontSize, $angle, 
                 $x + $i * $fontSize, $y, 
                 $textColor, 
                 __DIR__ . '/assets/fonts/arial.ttf', 
                 $code[$i]);
}

// 输出图片
header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
imagepng($image);
imagedestroy($image);
?>