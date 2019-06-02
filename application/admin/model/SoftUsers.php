<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/4/25
 * Time: 23:58
 */

namespace app\admin\model;


use think\Model;

class SoftUsers extends Model{
    public function getcreateTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getheartTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getoutTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function setoutTimeAttr($value)
    {
        $sjx=strtotime($value);
        return $sjx==0?$value:$sjx;
    }
    public function setmaccodeAttr($value)
    {
        return strtoupper($value);
    }
    public function setstatusAttr($value)
    {
        return $value=="正常"?0:1;
    }
}