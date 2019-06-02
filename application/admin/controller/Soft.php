<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/4/20
 * Time: 18:50
 */

namespace app\admin\controller;

use app\admin\model\Cards;
use app\admin\model\CardType;
use app\admin\model\ForwardUrl;
use app\admin\model\RemoteFunction;
use app\admin\model\SoftList;
use app\admin\model\SoftUsers;
use app\admin\model\SoftVer;
use app\admin\model\Variable;
use Crypt\Aes;
use Crypt\encode;
use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;


class Soft extends Controller
{
    public function softList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }


        $this->assign('user', $user);
        $this->assign('title', $user->username . " - 软件列表");
        $this->assign('keywords', $user->username . "- 软件列表");
        return $this->fetch('soft/list');
    }

    //获取软件列表
    function getSoftList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }


        $where = [
            'uid' => $user->id,
        ];
        $pageSize = input('limit');
        $name = input('name');
        if (!empty($name)) {
            $where['name'] = ['like', '%' . $name . '%'];;
        }
        $list = SoftList::where($where)->order('id desc')->limit(input('offset'), $pageSize)->select();
        $total = SoftList::where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    //删除软件
    function delSoft()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id < 0) return json(["msg" => '软件id不正确', "code" => -2]);
        $soft = SoftList::get($id);
        if (empty($soft)) return json(["msg" => '软件不存在', "code" => -5]);
        if ($soft->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -3]);
        if ($soft->delete()) {
            return json(["msg" => '成功删除了该软件', "code" => 0]);
        } else {
            return json(["msg" => '软件删除失败', "code" => -4]);
        }
    }

    //编辑软件页面
    function editSoft()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }


        $id = input('id');
        if ($id < 0) return "软件id不正确";
        $soft = SoftList::get($id);
        $this->assign('user', $user);
        $this->assign('soft', $soft);
        $this->assign('title', $user->username . " - 软件编辑");
        $this->assign('keywords', $user->username . "- 软件编辑");
        return $this->fetch('soft/editSoft');
    }

    //添加软件页面
    function addsoft()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }


        $soft = [
            'id' => '',
            'name' => '',
            'status' => '收费',
            'key' => '系统自动生成',
            'openReg' => 0,//开放注册
            'notice' => '',
            'data' => '',
            'regFree' => 0,
            'regFreePoint' => 0,
            'timeFree' => 0,
            'timeFreePointEnd' => 12,
            'timeFreePointStart' => 1,
            'freeChangeBundled' => 0,
            'verifyMode' => 0,
            'topLoginType' => 0,
            'pointStep' => 1,
            'multiType' => 0,
            'multiTypeValue' => 1,
            'isModifyMac' => 0,
            'encryptType' => 0,
            'privateKey' => generate_password(24),
            'privateSalt' => generate_password(16),
            'sale_remark'=>''
        ];
        $this->assign('user', $user);
        $this->assign('soft', $soft);
        $this->assign('title', $user->username . " - 添加软件");
        $this->assign('keywords', $user->username . "- 添加软件");
        return $this->fetch('soft/editSoft');
    }

    //添加或更新软件
    function updateSoft()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);


        //先判断是更新还是添加
        $id = input('id');
        $all = Request::instance()->post();
        if (!empty($id)) //更新软件
        {
            $soft = SoftList::get($id);
            if ($soft->update($all)) {
                return json(["msg" => "更新成功", "code" => 0]);
            } else {
                return json(["msg" => "更新失败", "code" => -2]);
            }
        } else {
            //查询用户名下有多少软件了,限制数量
            $max = 100;
            $count = SoftList::where(['uid' => $user->id])->count();
            if ($count >= $max) return json(["msg" => "最多添加" . $max . "个软件哦!", "code" => -4]);
            //添加软件
            $all['key'] = strtoupper(md5(guid()));
            $all['uid'] = $user->id;
            $all['expireTime'] = time();
            if (SoftList::create($all)) {
                return json(["msg" => "添加成功", "code" => 0]);
            } else {
                return json(["msg" => "添加失败", "code" => -3]);
            }
        }
    }

    //获取用户所有软件ID和名称
    function getSoftListsimple()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $where = [
            'uid' => $user->id,
        ];
        $list = SoftList::where($where)->field(['id', 'name'])->select();
        return json(["total" => count($list), "rows" => $list, "code" => 0]);
    }

    //软件版本列表页面
    function ver()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);


        $id = input('id');
        if ($id < 0) return json(["msg" => '软件id不正确', "code" => -2]);
        $soft = SoftList::get($id);
        if (empty($soft)) return json(["msg" => '软件不存在', "code" => -5]);
        if ($soft->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -3]);
        $this->assign('user', $user);
        $this->assign('soft', $soft);
        $this->assign('title', $user->username . " - 版本管理");
        $this->assign('keywords', $user->username . "- 版本管理");
        return $this->fetch('soft/verGM');
    }

    function getVerList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);


        $id = input('id');
        if ($id < 0) return json(["msg" => '软件id不正确', "code" => -2]);
        $soft = SoftList::get($id);
        if (empty($soft)) return json(["msg" => '软件不存在', "code" => -5]);
        if ($soft->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -3]);
        $pageSize = input('limit');
        $name = input('name');
        $where = [
            'sid' => $id
        ];
        if (!empty($name)) {
            $where['name'] = ['like', '%' . $name . '%'];
        }
        $list = SoftVer::where($where)->order('id desc')->limit(input('offset'), $pageSize)->select();
        $total = SoftVer::where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    //删除版本
    function delVer()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id < 0) return json(["msg" => '版本编号不正确', "code" => -2]);
        $ver = Softver::get($id);
        if (empty($ver)) return json(["msg" => '版本不存在', "code" => -5]);
        //查询所属软件
        $soft = SoftList::get($ver->sid);
        if ($soft->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -3]);
        //更新最新版的版本信息为该软件最后添加的版本
        if ($ver->delete()) {
            $newVer=SoftVer::where([
                "sid"=>$soft->id
            ])->order('addTime', 'desc')->find();
            $soft->version=empty($newVer)?"":$newVer->ver;
            $soft->save();
            return json(["msg" => '成功删除了该版本', "code" => 0]);
        } else {
            return json(["msg" => '版本删除失败', "code" => -4]);
        }
    }

    //添加版本页面
    function addVer()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }


        $id = input('id');
        if ($id < 0) return '软件编号不正确';
        $soft = SoftList::get($id);
        if (empty($soft)) return "软件不存在";
        if ($soft->uid != $user->id) return '非法访问数据';
        $ver = [
            'id' => '',
            'name' => '',
            'ver' => '1.0',
            'status' => 0,
            'checkUpdate' => 0,
            'MD5' => '',
            'updateUrl' => '',
            'notice' => '',
            'reamrk' => ''
        ];
        $this->assign('user', $user);
        $this->assign('ver', $ver);
        $this->assign('soft', $soft);
        $this->assign('sbName', "确认添加");
        $this->assign('cbName', "取消添加");
        $this->assign('title', $user->username . " - 版本添加");
        $this->assign('keywords', $user->username . "- 版本添加");
        return $this->fetch('soft/editVer');
    }

    //编辑版本页面
    function editVer()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }


        $id = input('id');
        if ($id < 0) return "版本id不正确";
        $ver = SoftVer::get($id);
        if (empty($ver)) return "版本不存在";
        $soft = SoftList::get($ver->sid);
        $this->assign('user', $user);
        $this->assign('ver', $ver);
        $this->assign('soft', $soft);
        $this->assign('sbName', "确认修改");
        $this->assign('cbName', "取消修改");
        $this->assign('title', $user->username . " - 版本编辑");
        $this->assign('keywords', $user->username . "- 版本编辑");
        return $this->fetch('soft/editVer');
    }

    //添加或者修改版本
    function updateVer()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);


        $id = input('id');    //版本ID
        $sid = input('sid'); //软件ID
        if ($sid <= 0) return json(["msg" => '软件编号错误', "code" => -2]);
        //先查询版本所属软件是不是该用户的
        $soft = SoftList::get($sid);
        if (empty($soft)) return json(["msg" => '软件不存在', "code" => -3]);
        if ($soft->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -4]);
        //先判断是更新还是添加
        $all = Request::instance()->post();
        if (!empty($id)) //更新版本
        {
            $soft = SoftVer::get($id);
            if ($soft->update($all)) {
                return json(["msg" => "更新成功", "code" => 0]);
            } else {
                return json(["msg" => "更新失败", "code" => -2]);
            }
        } else {
            //先查询版本是否存在
            if (SoftVer::where(['sid' => $sid, 'ver' => $all['ver']])->find()) return json(["msg" => "版本已经存在,请选择修改版本", "code" => -5]);
            //添加版本
            $all['addTime'] = time();
            $all['sid'] = $sid;
            if (SoftVer::create($all)) {
                //更新到软件最新版本
                $soft->version = $all['ver'];
                $soft->save();
                return json(["msg" => "添加成功", "code" => 0]);
            } else {
                return json(["msg" => "添加失败", "code" => -3]);
            }
        }
    }

    //远程变量页面
    function variableList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign("user", $user);
        $this->assign('title', $user->username . " - 远程变量");
        $this->assign('keywords', $user->username . "- 远程变量");
        return $this->fetch('soft/variableList');
    }

    function getVariableList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $where = [
            'v.authorid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['v.sname'] = ['like', '%' . $sname . '%'];
        }
        $var = new Variable();
        $list = $var->alias("v")
            ->join("soft_list s", "s.id=v.sid", "LEFT")
            ->field("v.*,s.name as sname")
            ->where($where)
            ->limit(input('offset'), $pageSize)
            ->order(['v.id' => 'desc'])
            ->select();
        $total = $var->alias("v")->where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    //删除变量
    function deleteVariable()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id < 0) return json(["msg" => '变量id不正确', "code" => -2]);
        $data =Variable::get($id);
        if (empty($data)) return json(["msg" => '变量没有找到', "code" => -3]);
        if ($data->authorid != $user->id) return json(["msg" => '非法访问数据', "code" => -4]);
        if ($data->delete()) {
            return json(["msg" => '成功删除了该变量', "code" => 0]);
        } else {
            return json(["msg" => '变量删除失败', "code" => -5]);
        }
    }
    //添加编辑变量
    function addVariable()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $id = input('id');
        $var=[];
        if ($id > 0){
            //修改
            $this->assign('sbName', "确认修改");
            $this->assign('cnName', "取消修改");
            $this->assign('title', $user->username . " - 变量修改");
            $this->assign('keywords', $user->username . "- 变量修改");
            $Variable = new Variable();
            $var = $Variable->alias("v")
                ->join("soft_list s", "s.id=v.sid", "LEFT")
                ->field("v.*,s.name as sname")
                ->where(["v.id"=>$id])
                ->find();
        }else
        {
            $var=[
              'id'=>0,
                'sname'=>"",
                'sid'=>0,
                'name'=>'',
                 'value'=>''
            ];
            //新增
            $this->assign('sbName', "确认添加");
            $this->assign('cnName', "取消添加");
            $this->assign('title', $user->username . " - 变量添加");
            $this->assign('keywords', $user->username . "- 变量添加");
        }
        $this->assign('var', $var);
        $this->assign('user', $user);
        return $this->fetch('soft/Variable');
    }
    //添加变量
    function addVar()
    {
        //新增还是修改
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id <= 0) {
            //新增
            $var = new Variable();
        } else {
            //修改
            $var = Variable::get($id);
        }
        $data = $this->request->param();
        $data['authorid'] = $user->id;
        $data['create_time']=time();
        if ($var->allowField(true)->save($data)) {
            return json(["msg" => '成功', "code" => 0]);
        } else {
            return json(["msg" => '失败', "code" => -2]);
        }
    }
    //远程JS函数
    function FunctionList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign("user", $user);
        $this->assign('title', $user->username . " - 远程函数");
        $this->assign('keywords', $user->username . "- 远程函数");
        return $this->fetch('soft/FunctionList');
    }
    //获取代码列表
    function getFunctionList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $where = [
            'r.uid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['r.sname'] = ['like', '%' . $sname . '%'];
        }
        $rf = new RemoteFunction();
        $list = $rf->alias("r")
            ->join("soft_list s", "s.id=r.sid", "LEFT")
            ->field("r.*,s.name as sname")
            ->where($where)
            ->limit(input('offset'), $pageSize)
            ->order(['r.id' => 'desc'])
            ->select();
        $total = $rf->alias("r")->where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }
    //添加修改函数页面
    function addFunction()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $id = input('id');
        $func=[];
        if ($id > 0){
            //修改
            $this->assign('sbName', "确认修改");
            $this->assign('cnName', "取消修改");
            $this->assign('title', $user->username . " - 函数修改");
            $this->assign('keywords', $user->username . "- 函数修改");
            $function = new RemoteFunction();
            $func = $function->alias("v")
                ->join("soft_list s", "s.id=v.sid", "LEFT")
                ->field("v.*,s.name as sname")
                ->where(["v.id"=>$id])
                ->find();
            if(empty($func))return "函数不存在";
            $file_path=ROOT_PATH . 'public' . DS . 'uploads'.DS."v8js". DS.$user->id."-".$func->name.".txt";
            if(file_exists($file_path)) {
                $func['value']=file_get_contents($file_path);
            }else{
                $func['value']="代码文件丢失".$file_path;
            }


        }else
        {
            $func=[
                'id'=>0,
                'sname'=>"",
                'sid'=>0,
                'name'=>'',
                'value'=>''
            ];
            //新增
            $this->assign('sbName', "确认添加");
            $this->assign('cnName', "取消添加");
            $this->assign('title', $user->username . " - 函数添加");
            $this->assign('keywords', $user->username . "- 函数添加");
        }
        $this->assign('func', $func);
        $this->assign('user', $user);
        return $this->fetch('soft/addFunction');
    }
    function addRemoteFunction()
    {
        //新增还是修改
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id <= 0) {
            //新增
            $var = new RemoteFunction();
        } else {
            //修改
            $var = RemoteFunction::get($id);
        }

        $data = $this->request->param();
        $data['uid'] = $user->id;
        $data['create_time']=time();
        if ($var->allowField(true)->save($data)) {
            //把代码保存到文件
            $file_path=ROOT_PATH . 'public' . DS . 'uploads'.DS."v8js". DS.$user->id."-".$data['name'].".txt";
            $myfile = fopen($file_path, "w") or die("Unable to open file!");
            fwrite($myfile, urldecode($data['value']));
            fclose($myfile);
            return json(["msg" => '成功', "code" => 0]);
        } else {
            return json(["msg" => '失败', "code" => -2]);
        }
    }
    //删除JS函数
    function deleteFunction()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id < 0) return json(["msg" => '函数id不正确', "code" => -2]);
        $data =RemoteFunction::get($id);
        if (empty($data)) return json(["msg" => '函数没有找到', "code" => -3]);
        if ($data->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -4]);
        if ($data->delete()) {
            return json(["msg" => '成功删除了该函数', "code" => 0]);
        } else {
            return json(["msg" => '函数删除失败', "code" => -5]);
        }
    }
    //算法转发
    function ForwardUrlList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign("user", $user);
        $this->assign('title', $user->username . " - 算法转发");
        $this->assign('keywords', $user->username . "- 算法转发");
        return $this->fetch('soft/ForwardUrlList');
    }
    function getForwardUrlList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $where = [
            'r.uid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['r.sname'] = ['like', '%' . $sname . '%'];
        }
        $fu = new ForwardUrl();
        $list = $fu->alias("r")
            ->join("soft_list s", "s.id=r.sid", "LEFT")
            ->field("r.*,s.name as sname")
            ->where($where)
            ->limit(input('offset'), $pageSize)
            ->order(['r.id' => 'desc'])
            ->select();
        $total = $fu->alias("r")->where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }
    function addForward()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $id = input('id');
        $forward=[];
        if ($id > 0){
            //修改
            $this->assign('sbName', "确认修改");
            $this->assign('cnName', "取消修改");
            $this->assign('title', $user->username . " - 算法转发修改");
            $this->assign('keywords', $user->username . "- 算法转发修改");
            $fu = new ForwardUrl();
            $forward = $fu->alias("v")
                ->join("soft_list s", "s.id=v.sid", "LEFT")
                ->field("v.*,s.name as sname")
                ->where(["v.id"=>$id])
                ->find();
            if(empty($forward))return "转发算法不存在";
        }else
        {
            $forward=[
                'id'=>0,
                'sname'=>"",
                'sid'=>0,
                'name'=>'',
                'value'=>'',
                'type'=>0,
                'url'=>''
            ];
            //新增
            $this->assign('sbName', "确认添加");
            $this->assign('cnName', "取消添加");
            $this->assign('title', $user->username . " - 算法转发添加");
            $this->assign('keywords', $user->username . "- 算法转发添加");
        }
        $this->assign('forward', $forward);
        $this->assign('user', $user);
        return $this->fetch('soft/addForward');
    }
    //添加或修改算法
    function addForwardUrl()
    {
        //新增还是修改
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id <= 0) {
            //新增
            $fu = new ForwardUrl();
        } else {
            //修改
            $fu = ForwardUrl::get($id);
        }
        $data = $this->request->param();
        $data['uid'] = $user->id;
        $data['add_time']=time();
        if ($fu->allowField(true)->save($data)) {
            return json(["msg" => '成功', "code" => 0]);
        } else {
            return json(["msg" => '失败', "code" => -2]);
        }
    }
    //删除转发
    function deleteForward()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id < 0) return json(["msg" => '转发算法id不正确', "code" => -2]);
        $data =ForwardUrl::get($id);
        if (empty($data)) return json(["msg" => '转发算法没有找到', "code" => -3]);
        if ($data->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -4]);
        if ($data->delete()) {
            return json(["msg" => '成功删除了该转发算法', "code" => 0]);
        } else {
            return json(["msg" => '转发算法删除失败', "code" => -5]);
        }
    }
    //修改算法转发的状态
    function changeForwardStatus()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        $status=input('status');
        if ($status < 0 || $status>1) return json(["msg" => '提交的状态不正确', "code" => -6]);
        if ($id < 0) return json(["msg" => '转发算法id不正确', "code" => -2]);
        $fu = new ForwardUrl();
        $where = [
            'v.id' => $id,
        ];
        $data = $fu->alias("v")
            ->join("soft_list s", "s.id=v.sid", "LEFT")
            ->where($where)
            ->field("v.*,s.uid")
            ->find();
        if (empty($data)) return json(["msg" => '转发算法没有找到', "code" => -3]);
        if ($data->uid != $user->id) return json(["msg" => '非法访问数据', "code" => -4]);
        $data->status=$status;
        if ($data->save()) {
            return json(["msg" => '成功更改了状态', "code" => 0]);
        } else {
            return json(["msg" => '状态修改失败', "code" => -5]);
        }
    }
    function test()
    {
        /*
        $card = new Cards;
        $list = $card->alias('a')
            ->join('soft_list u', 'u.id=a.sid and a.authorid =2')
            ->field('u.name,u.key,a.type,a.id')
            ->order(['a.id' => 'desc'])
            ->select();
        foreach ($list as $k => $v) {
            echo $k . "=>" . $v . "<br />";
        }
        echo $card->getLastSql();
        */

       // echo v8JS("2-des","encryptByDESModeCBC('2','2')");
        // 是否为 GET 请求
        if (Request::instance()->isGet()) echo "GET 请求";
        // 是否为 POST 请求
        if (Request::instance()->isPost()) echo "POST 请求";
        $data = $this->request->param();
        echo "参数".json_encode($data);

    }
}