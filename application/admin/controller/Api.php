<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/4/28
 * Time: 1:07
 */

namespace app\admin\controller;
namespace app\admin\controller;

use app\admin\model\CardRecord;
use app\admin\model\Cards;
use app\admin\model\ForwardUrl;
use app\admin\model\RemoteFunction;
use app\admin\model\SoftList;
use app\admin\model\SoftUsers;
use app\admin\model\SoftVer;
use app\admin\model\SuLoginRecord;
use app\admin\model\Variable;
use Crypt\encode;
use think\Controller;
use think\Request;
use think\Db;
use think\log;
use app\admin\model\Users;
use think\Session;


class Api extends Controller
{
    public $enc;

    function webgateway()
    {
        header("Content-type:text/html; charset=UTF-8");
        $data = file_get_contents("php://input");
        $id = input("server.HTTP_APPID");
        if ($id <= 0) return json(["msg" => "参数不正确", "code" => -10001]);
        $soft = SoftList::get($id);
        if (empty($soft)) return json(["msg" => "软件不存在", "code" => -10002]);
        $key = input("server.HTTP_INIT") == 1 ? substr($soft->key, 0, 24) : $soft['privateKey'];
        $privateSalt = $soft->privateSalt;
        $encryptType = input("server.HTTP_INIT") == 1 ? 0 : $soft->encryptType;
        $this->enc = new encode($key, $encryptType);
        $res = json_decode($this->enc->su_decrypt($data), true);
        if ($res['wtype'] == 1) $privateSalt = $key;
        $data = count($res['data']) == 1 ? $res['data'][0] : $res['data'];
        if ($res == null || empty($res)) return json(["msg" => "参数解密失败", "data" => ['key' => $key, 'encryptType' => $encryptType, 'data' => file_get_contents("php://input")], "code" => -10010]);
        //查询封包时间 与 服务器时间相差是否太大
        $timetiff=($res['timestamp']/1000)-time();
        if($timetiff>10 || $timetiff<-10)return $this->enc->encodeJson(["msg" => "封包时间与服务器时间相差太多".$timetiff , "data" => [], "code" => -10045]);
        if (!$this->enc->verifySign($res, $privateSalt)) return $this->enc->encodeJson(["msg" => "sign验证失败" . $privateSalt, "data" => [], "code" => -10003]);
        //选择操作类型
        switch ($res['wtype']) {
            case 1: //初始化
                return $this->init($id, $data, $soft);
                break;
            case 2: //注册
                return $this->register($id, $data, $soft);
                break;
            case 3: //登录
                return $this->login($id, $data, $soft);
                break;
            case 4: //心跳
                return $this->heart($id, $data, $soft);
                break;
            case 5: //退出登录
                return $this->quit($id, $data, $soft);
                break;
            case 6: //修改密码
                 return $this->changePassword($id, $data, $soft);
                break;
            case 7: //软件用户充值
                return $this->deposit($id, $data, $soft);
                break;
            case 8: //远程变量
                return $this->Variable($id, $data, $soft);
                break;
            case 9: //远程算法
                return $this->RemoteFunction($id, $data, $soft);
                break;
            case 10: //算法转发
                return $this->ForwardUrl($id, $data, $soft);
                break;
            case 11: //扣点
                return $this->makePoint($id, $data, $soft);
                break;
            case 12: //查询用户信息
                return $this->getUserInfo($id, $data, $soft);
                break;
            case 13: //修改绑定
                return $this->changeMaccode($id, $data, $soft);
                break;
            case 14: //扣点
                return $this->makeTime($id, $data, $soft);
                break;
            default:
        }

    }

    //初始化
    function init($id, $data, $soft)
    {
        //查询版本
        $ver = SoftVer::where(["sid" => $id, "ver" => $data['version']])->find();
        if (empty($ver)) return $this->enc->encodeJson(["msg" => "版本不存在", "data" => [], "code" => -10004]);
        $soft['ver'] = $ver;
        //版本是否启用
        if ($ver['status'] != 0) return $this->enc->encodeJson(["msg" => "版本停用", "data" => [], "code" => -10005]);
        //是否强制更新
        if ($ver['checkUpdate'] == 0 && $ver['ver'] != $soft['version']) return $this->enc->encodeJson(["msg" => "请更新到最新版本:" . $soft['version'], "data" => $soft, "code" => -10006]);
        return $this->enc->encodeJson(["msg" => "成功", "data" => $soft, "code" => 0]);
    }

