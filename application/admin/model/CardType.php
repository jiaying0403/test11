<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/2
 * Time: 22:59
 */

namespace app\admin\model;


use think\Model;

class CardType extends Model{
    public function getAddTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
}