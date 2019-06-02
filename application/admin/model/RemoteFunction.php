<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/6
 * Time: 22:15
 */

namespace app\admin\model;


use think\Model;

class RemoteFunction extends Model{
    public function getcreateTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
}