    //软件用户注册
    function register($id, $data, $soft)
    {
        //用户名是否存在
        if (SoftUsers::where(['sid' => $id, 'username' => $data['username']])->find()) return $this->enc->encodeJson(["msg" => "用户名已经被注册啦", "data" => [], "code" => -10008]);
        if (strlen($data['password']) < 6 || strlen($data['username']) < 6) return $this->enc->encodeJson(["msg" => "用户名或密码长度不能小于6", "data" => [], "code" => -10023]);
        //处理注册赠送
        if ($soft['regFree'] == 1) {
            //赠送时间
            $data['out_time'] = time() + ($soft['regFreePoint'] * 3600);
        } else if ($soft['regFree'] == 2) {
            //赠送点数
            $data['point'] = $soft['regFreePoint'];
        }
        $ip = getIp();
        $city = findCityByIp($ip);
        $data['ip'] = $ip;
        $data['city'] = $city;
        $data['sid'] = $id;
        $data['sname'] = $soft['name'];
        $data['heart_time'] = time();
        $data['createtime'] = time();
        $data['authorid'] = $soft['uid'];
        $data['password'] = md5(md5($data['password']));
        $data['out_time'] = time();
        // $data['city'] = $city;
        if (SoftUsers::create($data)){
            //软件表给用户数量加1
            $soft->count+=1;
            $soft->save();
            return $this->enc->encodeJson(["msg" => "恭喜您注册成功!", "data" => [], "code" => 0]);
        }
        else
        {
            return $this->enc->encodeJson(["msg" => "注册失败!", "data" => [], "code" => -10009]);
        }
    }

    //软件用户登录
    function login($id, $data, $soft)
    {
        if (strlen($data['username']) < 6 || strlen($data['password']) < 6) return $this->enc->encodeJson(["msg" => "用户名或者密码长度小于6", "data" => [], "code" => -10012]);
        //验证软件状态
        if ($soft['status'] == '关闭验证') return $this->enc->encodeJson(["msg" => "软件关闭了验证", "data" => [], "code" => -10011]);
        //检查用户账号密码
        $user = SoftUsers::where(['username' => $data['username'], "password" => md5(md5($data['password'])), "sid" => $id])->find();
        if ($user) {
            //更改1分钟没有心跳的登录状态
            $this->clearDummy();
            //用户是否被禁止
            if ($user->status == 1) return $this->enc->encodeJson(["msg" => "用户被锁定,禁止登陆", "data" => [], "code" => -10016]);
            //顶号登录,仅允许最后登录的在线
            if ($soft->topLoginType == 1) {
                //如果登录过就把之前的下线
                $sr = new SuLoginRecord;
                $sr->save([
                    'status' => 1 //把之前该用户所有在线的都踢下线
                ], ['sid' => $id, "username" => $data['username'], "status" => 0]);
            }
            //是否超出多开现在
            $sr = SuLoginRecord::where(['sid' => $id, "username" => $data['username'], "status" => 0])->select();
            if (count($sr) > $soft->multiTypeValue) {
                return $this->enc->encodeJson(["msg" => "用户最多同时登陆" . ($soft->multiTypeValue + 1) . "个软件,当前登录数量" . count($sr), "data" => [], "code" => -10018]);
            } else {
                //多开情况
                //是否验证账号+机器码,其它直接放行
                if (strtoupper($data['maccode']) != strtoupper($user->maccode) && $soft->multiType == 0) {
                    return $this->enc->encodeJson(["msg" => "软件仅限制在绑定的的机器上使用", "data" => [], "code" => -10021]);
                }
            }
            //如果免费开放,直接通过验证
            if ($soft['status'] == '免费') {
                goto ok;
            }
            //检查用户剩余时间或者点数
            if ($soft->verifyMode == 0)//验证时间
            {
                if (strtotime($user->out_time) <= time()) return $this->enc->encodeJson(["msg" => "软件使用时间" . $user->out_time . "已经过期啦,请续费", "data" => [], "code" => -10014]);
            } else {
                //验证点数
                if ($user->point < $soft['pointStep']) return $this->enc->encodeJson(["msg" => "软件使用点数剩余" . $user->point . ",已经不足啦,至少需要" . $soft['pointStep'] . "点,请续费", "data" => [], "code" => -10015]);
                //扣除点数
                $user->point -= $soft['pointStep'];
                $user->save();
            }
        } else {
            return $this->enc->encodeJson(["msg" => "用户账户或密码不正确", "data" => [], "code" => -10013]);
        }
        ok:
        $ip = getIp();
        $city = findCityByIp($ip);
        //保存登录记录
        $sr = new SuLoginRecord();
        $sr->data([
            "sid" => $id,
            "sname" => $soft['name'],
            "uid" => $user->id,
            "username" => $data['username'],
            "maccode" => $data['maccode'],
            "ip" => $ip,
            "city" => $city,
            "login_time" => time(),
            "heart_time" => time()-20, //减去20是因为 立马登录心跳会 出现 距离上次心跳时间 相差小于15
            "authorid" => $soft->uid
        ]);
        $record = $sr->save();
        if (!$record) $this->enc->encodeJson(["msg" => "用户登录记录保存失败", "data" => [], "code" => -10017]);
        $user['login_record'] = $sr->id;
        //更改软件用户表用户状态为在线
        $su = new SoftUsers();
        $su->where("id", $user['id'])->update(['isonline' => 1, 'heart_time' => time()]);
        return $this->enc->encodeJson(["msg" => "用户登录成功", "data" => $user, "code" => 0]);
    }

