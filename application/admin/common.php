<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/18
 * Time: 22:16
 */
use phpmailer\phpmailer;
define('SECRET', 'x$sfz321*%.:|_?*(');
function getUrl($url){
    $oCurl = curl_init();
    if(stripos($url,"https://")!==FALSE){
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
    }
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
    $sContent = curl_exec($oCurl);
    $aStatus = curl_getinfo($oCurl);
    curl_close($oCurl);
    if(intval($aStatus["http_code"])==200){
        return $sContent;
    }else{
        return false;
    }
}
//发送短信验证码
function SendMsg($phone)
{
    $code=mt_rand(999, 9999);
    $url="http://114.55.176.84/msg/HttpSendSM?account=nx-aimian&pswd=NXaimian0903&mobile=".$phone."&msg=您的验证码是".$code.",请不要告诉他人哦!&needstatus=false&product=";
    $res=getUrl($url);
    //20190325224906,0
    $arr=explode(",",$res);
    if(count($arr)==2){
        session('smsCode',$code);
        session('smsPhone',$phone);
        return true;
    }else{
        return false;
    }

}
function verifyMsg($phone,$code){
    if($phone==session('smsPhone') && $code ==session('smsCode')){
        return true;
    }else{
        return false;
    }
}
function guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000);
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);
        $uuid = chr(123)
            . substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12)
            . chr(125);
        return $uuid;
    }
}
function getIp()
{
    $ip=false;
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
        for ($i = 0; $i < count($ips); $i++) {
            if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}
//根据ip获取城市、网络运营商等信息,返回的是json
 function findCityByIp($ip){
    $data = file_get_contents('http://opendata.baidu.com/api.php?query='.$ip.'&co=&resource_id=6006&t=1412300361645&ie=utf8&oe=utf8&format=json&tn=baidu');
    $city=json_decode($data,$assoc=true);
     return count($city['data'])==0?"":$city['data'][0]['location'];
}

//获取用户浏览器类型
 function getBrowser(){
    $agent=$_SERVER["HTTP_USER_AGENT"];
    if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')) //ie11判断
        return "ie";
    else if(strpos($agent,'Firefox')!==false)
        return "firefox";
    else if(strpos($agent,'Chrome')!==false)
        return "chrome";
    else if(strpos($agent,'Opera')!==false)
        return 'opera';
    else if((strpos($agent,'Chrome')==false)&&strpos($agent,'Safari')!==false)
        return 'safari';
    else
        return 'unknown';
}

//获取网站来源
 function getFromPage(){
    return $_SERVER['HTTP_REFERER'];
}
function generate_password($length = 8 ) {
    $password ='';
    $chars ='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!';
    for ($i = 0; $i < $length; $i++ )
    {
        $password .=$chars[ mt_rand(0, strlen($chars) - 1) ];
    }
    return $password;
}
//发送邮件
function sendMail($Address,$Subject,$Body)
{

    Vendor('phpmailer.phpmailer');
    //实例化
    $mail = new PHPMailer(); //实例化
    try{
        //邮件调试模式
        $mail->SMTPDebug = 2;
        //设置邮件使用SMTP
        $mail->isSMTP();
        // 设置邮件程序以使用SMTP
        $mail->Host =config('mail.server');
        // 设置邮件内容的编码
        $mail->CharSet='UTF-8';
        // 启用SMTP验证
        $mail->SMTPAuth = true;
        // SMTP username
        $mail->Username = config('mail.acount');
        // SMTP password
        $mail->Password = config('mail.password');
        // 启用TLS加密，`ssl`也被接受
//            $mail->SMTPSecure = 'tls';
        // 连接的TCP端口
//            $mail->Port = 587;
        //设置发件人
        $mail->setFrom(config('mail.acount'), '网络验证');

        //  添加收件人1
        $mail->addAddress($Address, 'qq');     // Add a recipient
//            $mail->addAddress('ellen@example.com');               // Name is optional

//            收件人回复的邮箱
        $mail->addReplyTo(config('mail.acount'), 'yz');

//            抄送
//            $mail->addCC('cc@example.com');
//            $mail->addBCC('bcc@example.com');

        //附件
//            $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
//            $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

        //Content
        // 将电子邮件格式设置为HTML
        $mail->isHTML(true);
        $mail->Subject = $Subject;
        $mail->Body    = $Body;
//            $mail->AltBody = '这是非HTML邮件客户端的纯文本';
        $mail->send();
       // echo 'Message has been sent';

        $mail->isSMTP();
    }catch (Exception $e){
        echo 'Mailer Error: ' . $mail->ErrorInfo;
    }
}
//生成一个随机的订单号
function createOrderNo()
{
    return date('YmdHis').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
}
//执行JS,js文件名,执行函数(参数)
function v8JS($filename,$param)
{
    $file_path=ROOT_PATH . 'public' . DS . 'uploads'.DS."v8js". DS.$filename.".txt";
    if(file_exists($file_path)){
        $str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
    }else{
        return "算法文件不存在".$filename;
    }
    $v8 = new V8Js();

    /* basic.js */
    $JS = <<< EOT
$str
$param
EOT;

    try {
        $res=$v8->executeString($JS, 'basic.js');
        return $res;
    } catch (V8JsException $e) {
        //var_dump($e);
        return "v8js抛出异常,请检查参数";
    }
}
//curl
//get
function curl_get($url,$data="")
{
    $ch = curl_init();
    //设置抓取的url
    curl_setopt($ch, CURLOPT_URL, $url."?".$data);
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    //执行命令
    $data = curl_exec($ch);
    //关闭URL请求
    curl_close($ch);
    //显示获得的数据
    return $data;
}
//post
function curl_post($url, $data) {

    //初使化init方法
    $ch = curl_init();
    //指定URL
    curl_setopt($ch, CURLOPT_URL, $url);
    //设定请求后返回结果
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //声明使用POST方式来进行发送
    curl_setopt($ch, CURLOPT_POST, 1);
    //发送什么数据呢
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    //忽略证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //忽略header头信息
    curl_setopt($ch, CURLOPT_HEADER, 0);
    //设置超时时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    //发送请求
    $output = curl_exec($ch);
    //关闭curl
    curl_close($ch);
    //返回数据
    return $output;
}
function phpExcelList($field, $list, $title='文件')
{
    vendor('phpExcel.PHPExcel');
    $objPHPExcel = new \PHPExcel();
    $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel); //设置保存版本格式
    $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
    //设置宽度
        foreach ($field as $k => $v) {
            //设置列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($k)->setAutoSize(true);
            $objPHPExcel->getActiveSheet()->getColumnDimension($k)->setAutoSize(true);
        }
    foreach ($list as $key => $value) {
        foreach ($field as $k => $v) {
            if ($key == 0) {
                $objPHPExcel->getActiveSheet()->setCellValue($k . '1', $v[1]);
            }
            $i = $key + 2; //表格是从2开始的
            $objPHPExcel->getActiveSheet()->setCellValue($k . $i, $value[$v[0]]);
        }

    }
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
    header("Content-Type:application/force-download");
    header("Content-Type:application/vnd.ms-execl");
    header("Content-Type:application/octet-stream");
    header("Content-Type:application/download");;
    header('Content-Disposition:attachment;filename='.$title.'.xls');
    header("Content-Transfer-Encoding:binary");
//        $objWriter->save($title.'.xls');
    $objWriter->save('php://output');
}
?>
