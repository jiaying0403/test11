<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 20:18
 */

namespace app\admin\controller;
use app\admin\model\BuycardRecord;
use app\admin\model\Cards;
use app\admin\model\PayRecord;
use app\admin\model\Users;
use app\admin\model\MsgBox;
use think\Session;

vendor("wxpay.WxPay#Api");
vendor("wxpay.WxPay#Notify");
vendor("wxpay.log");

//初始化日志
$logHandler= new \CLogFileHandler("wxpayLog-".date('Y-m-d').'.log');
$log = \Log::Init($logHandler, 15);
class Wxnotify extends \WxPayNotify{
    public function _initialize()
    {
        Logs::write(time().'访问'.$_SERVER['PHP_SELF']);
    }
    //查询订单
    public function Queryorder($transaction_id)
    {
        $input = new \WxPayOrderQuery();
        $input->SetTransaction_id($transaction_id);

        $config = new \WxPayConfig();
        $result = \WxPayApi::orderQuery($config, $input);
        \Log::DEBUG("query:" . json_encode($result));
        if(array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS")
        {
            //根据订单号来查询用户
            //写入数据库
            $data=[
                'payno'=> $result["transaction_id"],
                'status'=>0,
                'pay_time'=>time()
            ];
            $record = PayRecord::getbyorderno($result["out_trade_no"]);
            $user=Users::get( $record->uid);
            if($record->status=="支付完成"){
                return json([ "msg"=>"用户".$user->username."订单已经被标识过成功".$result["out_trade_no"],"code"=>-2]);
            }
            if(!$record->save($data)){
                \Log::ERROR("用户".$user->username."订单成功状态保存失败".$result["out_trade_no"] );
                return json([ "msg"=>"用户".$user->username."订单成功状态保存失败".$result["out_trade_no"],"code"=>-3]);
            }
            //return true;
            $user->money=$user->money+$record->money;
            if($user->save()){
                $u=new User();
                if($record->type=="账户充值")
                {
                    //用户余额添加成功,发送系统消息
                    $user->msgbox()->save(['msg'=>'您使用'.$record->payMode.'充值了'.$record->money.'元!','sendTime'=>time()]);
                    //添加到余额变动表
                    $u->addMoneyList($user,$record->money,1,"充值");
                    \Log::DEBUG("用户".$user->username.'您使用'.$record->payMode.'充值了'.$record->money.'元!');
                    return json([ "msg"=>"用户".$user->username.'您使用支付宝充值了'.$record->money.'元!',"code"=>-4]);
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
                    \Log::DEBUG('sendCard,余额+'.$record->money.'元!');
                    return json([ "msg"=>'有人购买了软件,余额+'.$record->money.'元!',"code"=>-4]);
                }


            }else{
                \Log::ERROR("用户".$user->username."余额添加失败".$result["out_trade_no"] );
                return json([ "msg"=>"用户".$user->username."余额添加失败".$result["out_trade_no"],"code"=>-5]);
            }
            //return true;
            \Log::DEBUG("用户".$user->username."余额添加成功".$result["out_trade_no"]);
            return json([ "msg"=>"成功","code"=>0]);
        }
        //return false;
        \Log::DEBUG("最后失败".json($result));
        return json([ "msg"=>"失败","code"=>-6]);

    }

    /**
     *
     * 回包前的回调方法
     * 业务可以继承该方法，打印日志方便定位
     * @param string $xmlData 返回的xml参数
     *
     **/
    public function LogAfterProcess($xmlData)
    {
        \Log::DEBUG("call back， return xml:" . $xmlData);
        return;
    }

    //重写回调处理函数
    /**
     * @param WxPayNotifyResults $data 回调解释出的参数
     * @param WxPayConfigInterface $config
     * @param string $msg 如果回调处理失败，可以将错误信息输出到该方法
     * @return true回调出来完成不需要继续回调，false回调处理未完成需要继续回调
     */
    public function NotifyProcess($objData, $config, &$msg)
    {
        $data = $objData->GetValues();
        //TODO 1、进行参数校验
        if(!array_key_exists("return_code", $data)
            ||(array_key_exists("return_code", $data) && $data['return_code'] != "SUCCESS")) {
            //TODO失败,不是支付成功的通知
            //如果有需要可以做失败时候的一些清理处理，并且做一些监控
            $msg = "异常异常";
            return false;
        }
        if(!array_key_exists("transaction_id", $data)){
            $msg = "输入参数不正确";
            return false;
        }

        //TODO 2、进行签名验证
        try {
            $checkResult = $objData->CheckSign($config);
            if($checkResult == false){
                //签名错误
                \Log::ERROR("签名错误...");
                return false;
            }
        } catch(Exception $e) {
            \Log::ERROR(json_encode($e));
        }

        //TODO 3、处理业务逻辑
        \Log::DEBUG("call back:" . json_encode($data));
        $notfiyOutput = array();


        //查询订单，判断订单真实性
        $this->Queryorder($data["transaction_id"]);
        return true;
    }
    public function index()
    {
        $config = new \WxPayConfig();
        \Log::DEBUG("begin notify");
        $this->Handle($config, false);
    }
}