    //将所有301秒内没有心跳的用户设置为下线,可能是意外关闭,心跳终止 ,服务器定是执行这条SQL来检查
    function clearDummy()
    {
        $sr = new SuLoginRecord;
        $sr->save(['status' => 1], function ($query) {
            // 更新status值为1 并且id大于10的数据
            $query->where('status', 0)->where('heart_time', '<', time() - 301);
        });
    }

    //退出登录
    function quit($id, $data, $soft)
    {
        //login_record
        $sr = SuLoginRecord::get($data['login_record']);
        $sr->status = 1;
        $sr->save();
        $uid = $sr->uid;
        $su = new SoftUsers();
        $su->where("id", $uid)->update(['isonline' => 0, 'heart_time' => time()]);
    }

    //心跳包
    function heart($id, $data, $soft)
    {
        if (!array_key_exists('login_record', $data) || !array_key_exists('softMD5', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        if ($sr->status == 1) return $this->enc->encodeJson(["msg" => "登录状态已经失效", "data" => [], "code" => -10044]);
        $uid = $sr->uid;
        $su = SoftUsers::get($uid);
        if (!$su) return $this->enc->encodeJson(["msg" => "用户不存在", "data" => [], "code" => -10019]);
        if ($su->status != 0) return $this->enc->encodeJson(["msg" => "用户状态被锁定", "data" => [], "code" => -10020]);
        //是否验证账号+机器码,其它直接放行
        if (strtoupper($data['maccode']) != strtoupper($su->maccode) && $soft->multiType == 0) 
		{
            return $this->enc->encodeJson(["msg" => "软件仅限制在绑定的的机器上使用", "data" => [], "code" => -10021]);
        }
        //检验用户是否到期
        if ($soft->verifyMode == 0 && $soft['status'] == '收费')//验证时间
        {
            if (strtotime($su->out_time) <= time()) {
                $sr->status = 1;
                $sr->save();
                $su->where("id", $uid)->update(['isonline' => 0, 'heart_time' => time()]);
                return $this->enc->encodeJson(["msg" => "软件使用时间" . $su->out_time . "已经过期啦,请续费", "data" => [], "code" => -10014]);
            }
        }
        //检查上次心跳距离现在是否超过15秒
        if (time() - strtotime($sr->heart_time) < 15) return $this->enc->encodeJson(["msg" => "两次心跳时间小于15秒", "data" => [], "code" => -10040]);
        $sr->softMD5 = $data['softMD5'];
        //更新心跳时间,登录记录的心跳
        $sr->heart_time = time();
        $sr->save();
        //软件用户的心跳
        $su->heart_time = time();
        $su->save();
        return $this->enc->encodeJson(["msg" => "成功", "data" => [], "code" => 0]);
    }

    //用户修改密码
    function changePassword($id, $data, $soft)
    {
        if (strlen($data['newPassword']) < 6) return $this->enc->encodeJson(["msg" => "新密码长度不能小于6", "data" => [], "code" => -10023]);
        $su = SoftUsers::where(['username' => $data['username'], 'password' => md5(md5($data['oldPassword']))])->find();
        if (!$su) return $this->enc->encodeJson(["msg" => "用户名或密码错误", "data" => [], "code" => -10022]);
        $su->password = md5(md5($data['newPassword']));
        return $su->save() ? $this->enc->encodeJson(["msg" => "密码修改成功", "data" => [], "code" => 0]) : $this->enc->encodeJson(["msg" => "密码修改失败", "data" => [], "code" => -10024]);
    }

    //用户充值
    function  deposit($id, $data, $soft)
    {
        //用户名,充值卡
        $user = SoftUsers::getByUsername($data['username']);
        if (!$user) return $this->enc->encodeJson(["msg" => "用户不存在", "data" => [], "code" => -10013]);
        //查询用户状态
        if ($user->status == 1) return $this->enc->encodeJson(["msg" => "用户被锁定,禁止登陆", "data" => [], "code" => -10016]);
        $card = Cards::getByCardNo($data['card_no']);
        if (!$card) return $this->enc->encodeJson(["msg" => "充值卡有误", "data" => [], "code" => -10025]);
        //查询充值卡类型 点卡 时间卡
        //卡是否被使用或者禁止 !=0
        if ($card->status == 1) return $this->enc->encodeJson(["msg" => "充值卡被使用过了", "data" => [], "code" => -10027]);
        if ($card->status == 2) return $this->enc->encodeJson(["msg" => "充值卡被封停", "data" => [], "code" => -10028]);
        $beforTime = strtotime($user->out_time);
        $beforPoint = $user->point;
        if ($card->type == 0) {
            //点卡充值  直接加上去
            $user->point += $card->card_value;
        } else {
            //时间点卡
            //如果到期时间小于当前时间,充值后的时间登陆 当前时间+充值卡时间 大于等于 到期时间+充值卡时间
            $out = strtotime($user->out_time) < time() ? time() : strtotime($user->out_time);
            $beforTime = $out;
            $value = 0;
            switch ($card->type) {
                case 1: //分
                    $value = $card->card_value * 60;
                    break;
                case 2: //时
                    $value = $card->card_value * 3600;
                    break;
                case 3: //天
                    $value = $card->card_value * 86400;
                    break;
                case 4: //周
                    $value = $card->card_value * 604800;
                    break;
                case 5: //月 31天
                    $value = $card->card_value * 2678400;
                    break;
                case 6: //年 365天
                    $value = $card->card_value * 31536000;
                    break;
                default:
            }
            $user->out_time = $out + $value;
        }
        //更改卡密状态
        $card->status = 1;//已经使用
        $card->user_account = $data['username'];
        if (!$card->save()) return $this->enc->encodeJson(["msg" => "充值卡状态保存失败", "data" => [], "code" => -10029]);
        if ($user->save()) {
            //添加到充值记录表
            $list = [
                'sid' => $card->sid,
                'sname' => $card->sname,
                'authorid' => $card->authorid,
                'card_no' => $card->card_no,
                'type' => $card->type,
                'cardValue' => $card->card_value,
                'user_account' => $data['username'],
                'depositTime' => time(),
                'beforTime' => $beforTime,
                'ofterTime' => strtotime($user->out_time),
                'beforPoint' => $beforPoint,
                'ofterPoint' => $user->point
            ];
            $cr = new CardRecord();
            $cr->create($list);
            return $this->enc->encodeJson(["msg" => "充值成功", "data" => ['point' => $user->point, 'time' => $user->out_time], "code" => 0]);
        } else {
            return $this->enc->encodeJson(["msg" => "充值卡失败", "data" => [], "code" => -10026]);
        }
    }

    //下线某个登录
    function getOut()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id < 0) return json(["msg" => '登录编号不正确', "code" => -2]);
        $sr = SuLoginRecord::get($id);
        if (empty($sr)) return json(["msg" => '版本不存在', "code" => -5]);
        if ($sr->authorid != $user->id) return json(["msg" => '非法访问数据', "code" => -3]);
        $sr->status = 1;
        if ($sr->save()) {
            return json(["msg" => '下线成功', "code" => 0]);
        } else {
            return json(["msg" => '下线失败', "code" => -4]);
        }
    }

