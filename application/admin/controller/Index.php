<?php
namespace app\admin\controller;

use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;


class Index extends Controller
{
    public function index()
    {
        if (cookie('autoLogin') == 1) {
            //自动登录
            $user = Users::getByUsername(cookie('username'));
            if (!empty($user)) {
                if (cookie('token') == md5($user->username . $user->user_id . "" . SECRET))
                    Session::set('user', $user);
            }
        }
        $user = Session::get('user');
        if (!empty($user))
            $user = Users::get($user->id);
        $this->assign('user', $user);
        $this->assign('title', "几时网络验证");
        $this->assign('keywords', "几时网络验证");
        return $this->fetch();
    }

    public function doLogin()
    {
        $captcha = input('captcha');
        if (!captcha_check($captcha)) {
            //验证失败
            return json(["msg" => "验证码错误", "code" => -1]);
        };
        $request = Request::instance();
        $param = $request->only('username,password');
        $param['password'] = md5(md5($param['password']));
        //检查用户名 和 密码是否匹配
        $user = Users::get($param);
        if (empty($user)) {
            return json(["msg" => "用户名或者密码错误", "code" => -2]);
        } else {
            Session::set('user', $user);
            if (input('autoLogin') == "on") {
                cookie('username', $user->username, 86400);//保持1天
                cookie('autoLogin', 1, 86400);//保持1天
                cookie('token', md5($user->username . $user->user_id . "" . SECRET), 86400);//保持1天
            }
            //记录登录IP信息
            $ip = getIp();
            $city = findCityByIp($ip);
            $location = $city;
            $user->loginlist()->save(['ip' => $ip, 'location' => $location, 'login_time' => time()]);
            return json(["msg" => "登录成功" . $user->username, "code" => 0]);
        }
    }

    //注册
    public function doRegister()
    {
        $request = Request::instance();
        $param = $request->only('username,password,email,phone');
        $param['password'] = md5(md5($param['password']));
        $param['user_id'] = md5(guid());
        $param['regtime'] = time();
        //检查验证码是否正确
        //if(!verifyMsg($param['phone'],input('code')))
        // return json(["msg" => "手机验证码错误", "code" => -4]);
        //先查询数据库是否存在该用户
        if (!empty(Users::getByUsername($param['username']))) {
            return json(["msg" => "用户名已经存在", "code" => -1]);
        }
        //手机是否存在
        if (!empty(Users::getByPhone($param['phone']))) {
            return json(["msg" => "手机已经存在", "code" => -2]);
        }
        $user = Users::create($param);
        if (!empty($user->id)) {
            $user = Users::get($user->id);
            Session::set('user', $user);
            cookie('username', null);
            cookie('autoLogin', null);
            cookie('token', null);
            //添加一条系统消息
            $user->msgbox()->save(['msg' => '非常感谢您的注册,请遵守国家法律法规,勿发布违规内容！', 'sendTime' => time()]);
            return json(["msg" => "注册成功", 'uid' => $user->id, "code" => 0]);
        } else {
            return json(["msg" => "注册失败", "code" => -3]);
        }

    }

    //发送注册短信
    public function doSendMsg()
    {
        $captcha = input('captcha');
        if (!captcha_check($captcha)) {
            //验证失败
            return json(["msg" => "图片验证码错误", "code" => -1]);
        };
        //检查手机号是否注册
        if (!empty(Users::getByPhone(input('phone')))) {
            return json(["msg" => "手机已经存在", "code" => -2]);
        }
        //发送短信验证码
        return SendMsg(input('phone')) ? json(["msg" => "发送成功", "code" => 0]) : json(["msg" => "发送失败", "code" => 0]);

    }

    public function doSendMsg1()
    {
        $captcha = input('captcha');
        if (!captcha_check($captcha)) {
            //验证失败
            return json(["msg" => "图片验证码错误", "code" => -1]);
        };
        //检查手机号是否注册
        if (empty(Users::getByPhone(input('phone')))) {
            return json(["msg" => "手机号未注册", "code" => -2]);
        }
        //发送短信验证码
        return SendMsg(input('phone')) ? json(["msg" => "发送成功", "code" => 0]) : json(["msg" => "发送失败", "code" => 0]);

    }

    //登出
    public function logout()
    {
        cookie('username', null);
        cookie('autoLogin', null);
        cookie('token', null);
        session('user', null);
        return $this->success('退出成功', "../../index");//跳转到登录页面
    }

    public function index_v1()
    {
        $this->assign('title', "首页");
        $this->assign('keywords', "首页");
        return $this->fetch();
    }

    //用户修改头像
    public function form_avatar()
    {
        $this->assign('title', "修改头像");
        $this->assign('keywords', "修改头像");
        return $this->fetch();
    }

    public function Update_avatar()
    {
        $file = request()->file('__avatar1');
        if ($file) {
            $id = time();
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads', "avatar" . DS . $id . ".jpg", true);
            if ($info) {
                // 成功上传后 获取上传信息
                // 输出 jpg
//                echo $info->getExtension();
//                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getSaveName();
//                // 输出 42a79759f284b767dfcb2a0197904287.jpg
//                echo $info->getFilename();
                $user = session('user');
                $user['avatar'] = $info->getFilename();
                $user->save();
                return json(["success" => true, "avatarUrls" => array($info->getSaveName())]);
            } else {
                // 上传失败获取错误信息
                return json(["success" => false, "msg" => $file->getError()]);
            }
        }

    }

    function APIDOC()
    {
        return "等待添加";
    }
    function FAQ()
    {
        $this->assign('title', "FAQ");
        $this->assign('keywords', "FAQ");
        return $this->fetch();
    }
}
