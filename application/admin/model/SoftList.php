<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/4/20
 * Time: 22:14
 */

namespace app\admin\model;


use think\Model;

class SoftList extends Model{
    public function getexpireTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getstatusAttr($value,$data)
    {
        $status = [0=>'免费',1=>'收费',2=>'关闭验证'];
        return $status[$data['status']];
    }
    public function getversionAttr($value,$data)
    {
        return ($value==null)?"无":$value;
    }
}