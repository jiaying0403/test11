<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/3
 * Time: 6:05
 */

namespace app\admin\model;


use think\Model;

class CardRecord extends Model{
    public function getdepositTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getbeforTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getofterTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
}