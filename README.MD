require_once __DIR__.'/vendor/autoload.php';

$captcha = new \Captcha\Captcha();
#输出验证码
$captcha->create();

#验证
$captcha->check()
