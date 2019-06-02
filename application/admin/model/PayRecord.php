<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 11:11
 */

namespace app\admin\model;


use think\Model;

class PayRecord extends Model {
    public function getOrderTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getPayTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function gettypeAttr($value,$data)
    {
        $status = [1=>'账户充值',2=>'购买软件'];
        return $status[$data['type']];
    }
    public function getstatusAttr($value,$data)
    {
        //支付状态0成功1未支付2未知
        $status = [0=>'支付完成',1=>'未支付',2=>'未知状态'];
        return $status[$data['status']];
    }
    public function getPayModeAttr($value,$data)
    {
        $status = [1=>'支付宝',2=>'qq钱包',3=>'微信支付'];
        return $status[$data['pay_mode']];
    }
}