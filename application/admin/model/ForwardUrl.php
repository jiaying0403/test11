<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/7
 * Time: 15:49
 */

namespace app\admin\model;


use think\Model;

class ForwardUrl extends Model{
    public function getaddTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }

}