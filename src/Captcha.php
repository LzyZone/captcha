<?php
namespace Captcha;

/**
 * 注意: 此函数仅在 PHP 编译时加入 freetype 支持时有效（--with-freetype-dir=DIR）
 * @package Captcha
 * @author GaryLee<321539047@qq.com>
 * @create 2024/12/3 14:03
 */
class Captcha {
    private $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    private $fontSize = 26;
    private $useNoise = true;
    private $useCurve = true;
    private $font = '';
    private $width = 250;
    private $height = 62;
    private $sessionName = 'captcha';

    public function __construct($config=null){
        $this->fontSize = $config['font_size'] ?? 26;
        $this->useNoise = $config['use_noise'] ?? true;
        $this->useCurve = $config['use_curve'] ?? true;
        $this->font = $config['font'] ?? __DIR__.'/fonts/fangzhengbiaoyasong.TTF';
        $this->width = $config['width'] ?? 250;
        $this->height = $config['height'] ?? 62;
        session_start();
    }

    public function create(){
        $im = imagecreate($this->width,$this->height);
        //白色背景
        imagecolorallocate($im, 255, 255, 255);
        //文本颜色
        $text_color = imagecolorallocate($im, mt_rand(1,200), mt_rand(0,255), mt_rand(0,255));

        list($text,$value) = $this->_operator();
        $_SESSION[$this->sessionName] = $value;

        $font_size = $this->fontSize;
        $font_width = imagefontwidth($font_size);
        $font_height = imagefontheight($font_size);

        while (true && $font_size > 0){
            $box = imageftbbox($font_size,0,$this->font,$text);
            $font_width = $box[2] - $box[0];
            $font_height = abs($box[7] - $box[1]);
            if($font_width > $this->width){
                $font_size--;
                continue;
            }
            break;
        }

        //随机干扰字符
        if($this->useNoise){
            for($i=0;$i<10;$i++){
                $_color = imagecolorallocatealpha($im, mt_rand(1,255), mt_rand(0,255), mt_rand(0,255),mt_rand(60,120));
                $_x = mt_rand(0,$this->width);
                $_y = mt_rand(0,$this->height);
                $_text = (string)substr($this->chars,mt_rand(0,strlen($this->chars)-1),1);
                imagettftext($im,mt_rand(8,16),mt_rand(0,180),$_x,$_y,$_color,$this->font,$_text);
            }
        }

        //验证码
        $x = mt_rand(0,$this->width-$font_width);
        $y = mt_rand($font_height,$this->height);
        imagettftext($im,$font_size,0,$x,$y ,$text_color,$this->font,$text);

        //干扰线
        if($this->useCurve){
            $curve_count = mt_rand(1,3);
            for($i=0; $i<$curve_count; ++$i){
                $x = rand(0, mt_rand($this->width/2,$this->width));
                $y = rand(0, mt_rand($this->height/2,$this->width));
                $x1 = rand(0, $this->width);
                $y1 = rand(0, $this->height);
                imageline ($im, $x, $y, $x1, $y1, imagecolorallocate($im, rand(0,255), rand(0,255), rand(0,255)));
            }
        }

        header("Content-Type: image/png");
        imagepng($im);
        imagedestroy($im);
    }

    /**
     * 验证
     * @param $code
     * @return bool
     */
    public function check($code){
        return $_SESSION[$this->sessionName] == $code;
    }

    private function _operator(){
        $operators = ['+','-','×','÷'];
        $calc = [];
        $calc[] = mt_rand(0,10);
        $calc[] = $operators[array_rand($operators)];
        $calc[] = mt_rand(0,10);
        $calc[] = ' = ?';
        $value = 0;
        list($n1,$operator,$n2) = $calc;
        switch ($operator){
            case '+':
                $value = $n1 + $n2;
                break;
            case '-':
                $value = $n1 - $n2;
                break;
            case '×':
                $value = $n1*$n2;
                break;
            case '÷':
                while ($n2 == 0 || ($n1 > 0 && $n1 % $n2 != 0)){
                    $n2 = mt_rand(1,10);
                    $calc[2] = $n2;
                }
                $value = $n1/$n2;
                break;
        }

        return [implode(' ',$calc),$value];
    }
}