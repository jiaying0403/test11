<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/6
 * Time: 17:10
 */

namespace app\admin\model;


use think\Model;

class Variable extends Model{
    public function getcreateTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }

}