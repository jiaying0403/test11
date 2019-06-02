<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 6:22
 */

namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;

class Tools extends Controller
{
    public function qrcode()
    {
        Vendor('phpqrcode.phpqrcode');
        $url = urldecode(input('url'));
        if (!empty($url)) {
            $object = new \QRcode();//实例化二维码类
            $errorCorrectionLevel = "L";
            $matrixPointSize = "4";
            $object->png($url, false, $errorCorrectionLevel, $matrixPointSize);
        } else {
            echo "未传参数";
        }
    }

    public function upload()
    {
        $file = request()->file('file');
        $host= input('server.REQUEST_SCHEME') . '://' . input('server.SERVER_NAME');
        if ($file) {
            $id = time();
            $info = $file->validate(['size' => 1023 * 1024 * 20, 'ext' => 'jpg,png,gif,gz,zip,rar,7z,apk,bmp'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'files');
            if ($info) {
                // 成功上传后 获取上传信息
                // 输出 jpg
//                echo $info->getExtension();
//                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getSaveName();
//                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();
                echo json_encode(["code" => 0, "msg" => "上传成功", "data" => ["src" =>  strtr($host.  DS . 'uploads' . DS . 'files'. DS .$info->getSaveName(), DS, '/')]]);
            } else {
                // 上传失败获取错误信息
                echo json_encode(["code" => 0, "msg" => "上传失败:" . $file->getError(), "data" => ["src" => ""]]);
            }
        }
    }
    //测试\
    function test()
    {
       // sendMail("2652648116@qq.com","卡密发货","这是你的卡密");

    }
}