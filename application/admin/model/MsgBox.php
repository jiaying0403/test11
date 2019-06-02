<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 20:42
 */

namespace app\admin\model;
use think\Model;

class MsgBox  extends Model{
    public function getsendTimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function gettypeAttr($value,$data)
    {
        $status = [1=>'系统消息'];
        return $status[$data['type']];
    }

}