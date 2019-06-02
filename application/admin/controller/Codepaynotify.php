<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 22:49
 */

namespace app\admin\controller;
vendor("wxpay.log");
use app\admin\model\BuycardRecord;
use app\admin\model\PayRecord;
use app\admin\model\Users;
use app\admin\model\MsgBox;
use think\Session;
use think\Controller;

//初始化日志
$logHandler= new \CLogFileHandler("codepayLog-".date('Y-m-d').'.log');
$log = \Log::Init($logHandler, 15);
class Codepaynotify extends Controller{
    public function notify(){
        ksort($_POST); //排序post参数
        reset($_POST); //内部指针指向数组中的第一个元素
        $codepay_key=config('codepay.key'); //这是您的密钥
        \Log::DEBUG("callback:" . json_encode($_POST));
        $sign = '';//初始化
        foreach ($_POST AS $key => $val) { //遍历POST参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $sign .= "$key=$val"; //拼接为url参数形式
        }
        if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
            \Log::DEBUG("fail:" . json_encode($_POST));
            exit('fail');  //返回失败 继续补单
        } else { //合法的数据
            //业务处理
            $pay_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
            $money = (float)$_POST['money']; //实际付款金额
            $price = (float)$_POST['price']; //订单的原价
            $param = $_POST['param']; //自定义参数
            $pay_no = $_POST['pay_no']; //流水号

            //写入数据库
            $data=[
                'payno'=> $pay_no,
                'status'=>0,
                'pay_time'=>time()
            ];
            $record = PayRecord::getbyorderno($pay_id);
            $user=Users::get( $record->uid);
            if($record->status=="支付完成"){
                return json([ "msg"=>"用户".$user->username."订单已经倍标识过成功".$pay_id,"code"=>-2]);
            }
            if(!$record->save($data)){
                \Log::ERROR("用户".$user->username."订单成功状态保存失败".$pay_id );
                return json([ "msg"=>"用户".$user->username."订单成功状态保存失败".$pay_id,"code"=>-3]);
            }
            $user->money=$user->money+$money;
            if($user->save()){
                $u=new User();
                if($record->type=="账户充值")
                {
                    //用户余额添加成功,发送系统消息
                    $user->msgbox()->save(['msg'=>'您使用'.$record->payMode.'充值了'.$money.'元!','sendTime'=>time()]);
                    //添加到余额变动表
                    $u->addMoneyList($user,$record->money,1,"充值");
                    \Log::DEBUG("用户".$user->username.'您使用'.$record->payMode.'充值了'.$money.'元!');
                    return json([ "msg"=>"用户".$user->username.'您使用支付宝充值了'.$money.'元!',"code"=>-4]);
                }else if($record->type=="购买软件"){
                    $user->msgbox()->save(['msg'=>'有人购买了软件,余额+'.$record->money.'元!','sendTime'=>time()]);
                    \Log::DEBUG('写通知消息,余额+'.$record->money.'元!'.$record->id);
                    $br = BuycardRecord::getBypid($record->id);
                    \Log::DEBUG('创建br,余额+'.$record->money.'元!'.$br->sname."|卡类编号:".$br->cardId);
                    $u->addMoneyList($user,$record->money,2,$br->sname."|卡类编号:".$br->cardId);
                    \Log::DEBUG('添加变动记录,余额+'.$record->money.'元!');
                    //卡密发货
                    $card = new Card();
                    \Log::DEBUG('创建card,余额+'.$record->money.'元!');
                    $res=$card->sendCard($record->id,$record->orderno);
                    \Log::DEBUG('sendCard,余额+'.$record->orderno.'元!'.$record->id.",res=".$res);
                    return json([ "msg"=>'有人购买了软件,余额+'.$money.'元!',"code"=>-4]);
                }
                exit('success');//返回成功 不要删除哦
            }else{
                \Log::ERROR("用户".$user->username."余额添加失败".$pay_id );
                return json([ "msg"=>"用户".$user->username."余额添加失败".$pay_id,"code"=>-5]);
            }
            \Log::DEBUG("success:" . json_encode($_POST));
            exit('success'); //返回成功 不要删除哦
        }
    }

}