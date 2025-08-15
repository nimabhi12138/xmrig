<?php
session_start();

class Captcha {
    private $length = 4;
    private $width = 120;
    private $height = 40;
    private $fontSize = 20;
    
    public function generate() {
        // 生成简单的数字验证码
        $code = '';
        for ($i = 0; $i < $this->length; $i++) {
            $code .= rand(0, 9);
        }
        
        // 保存到session
        $_SESSION['captcha_code'] = $code;
        
        // 创建图像
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // 设置背景色（白色）
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $this->width, $this->height, $bgColor);
        
        // 添加一些干扰线（但不要太多）
        for ($i = 0; $i < 3; $i++) {
            $lineColor = imagecolorallocate($image, rand(200, 255), rand(200, 255), rand(200, 255));
            imageline($image, rand(0, $this->width), rand(0, $this->height), 
                     rand(0, $this->width), rand(0, $this->height), $lineColor);
        }
        
        // 绘制验证码文字（黑色，清晰）
        $textColor = imagecolorallocate($image, 0, 0, 0);
        $x = 15;
        for ($i = 0; $i < $this->length; $i++) {
            imagestring($image, 5, $x, 12, $code[$i], $textColor);
            $x += 25;
        }
        
        // 输出图像
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    
    public static function verify($input) {
        if (!isset($_SESSION['captcha_code'])) {
            return false;
        }
        
        $result = strtolower($input) === strtolower($_SESSION['captcha_code']);
        
        // 验证后清除验证码
        unset($_SESSION['captcha_code']);
        
        return $result;
    }
}