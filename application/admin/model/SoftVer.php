<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/4/23
 * Time: 19:43
 */

namespace app\admin\model;


use think\Model;

class SoftVer extends Model{
    public function getaddTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
}