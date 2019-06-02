<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/4/2
 * Time: 22:39
 */

namespace app\admin\model;


use think\Model;

class MoneyList extends Model{
    public function getaddtimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function gettypeAttr($value,$data)
    {
        $status = [0=>'支出',1=>'收入',2=>'出售软件'];
        return $status[$data['type']];
    }
}