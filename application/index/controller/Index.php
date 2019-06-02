<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use think\Db;




class Index extends Controller
{
    public function index()
    {
       // print_r($this->request->param());
    }
    public  function index2($name="张三")
    {
        //echo $name;
        print_r($this->request->param());
        $data=DB::name('user')->find();
        $this->assign('data',$data);
        $this->assign('name',$name);
        return $this->fetch();
    }
}
