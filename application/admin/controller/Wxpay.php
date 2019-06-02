<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 1:37
 */

namespace app\admin\controller;
use app\admin\model\PayRecord;
use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;


class Wxpay extends Controller{
    public function pay()
    {
        header("Content-type:text/html;charset=utf-8");
        $orderno=input("orderno");
        if(empty($orderno))
            return "未传入订单ID";
        $Record=PayRecord::getByorderno($orderno);
        //如果订单已经支付过了
        if($Record->status=="支付完成")
            return "订单已经支付过啦!";
        $this->assign('record',$Record);
        $this->assign('code_url',base64_decode(input("code_url")));
        $this->assign('check_url',url("admin/wxpay/getStatus",["orderno"=>$orderno]));
        if($Record->type=="账户充值"){
            $this->assign("jump","/profile.html");
        }else if($Record->type=="购买软件"){
            $this->assign("jump","/tk.html");
        }

        $this->assign('title',"微信支付");
        $this->assign('keywords',"个人信息");
        return $this->fetch('index/wxpay');
    }
    //查询支付状态
    public function getStatus()
    {
        //根据订单号查询
        $orderno=input('orderno');
        if(!isset($orderno))
            return json([ "msg"=>"未传入订单号","code"=>-2]);
        $Record=PayRecord::getByorderno($orderno);
        if(empty($Record)){
            return json([ "msg"=>"未查询到订单","code"=>-2]);
        }else{
            return json([ "status"=>$Record->status,"code"=>0,"msg"=>"成功","money"=>$Record->money,"orderno"=>$Record->orderno]);
        }
    }
}