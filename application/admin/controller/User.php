<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/3/26
 * Time: 18:09
 */

namespace app\admin\controller;
use app\admin\model\MoneyList;
use app\admin\model\SoftList;
use app\admin\model\SoftUsers;
use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;




class User extends Controller
{
    public function profile(){
        $user=Session::get('user');
        if(empty($user))
            {$this->assign('title',"超时");$this->assign('keywords',"超时"); return $this->fetch('index/timeout');}
        if(!empty($user))
            $user=Users::get($user->id);
        $softTotal=SoftList::where(['uid'=>$user->id])->count();
        $userList=SoftUsers::where(['authorid'=>$user->id])->select();
        $userTotal=0;
        $onlineTotal=0;
        $timeoutTotal=0;
        foreach($userList as $ul)
        {
            $userTotal+=1;
            if(strtotime($ul['heart_time'])>time()-61)$onlineTotal+=1;
            if(strtotime($ul['out_time'])<time())$timeoutTotal+=1;
        }
        //今日收入
        $moneyList=MoneyList::where([
            "uid"=>$user->id,
            "type"=>['in',array(1,2)]
        ])->select();
        //            "add_time"=>['>=',mktime(0,0,0,date('m'),date('d'),date('Y'))]
        $sumMoney=0;
        $todayMoney=0;
        foreach ($moneyList as $ml)
        {
           if(strtotime($ml['add_time'])>=mktime(0,0,0,date('m'),date('d'),date('Y')))$todayMoney+=$ml['money'];
            $sumMoney+=$ml['money'];
        }
        $this->assign('sumMoney',$sumMoney);
        $this->assign('todayMoney',$todayMoney);
        $this->assign('timeoutTotal',$timeoutTotal);
        $this->assign('onlineTotal',$onlineTotal);
        $this->assign('softTotal',$softTotal);
        $this->assign('userTotal',$userTotal);
        $this->assign('user',$user);
        $this->assign('location',$user->loginlist);
        $this->assign('msg',$user->msgbox);
        $this->assign('title',$user->username." - 用户信息");
        $this->assign('keywords',$user->username. "- 个人信息");
        return $this->fetch('index/profile');
    }
    public function changePwd(){
        $user=Session::get('user');
        if(empty($user))
            {$this->assign('title',"超时");$this->assign('keywords',"超时"); return $this->fetch('index/timeout');}
        if(!empty($user))
            $user=Users::get($user->id);
        $this->assign('user',$user);
        $this->assign('title',$user->username." - 修改密码");
        $this->assign('keywords',$user->username. "- 修改密码");
        return $this->fetch('index/change_pwd');
    }
    public function doChangePwd(){
        $user=Session::get('user');
        if(empty($user))
            {$this->assign('title',"超时");$this->assign('keywords',"超时"); return $this->fetch('index/timeout');}
        if(!empty($user))
            $user=Users::get($user->id);
        if(md5(md5(input('oldPwd')))!=$user->password)
            return json( [ "msg"=>"旧密码错误","code"=>-1]);
        $user->password=md5(md5(input('newPwd')));
        $user->save();
        return json( [ "msg"=>"密码修改成功","code"=>0]);
    }
    //用户修改资料
    public function changeInfo(){
        $user=Session::get('user');
        if(empty($user))
            {$this->assign('title',"超时");$this->assign('keywords',"超时"); return $this->fetch('index/timeout');}
        if(!empty($user))
            $user=Users::get($user->id);
        $this->assign('user',$user);
        $this->assign('title',$user->username." - 修改密码");
        $this->assign('keywords',$user->username. "- 修改密码");
        return $this->fetch('index/change_Info');
    }
    public function doChangeInfo(){
        $user=Session::get('user');
        if(empty($user))
            {$this->assign('title',"超时");$this->assign('keywords',"超时"); return $this->fetch('index/timeout');}
        if(!empty($user))
            $user=Users::get($user->id);
        if(md5(md5(input('password')))!=$user->password)
            return json( [ "msg"=>"密码错误","code"=>-1]);
        $user->name=input('name')==""?$user->name:input('name');
        $user->email=input('email')==""?$user->email:input('email');
        $user->qq=input('qq')==""?$user->qq:input('qq');
        $user->alipay=input('alipay')==""?$user->alipay:input('alipay');
        $user->save();
        return json( [ "msg"=>"资料修改成功","code"=>0]);
    }
    //忘记密码
    public function forgotpassword(){
        $this->assign('title'," 找回密码");
        $this->assign('keywords', "找回密码");
        return $this->fetch('index/forgotpassword');
    }
    public function doforgotpassword(){
        //检查验证码是否正确
        if(!verifyMsg(input('phone'),input('code')))
            return json(["msg" => "手机验证码错误", "code" => -4]);
        //手机是否存在
        $user=Users::getByPhone(input('phone'));
        if(empty($user)){
            return json(["msg" => "该手机未注册", "code" => -2]);
        }
        $user->password=md5(md5(input('password')));
        if($user->save()){
            return json( [ "msg"=>"密码找回成功","password"=>input('password'),'username'=>$user->username,"code"=>0]);
        }else{
            return json( [ "msg"=>"密码找回失败","code"=>-1]);
        }
    }
    public function resetKey(){
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
        if(!empty($user))
            $user=Users::get($user->id);
        $user_id=strtoupper( md5(guid()));
        $user->user_id=$user_id;
        if($user->save()){
            $user->msgbox()->save(['msg'=>'您将Secret Key重置为:'.$user_id,'sendTime'=>time()]);
            return json( [ "msg"=>"重置成功","code"=>0,'SecretKey'=> $user_id]);
        }else{
            return json( [ "msg"=>"重置失败","code"=>-1]);
        }
    }
    //充值
    public function payPage()
    {
        $this->assign('title'," 用户充值");
        $this->assign('keywords',"用户充值");
        return $this->fetch('index/payPage');
    }
    //提现
    public function txPage()
    {
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
        if(!empty($user))
            $user=Users::get($user->id);
        $this->assign('user',$user);
        $this->assign('title',"用户提现");
        $this->assign('keywords',"用户提现");
        return $this->fetch('index/txPage');
    }
    //添加余额记录,扣除手续费
    /*
     * $money 账变金额
     * $type 0 支出 1 收入
     * $info 账变说明
     */
    public function addMoneyList($user,$money,$type,$info)
    {
        //插入记录
        //只扣手续费
        //手续费比例
        $scale=config('pay_scale');
        $charge=$money*$scale;
        $user->money=$user->money-$charge;
        $data=[
            "money"=>$money,
            "after"=>$user->money,
            "uid"=>$user->id,
            "type"=>$type,
            "info"=>$info,
            "add_time"=>time(),
            "charge"=>$charge //手续费
        ];
        if(MoneyList::create($data))
        {
            //变更用户的余额
            $user->save();
            return true;
        }
        else
        {
            return false;
        }
    }
    //获取余额记录
    public function getMoneyList()
    {
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
         $pageSize = input('limit');
        //查询条件
        $start=strtotime(input('start'));
        $end=strtotime(input('end'));
        $type=input('type');
        $where = [
            "uid"=>$user->id
        ];
        $superid=input('superid');
        if($superid>0){
            //检查用户是不是超级管理
            if($user->group_id!='超级管理')
            {
                $where['uid']=$superid;
            }
        }
        if(!empty($start) && !empty($end)){
            $where['add_time']=['between',[$start,$end]];
        }
        if(isset($type) && $type!="")
        {
            $where['type']=['=',$type];
            if($type==1)//收入类型
            {
                $where['type']=['in',array(1,2)];
            }
        }
        $list=MoneyList::where($where)->order('id desc')->limit(input('offset'),$pageSize)->select();
        $total= MoneyList::where($where)->count();
        return json(["total"=>$total,"rows"=>$list]);
    }
    public function moneyList(){
        $superid=input('superid');
        if(empty($superid))$superid=0;
        $this->assign('superid',$superid);
        $this->assign('title',"账变记录");
        $this->assign('keywords',"余额记录");
        return $this->fetch('index/moneylist');
    }
    //获取余额记录GM
    public function getMoneyListGM()
    {
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
        $pageSize = input('limit');
        //查询条件
        $start=strtotime(input('start'));
        $end=strtotime(input('end'));
        $type=input('type');
        $where = [
            "info"=>'提现-审核中'
        ];
        if(!empty($start) && !empty($end)){
            $where['add_time']=['between',[$start,$end]];
        }
        if(isset($type) && $type!="")
        {
            $where['type']=['=',$type];
            if($type==1)//收入类型
            {
                $where['type']=['in',array(1,2)];
            }
        }
        $list=MoneyList::where($where)->order('id desc')->limit(input('offset'),$pageSize)->select();
        $total= MoneyList::where($where)->count();
        return json(["total"=>$total,"rows"=>$list]);
    }
    public function moneylistGM()
    {
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
        if(!empty($user))
            $user=Users::get($user->id);
        if($user->group_id!='超级管理')return "非法访问".$user->group_id;
        $this->assign('title',"提现管理");
        $this->assign('keywords',"提现管理");
        return $this->fetch('index/moneylistGM');
    }
    //审核提现
    public function checkTx()
    {
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
        if(!empty($user))
            $user=Users::get($user->id);
        if($user->group_id!='超级管理')return "非法访问".$user->group_id;
        $id=input('id');
        if($id<0)return "用户ID非法";
        $where = [
            'm.id' =>$id,
        ];
        $ml=new MoneyList();
        $list=$ml->alias("m")
            ->join("users u", "u.id=m.uid","LEFT")
            ->field("m.*,u.username,u.alipay,u.name,u.qq")
            ->where($where)
            ->find();
        if(!$list)return "没有找到该提现记录";
        $this->assign('user',$list);
        $this->assign('title',"审核提现");
        $this->assign('keywords',"审核提现");
        return $this->fetch('index/checkTx');
    }
    //提现审核梳理
    public function checkTxApply()
    {
        $id=input('id');
        $alt=input('alt');
        $reason=input('reason');
        if($id<=0 || $alt!=0 && $alt!=1)return json(["msg"=>"参数错误","code"=>-1]);
        $ml=MoneyList::get($id);
        if(empty($ml))return  json(["msg"=>"记录找不到","code"=>-2]);
        if($alt==0)
        {
            $ml->info="提现-通过";
            //通过
        }else{
            //拒绝
            $ml->info="提现-拒绝,钱已经退回账户,".$reason;
            //把用户的钱加回去
            $user=Users::get($ml->uid);
            if(empty($user))return  json(["msg"=>"找不到提现的用户","code"=>-2]);
            $user->money=$user->money+ abs($ml->money);
            if(!$user->save()){
                return json(["msg"=>"拒绝提现,退钱保存失败","code"=>4]);
            }
        }
        if($ml->save()){
            return json(["msg"=>"操作成功","code"=>0]);
        }else{
            return json(["msg"=>"操作失败","code"=>3]);
        }
    }
    public function tx()
    {
        $user=Session::get('user');
        if(empty($user))
            return json(["msg" => "登录超时", "code" => -2]);
        if(!empty($user))
            $user=Users::get($user->id);
        $txMoney=input('money');
        if($txMoney<=0 || $txMoney>$user->money)
            return json(["msg" => "申请的提现金额错误", "code" => -3]);
        if($user->alipay=='未填写')
            return json(["msg" => "支付宝账号未填写,请在修改资料处补充", "code" => -4]);
        $this->addMoneyList($user,$txMoney,0,"提现-审核中");
        $user=Users::get($user->id);
        $user->msgbox()->save(['msg'=>'您提现了'.$txMoney.'元,账户余额:'.$user->money,'sendTime'=>time()]);
        //记录到账变
        return json(["msg" => "申请成功,审核成功后将提现到您账户资料中的支付宝账号", "code" => 0]);
    }
    //用户软件店铺页面
    public function shop()
    {
        $this->assign('title',"用户店铺");
        $this->assign('keywords',"xx的软件");
        return $this->fetch('index/shop');
    }

}