    //远程变量
    function Variable($id, $data, $soft)
    {
        if (!array_key_exists('login_record', $data) || !array_key_exists('name', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        //检查用户登录状态
        $var = Variable::where(["name" => urldecode($data['name']), "sid" => $id])->find();
        if (empty($var)) return $this->enc->encodeJson(["msg" => "变量没有找到", "data" => [], "code" => -10031]);
        if ($soft->uid != $var->authorid) return $this->enc->encodeJson(["msg" => "变量不属于当前软件", "data" => [], "code" => -10033]);
        //查看是否存在value 存在就设置 不存在就取出
        if (array_key_exists('value', $data)) {
            //设置变量
            $var->value = urldecode($data['value']) ;
            if ($var->save() !== false) {
                return $this->enc->encodeJson(["msg" => "设置成功", "data" => ['value' => $var->value], "code" => 0]);
            } else {
                return $this->enc->encodeJson(["msg" => "变量设置失败", "data" => [], "code" => -10032]);
            }
        } else {
            //取出变量
            return $this->enc->encodeJson(["msg" => "取出成功", "data" => ['value' => $var->value], "code" => 0]);
        }
    }

    //远程算法 9
    function RemoteFunction($id, $data, $soft)
    {
        if (!array_key_exists('name', $data) || !array_key_exists('functionParam', $data) || !array_key_exists('login_record', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        //查出函数表
        $rf = RemoteFunction::where(["name" => $data['name'], "uid" => $soft->uid])->find();
        if (!$rf) return $this->enc->encodeJson(["msg" => "算法不存在,请确认算法标签是否正确", "data" => [], "code" => -10034]);
        if ($soft->id != $rf->sid) return $this->enc->encodeJson(["msg" => "算法不属于当前软件", "data" => [], "code" => -10035]);
        return $this->enc->encodeJson(["msg" => "执行成功", "data" => ['result' => v8JS($rf->uid . "-" . $rf->name,urldecode($data['functionParam']) )], "code" => 0]);
    }

    //算法转发 10
    function ForwardUrl($id, $data, $soft)
    {
        if (!array_key_exists('name', $data) || !array_key_exists('Param', $data) || !array_key_exists('login_record', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        $fu = ForwardUrl::where(["name" => $data['name'], "uid" => $soft->uid])->find();
        if (!$fu) return $this->enc->encodeJson(["msg" => "远程算法不存在", "data" => [], "code" => -10036]);
        if ($soft->id != $fu->sid) return $this->enc->encodeJson(["msg" => "远程算法不属于当前软件", "data" => [], "code" => -10037]);
        if ($fu->status == 0)//get
        {
            $res = curl_get($fu->url, $data['Param']);
        } else {
            $res = curl_post($fu->url, $data['Param']);
        }
        return $this->enc->encodeJson(["msg" => "取出成功", "data" => ['result' => $res], "code" => 0]);
    }

    //作者扣点11
    function makePoint($id, $data, $soft)
    {
        //取出登录记录
        if (!array_key_exists('point', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        if ($sr->status == 1) return $this->enc->encodeJson(["msg" => "登录状态已经失效", "data" => [], "code" => -10019]);
        $uid = $sr->uid;
        $su = SoftUsers::get($uid);
        if (!$su) return $this->enc->encodeJson(["msg" => "用户不存在", "data" => [], "code" => -10019]);
        if ($su->status != 0) return $this->enc->encodeJson(["msg" => "用户状态被锁定", "data" => [], "code" => -10020]);
        if ($su->point < $data['point']) {
            return $this->enc->encodeJson(["msg" => "用户点数不足", "data" => ["point" => $su->point], "code" => -10039]);
        }
        $su->point = $su->point - $data['point'];
        if ($su->save()) {
            return $this->enc->encodeJson(["msg" => "扣点成功", "data" => ["point" => $su->point], "code" => 0]);
        } else {
            return $this->enc->encodeJson(["msg" => "扣点失败", "code" => -10038]);
        }
    }
    //作者扣时14
    function makeTime($id, $data, $soft)
    {
        //取出登录记录
        if (!array_key_exists('hour', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        if ($sr->status == 1) return $this->enc->encodeJson(["msg" => "登录状态已经失效", "data" => [], "code" => -10019]);
        $uid = $sr->uid;
        $su = SoftUsers::get($uid);
        if (!$su) return $this->enc->encodeJson(["msg" => "用户不存在", "data" => [], "code" => -10019]);
        if ($su->status != 0) return $this->enc->encodeJson(["msg" => "用户状态被锁定", "data" => [], "code" => -10020]);
        $su->out_time=strtotime($su->out_time)-$data['hour']*3600;
        if ($su->save()) {
            return $this->enc->encodeJson(["msg" => "扣时成功", "data" => ["out_time" => $su->out_time], "code" => 0]);
        } else {
            return $this->enc->encodeJson(["msg" => "扣时失败", "code" => -10038]);
        }
    }
    //获取用户信息
    function getUserInfo($id, $data, $soft)
    {
        //取出登录记录
        if (!array_key_exists('login_record', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $sr = SuLoginRecord::get($data['login_record']);
        if (!$sr) return $this->enc->encodeJson(["msg" => "未找到登录记录", "data" => [], "code" => -10018]);
        if ($sr->status == 1) return $this->enc->encodeJson(["msg" => "登录状态已经失效", "data" => [], "code" => -10019]);
        $uid = $sr->uid;
        $su = SoftUsers::get($uid);
        if (!$su) return $this->enc->encodeJson(["msg" => "用户不存在", "data" => [], "code" => -10019]);
        return $this->enc->encodeJson(["msg" => "查询成功", "data" => $su, "code" => 0]);
    }
    //用户修改绑定
    function changeMaccode($id, $data, $soft)
    {
        if (!array_key_exists('username', $data) || !array_key_exists('password', $data) || !array_key_exists('maccode', $data)) return $this->enc->encodeJson(["msg" => "参数不正确", "data" => json($data), "code" => -10001]);
        $su=SoftUsers::where(['username'=>$data['username'],'password'=> md5(md5($data['password'])),'sid'=>$id])->find();
        if (!$su) return $this->enc->encodeJson(["msg" => "用户不存在", "data" => [], "code" => -10019]);
        if(strtoupper($data['maccode']) ==strtoupper($su->maccode) )return $this->enc->encodeJson(["msg" => "用户已经绑定到当前机器啦,无需换绑", "data" => [], "code" => -10041]);
        //检查换绑次数
        if($su->modif_num<=0)return $this->enc->encodeJson(["msg" => "对不起换绑次数不足", "data" => [], "code" => -10042]);
        $su->modif_num=$su->modif_num-1;
        $su->maccode=strtoupper($data['maccode']) ;
        if($su->save()){
            return $this->enc->encodeJson(["msg" => "换绑成功", "data" => [], "code" => 0]);
        }else{
            return $this->enc->encodeJson(["msg" => "换绑失败", "data" => [], "code" => -10043]);
        }
    }
    function test()
    {
        // $url="http://mytest.com/admin/soft/test";
        // echo curl_post($url,"id=1&a=6");
        // echo phpinfo();
        //v8JS("18-max","max(1,2)");
        echo $this->request->scheme();
    }
}