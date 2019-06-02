<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/25
 * Time: 20:21
 */

namespace app\admin\model;
use think\Model;

class LoginRecord extends Model{
    public function getLoginTimeAttr($LoginTime,$data)
    {
        return date('Y-m-d H:i:s',$data['login_time']);
    }
}