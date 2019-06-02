<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/24
 * Time: 22:53
 */

namespace app\admin\model;
use think\Model;

class Users extends Model{
    public function getGroupIdAttr($value,$data)
    {
        $status = [-1=>'禁止访问',0=>'黑名单',1=>'荣誉会员',2=>'超级管理'];
        return $status[$data['group_id']];
    }
    public function getnameAttr($value,$data)
    {
        return $value==""?"未填写":$value;
    }
    public function getqqAttr($value,$data)
    {
        return $value==""?"未填写":$value;
    }
    public function getalipayAttr($value,$data)
    {
        return $value==""?"未填写":$value;
    }
    public function getregtimeAttr($value,$data)
    {
        return date('Y-m-d H:i:s',$value);
    }
    public function getAvatarAttr($value,$data)
    {
        return '\\uploads\\avatar\\'.$value;
    }
    //关联用户登录记录表
    public function loginlist()
    {
        //默认查询10条最近的登录记录
        return $this->hasMany('LoginRecord','uid','id')->order("id desc")->limit(10);
    }
    //关联消息表
    public function msgbox()
    {
        //默认查询10条最近的消息
        return $this->hasMany('msgBox', 'uid', 'id')->order("id desc")->limit(10);
    }
}