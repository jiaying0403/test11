<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 9:54
 */

namespace app\admin\controller;
use app\admin\model\PayRecord;
use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;



class Pay extends Controller{
    //生成订单
    public function order()
    {
        $user=Session::get('user');
        if(empty($user))
            return json([ "msg"=>"登录超时","code"=>-1]);
        if(!empty($user))
            $user=Users::get($user->id);
        if(input('money')<=0)
            return json([ "msg"=>"支付金额错误","code"=>-2]);
        //生成订单号
        $orderNo=createOrderNo();
        $link="";
        $pay_mode=3;
        //如果选择了码支付
        if(input("payType")=="alipay" || input("payType")=="qq" )
        {
            $codepay_id=config('codepay.id');//这里改成码支付ID
            $codepay_key=config('codepay.key'); //这是您的通讯密钥
            $data = array(
                "id" => $codepay_id,//你的码支付ID
                "pay_id" => $orderNo, //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
                "type" =>1,//1支付宝支付 3微信支付 2QQ钱包
                "price" => input('money'),//金额100元
                "param" => $orderNo,//自定义参数
                "notify_url"=>config('codepay.notify_url'),//通知地址
                "return_url"=>config('codepay.return_url'),//跳转地址
            ); //构造需要传递的参数
        }
        //微信通知URL
        $Notify_url=config('notify_url');
        switch (input("payType")) {
            case "weixin":
                //echo "你选择了微信支付";
                require VENDOR_PATH.'/wxpay/WxPay.Api.php'; //引入微信支付
                require VENDOR_PATH.'/wxpay/WxPay.NativePay.php'; //引入微信支付
                $input = new \WxPayUnifiedOrder();//统一下单
                $notify = new \NativePay();
                $config = new \WxPayConfig();//配置参数
                $input->SetBody("用户".$user->username."账户充值");
                $input->SetAttach($orderNo);
                $input->SetOut_trade_no($orderNo);
                $input->SetTotal_fee(input('money')*100);
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetGoods_tag($user->username);
                $input->SetNotify_url($Notify_url);
                $input->SetTrade_type("NATIVE");
                $input->SetProduct_id("00000001");
                $result = $notify->GetPayUrl($input);
                if($result['result_code']=='SUCCESS' && $result['return_code']=='SUCCESS') {
                    $link =url('admin/wxpay/pay',['code_url'=>base64_encode($result["code_url"]),'orderno'=>$orderNo]);
                }else{
                    return json([ "msg"=>"微信支付参数错误","code"=>-4]);
                }
                break;
            case "alipay":
                //echo "你选择了支付宝支付";
                $data['type']=1;
                $pay_mode=1;
                break;
            case "qq":
               // echo "你选择了qq支付";
                $data['type']=2;
                $pay_mode=2;
                break;
            default:
                return json([ "msg"=>"未选择支付方式","code"=>-3]);
        }
        //码支付生成支付链接
        if(input("payType")=="alipay" || input("payType")=="qq" ) {
            ksort($data); //重新排序$data数组
            reset($data); //内部指针指向数组中的第一个元素

            $sign = ''; //初始化需要签名的字符为空
            $urls = ''; //初始化URL参数为空

            foreach ($data AS $key => $val) { //遍历需要传递的参数
                if ($val == ''||$key == 'sign') continue; //跳过这些不参数签名
                if ($sign != '') { //后面追加&拼接URL
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
                $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

            }
            $query = $urls . '&sign=' . md5($sign .$codepay_key); //创建订单所需的参数
            if($this->request->scheme()=="http"){
                $link = "http://api2.yy2169.com:52888/creat_order/?{$query}"; //支付页面
            }else{
                $link = "https://codepay.fateqq.com/creat_order/?{$query}"; //支付页面
            }
        }
        $payArr=array(
            'uid'=>$user->id,
            'orderno'=>$orderNo,
            'money'=>input('money'),
            'pay_mode'=>$pay_mode,
            'status'=>1,//1 未支付订单
            'type'=>1,//用户余额充值
            'order_time'=>time()

        );
        //写到数据库
        if(PayRecord::create($payArr)){
            //返回支付链接
            return json([ "msg"=>"订单创建完成","code"=>0,"link"=>$link]);
        }else{
            return json([ "msg"=>"创建订单失败","code"=>-4]);
        }

    }
}