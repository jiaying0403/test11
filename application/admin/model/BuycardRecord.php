<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/4
 * Time: 20:03
 */

namespace app\admin\model;


use think\Model;

class BuycardRecord extends Model{

    public function PayRecord()
    {
        return $this->belongsToMany('PayRecord');
    }

}