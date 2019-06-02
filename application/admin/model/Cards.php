<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/3
 * Time: 1:48
 */

namespace app\admin\model;


use think\Model;

class Cards extends Model{
    public function getAddTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
}