<?php
/**
 * Created by IntelliJ IDEA.
 * User: Administrator
 * Date: 2019/5/2
 * Time: 22:40
 */

namespace app\admin\controller;

use app\admin\model\BuycardRecord;
use app\admin\model\CardRecord;
use app\admin\model\CardType;
use app\admin\model\PayRecord;
use app\admin\model\SoftList;
use think\Session;
use think\Controller;
use app\admin\model\Cards;

class Card extends Controller
{
    function cardTypeList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            {
                $this->assign('title', "超时");
                $this->assign('keywords', "超时");
                return $this->fetch('index/timeout');
            }
        }

        $this->assign('user', $user);
        $this->assign('title', $user->username . " - 登录记录");
        $this->assign('keywords', $user->username . "- 登录记录");
        return $this->fetch('card/cardTypeList');
    }

    function getCardTypeList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $id = input('id');
        $where = [
            'authorid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['sname'] = ['like', '%' . $sname . '%'];
        }
        if (!empty($id)) {
            $where['id'] = $id;
            $list = CardType::where($where)->order('id desc')->limit(input('offset'), $pageSize)->select();
        } else {
            //过滤掉不需要的字段
            $list = CardType::where($where)->limit(input('offset'), $pageSize)->select();
        }
        $total = CardType::where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    function deleteCardType()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id <= 0) return json(["msg" => '卡类编号不正确', "code" => -2]);
        $ct = CardType::get($id);
        if (empty($ct)) return json(["msg" => '没有找到该类型', "code" => -3]);
        if ($ct->authorid != $user->id) return json(["msg" => '你不能删除不属于你的卡密类型', "code" => -4]);
        if ($ct->delete())
            return json(["msg" => '删除成功', "code" => 0]);
        else
            return json(["msg" => '删除失败', "code" => -5]);
    }

    //添加卡类型页面
    function addType()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $id = input('id');
        if ($id <= 0) {
            //新增
            $ct = [
                'id' => 0,
                'sid' => 0,
                'type' => 0,
                'cardValue' => "",
                'cardPrice' => "",
                'remark' => '',
                'authorid' => $user->id
            ];
            $this->assign('sbName', '确认添加');
            $this->assign('cnName', '取消添加');
        } else {
            //修改
            $ct = CardType::get($id);
            $this->assign('sbName', '确认修改');
            $this->assign('cnName', '取消修改');
        }
        $this->assign('user', $user);
        $this->assign('card', $ct);
        $this->assign('title', $user->username . " - 编辑卡类");
        $this->assign('keywords', $user->username . "- 编辑卡类");
        return $this->fetch('card/editCard');
    }

    //编辑卡密类型
    function editCardType()
    {
        //新增还是修改
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');

        if ($id <= 0) {
            //新增
            $ct = new CardType();
        } else {
            //修改
            $ct = CardType::get($id);
        }
        $data = $this->request->param();
        $data['authorid'] = $user->id;
        $data['add_time'] = time();
        if ($ct->allowField(true)->save($data)) {
            return json(["msg" => '成功', "code" => 0]);
        } else {
            return json(["msg" => '失败', "code" => -2]);
        }
    }

    //卡密页面
    function cardList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign('user', $user);
        $this->assign('title', $user->username . " - 卡密列表");
        $this->assign('keywords', $user->username . "- 卡密列表");
        return $this->fetch('card/CardList');
    }

    //获取卡密数据
    function getCardList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $where = [
            'authorid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['sname'] = ['like', '%' . $sname . '%'];
        }
        //过滤掉不需要的字段
        $list = Cards::where($where)->order('id desc')->limit(input('offset'), $pageSize)->select();
        $total = Cards::where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    //更改卡密状态
    function updateCardStatus()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        $status = input('status');
        if ($status < 0 || $status > 2) return json(["msg" => '输入的状态码错误', "code" => -4]);
        if ($id <= 0) return json(["msg" => '卡密ID错误', "code" => -2]);
        $card = Cards::get($id);
        if (!$card) return json(["msg" => '卡密不存在', "code" => -3]);
        //被使用的不可更改
        if ($card->status == 1) return json(["msg" => '卡密已经被使用啦,不可更改状态哦', "code" => -6]);
        $card->status = $status;
        if ($card->save()) {
            return json(["msg" => '更新成功', "status" => $card->status, "code" => 0]);
        } else {
            return json(["msg" => '更新失败', "code" => -5]);
        }
    }

    //删除卡密
    function deleteCard()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $id = input('id');
        if ($id <= 0) return json(["msg" => '卡密编号不正确', "code" => -2]);
        $ct = Cards::get($id);
        if (empty($ct)) return json(["msg" => '没有找到该卡密', "code" => -3]);
        if ($ct->authorid != $user->id) return json(["msg" => '你不能删除不属于你的卡密', "code" => -4]);
        if ($ct->delete())
            return json(["msg" => '删除成功', "code" => 0]);
        else
            return json(["msg" => '删除失败', "code" => -5]);
    }

    //添加卡密页面
    function addCard()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign('user', $user);
        $this->assign('sbName', '确认添加');
        $this->assign('cnName', '取消添加');
        $this->assign('title', $user->username . " - 添加卡密");
        $this->assign('keywords', $user->username . "- 添加卡密");
        return $this->fetch('card/addCard');
    }

    //获取简单的卡密类型
    function getCardTypeSimple()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $sid = input('id');
        if ($sid <= 0) return json(["msg" => '软件ID错误', "code" => -2]);
        $where = [
            'sid' => $sid,
            'authorid' => $user->id,
        ];
        $list = CardType::where($where)->field(['id', 'type', 'cardValue', 'cardPrice'])->select();
        return json(["total" => count($list), "rows" => $list, "code" => 0]);
    }

    //用户添加卡密
    function userAddCard()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $data = $this->request->param();
        $cardNum = floor($data['cardNum']);
        if ($cardNum <= 0) return json(["msg" => '生成数量不能小于1', "code" => -1]);
        //判断sid与type是否存在
        if ($data['sid'] <= 0 || $data['typeId'] < 0) return json(["msg" => '传入参数错误', "code" => -2]);
        //找出卡类型数据
        $ct = CardType::get($data['typeId']);
        if (!$ct) return json(["msg" => '卡密类型没有找到', "code" => -3]);
        $list = array();
        $keys = "";
        for ($i = 0; $i < $cardNum; $i++) {
            $card_no = $data['cardHead'] . strtoupper(MD5(guid()));
            $keys = $keys . $card_no . "<br />";
            $list[$i] = [
                'sid' => $data['sid'],
                'sname' => $data['sname'],
                'type' => $ct->type,
                'authorid' => $user->id,
                'card_no' => $card_no,
                'status' => 0,
                'card_value' => $ct->cardValue,
                'remark' => $data['remark'],
                'add_time' => time(),
                'sell' => $data['sell']
            ];

        }
        $card = new Cards();
        if ($card->saveAll($list)) {
            return json(["msg" => '添加成功', "keys" => $keys, "code" => 0]);
        } else {
            return json(["msg" => '添加失败', "code" => 4]);
        }
    }

    //充值记录页面
    function cardLogList()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign('user', $user);
        $this->assign('title', $user->username . " - 充值日志");
        $this->assign('keywords', $user->username . "- 充值日志");
        return $this->fetch('card/cardLogList');
    }

    function getCardLogList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $where = [
            'authorid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['sname'] = ['like', '%' . $sname . '%'];
        }
        //过滤掉不需要的字段
        $list = CardRecord::where($where)->limit(input('offset'), $pageSize)->select();
        $total = CardRecord::where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    //导出卡密页面
    function exportCard()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign('user', $user);
        $this->assign('title', $user->username . " - 充值日志");
        $this->assign('keywords', $user->username . "- 充值日志");
        return $this->fetch('card/exportCard');
    }

    //导出卡密
    function userExportCard()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        //查询出数据
        $sid = (int)input('sid');
        $sell = (int)input('sell');
        $type = (int)input('type');
        $exportType = (int)input('exportType');
        $remark = input("remark");
        $where = [
            "status" => 0,
            "authorid" => $user->id
        ];
        if ($sid != -1) $where["sid"] = $sid;
        if ($type != -1) $where["type"] = $type;
        if ($sell != 2) $where["sell"] = $sell;
        if (!empty($remark)) $where['remark'] = $remark;
        $list = Cards::where($where)->select();
        foreach ($list as $key => $value) {
            switch ($list[$key]['type']) {
                case 0: //点卡
                    $list[$key]['type'] = "点卡";
                    break;
                case 1: //分
                    $list[$key]['type'] = "分卡";
                    break;
                case 2: //时
                    $list[$key]['type'] = "时卡";
                    break;
                case 3: //天
                    $list[$key]['type'] = "天卡";
                    break;
                case 4: //周
                    $list[$key]['type'] = "周卡";
                    break;
                case 5: //月 31天
                    $list[$key]['type'] = "月卡";
                    break;
                case 6: //年 365天
                    $list[$key]['type'] = "年卡";
                    break;
                default:
            }
        }
        //导出xls 还是txt
        if ($exportType == 0) {
            //xls
            $field = array(
                'A' => array('sname', '软件名称'),
                'B' => array('card_no', '充值卡号'),
                'C' => array('type', '类型'),
                'D' => array('card_value', '面值'),
                'E' => array('add_time', '添加时间'),
                'F' => array('remark', '备注'),
            );
            phpExcelList($field, $list, '卡密列表_' . date('Y-m-d'));
        } else {
            //txt
            $ua = $_SERVER["HTTP_USER_AGENT"];
            $filename = "cards". date('Y-m-d').".txt";
            $encoded_filename = urlencode($filename);
            $encoded_filename = str_replace("+", "%20", $encoded_filename);

            header("Content-Type: application/octet-stream");
            if (preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT'])) {
                header('Content-Disposition:  attachment; filename="' . $encoded_filename . '"');
            } elseif (preg_match("/Firefox/", $_SERVER['HTTP_USER_AGENT'])) {
                header('Content-Disposition: attachment; filename*="utf8' . $filename . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                echo "软件名称\t充值卡号\t类型\t面值\t添加时间\t备注\r\n";
                foreach ($list as $key => $value) {
                    echo $value['sname']."\t".$value['card_no']."\t".$value['type']."\t".$value['card_value']."\t".$value['add_time']."\t".$value['remark']."\r\n";
                }
            }
        }
    }

    //卡密出售页面
    function buyCard()
    {
        $sid = input('id');
        if ($sid <= 0) return "软件ID错误";
        $soft = SoftList::get($sid);
        if (empty($soft)) return "软件不存在";
        //获取充值卡信息
        $cardType = CardType::where("sid", $sid)->select();
        $this->assign('soft', $soft);
        $this->assign('cardType', $cardType);
        $this->assign('title', "欢迎购买" . $soft['name'] . "软件充值卡");
        $this->assign('keywords', $soft['name'] . "软甲卡密," . $soft['name']);
        return $this->fetch('card/buyCard');
    }

    //库存
    function cardInventory()
    {
        $sid = (int)input('sid');
        $type = (int)input('type');
        $card_value = (int)input('card_value');
        $where = [
            "sid" => $sid,
            "status" => 0,
            "card_value" => $card_value,
            'sell' => 0,
            'bid' => 0,
        ];
        if (!($type < 0 || $type > 6)) {
            $where['type'] = $type;
        }
        if ($sid < 1 || $card_value < 1) return json(["msg" => "参数错误", "code" => -1]);
        $total = Cards::where($where)->count();
        return json(["total" => $total, "code" => 0]);
    }

    //卡密出售校验跳转
    function goBuy()
    {
        $sid = (int)input('sid');
        $num = (int)input('num');
        if ($num < 1) return "购买数量小于1";
        if ($sid <= 0) return "软件ID错误";
        $soft = SoftList::get($sid);
        if (empty($soft)) return "软件不存在";
        $cardTypeId = input('cardId');
        if ($cardTypeId <= 0) return "卡密类型错误,或作者没有添加卡密类型";
        $cardType = CardType::get($cardTypeId);
        if (empty($cardType)) return "卡密类型不存在";
        //查询有没有那么多可售的卡
        //查出卡密表  软件id  面值 卡密类型 状态 且 系统可售 符合的卡有多少张
        $cardTotal = Cards::where([
            "sid" => $sid,
            "card_value" => $cardType->cardValue,
            "type" => $cardType->type,
            "status" => 0,
            "sell" => 0
        ])->count();
        if ($cardTotal < $num) return "系统可售卡不足" . $num . "张,剩余" . $cardTotal . "张";
        $price = $cardType->cardPrice * $num;
        //生成订单号
        $orderNo = createOrderNo();
        $link = "";
        $pay_mode = 3;
        //如果选择了码支付
        if (input("payType") == "alipay" || input("payType") == "qq") {
            $codepay_id = config('codepay.id');//这里改成码支付ID
            $codepay_key = config('codepay.key'); //这是您的通讯密钥
            $data = array(
                "id" => $codepay_id,//你的码支付ID
                "pay_id" => $orderNo, //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
                "type" => 1,//1支付宝支付 3微信支付 2QQ钱包
                "price" => $price,//金额100元
                "param" => $orderNo,//自定义参数
                "notify_url" => config('codepay.notify_url'),//通知地址
                "return_url" => config('codepay.cardBuyReturn_url') . "?tt=" . time() . "&orderNo=" . $orderNo,//跳转地址
            ); //构造需要传递的参数
        }
        //微信通知URL
        $Notify_url = config('notify_url');
        switch (input("payType")) {
            case "weixin":
                //echo "你选择了微信支付";
                require VENDOR_PATH . '/wxpay/WxPay.Api.php'; //引入微信支付
                require VENDOR_PATH . '/wxpay/WxPay.NativePay.php'; //引入微信支付
                $input = new \WxPayUnifiedOrder();//统一下单
                $notify = new \NativePay();
                $config = new \WxPayConfig();//配置参数
                $input->SetBody("购买软件" . $soft->name);
                $input->SetAttach($orderNo);
                $input->SetOut_trade_no($orderNo);
                $input->SetTotal_fee($price * 100);
                $input->SetTime_start(date("YmdHis"));
                $input->SetTime_expire(date("YmdHis", time() + 600));
                $input->SetGoods_tag(1);
                $input->SetNotify_url($Notify_url);
                $input->SetTrade_type("NATIVE");
                $input->SetProduct_id("00000001");
                $result = $notify->GetPayUrl($input);
                if ($result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS') {
                    $link = url('admin/wxpay/pay', ['code_url' => base64_encode($result["code_url"]), 'orderno' => $orderNo]);
                } else {
                    return json(["msg" => "微信支付参数错误", "code" => -4]);
                }
                break;
            case "alipay":
                //echo "你选择了支付宝支付";
                $data['type'] = 1;
                $pay_mode = 1;
                break;
            case "qq":
                // echo "你选择了qq支付";
                $data['type'] = 2;
                $pay_mode = 2;
                break;
            default:
                return json(["msg" => "未选择支付方式", "code" => -3]);
        }
        //码支付生成支付链接
        if (input("payType") == "alipay" || input("payType") == "qq") {
            ksort($data); //重新排序$data数组
            reset($data); //内部指针指向数组中的第一个元素

            $sign = ''; //初始化需要签名的字符为空
            $urls = ''; //初始化URL参数为空

            foreach ($data AS $key => $val) { //遍历需要传递的参数
                if ($val == '' || $key == 'sign') continue; //跳过这些不参数签名
                if ($sign != '') { //后面追加&拼接URL
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
                $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

            }
            $query = $urls . '&sign=' . md5($sign . $codepay_key); //创建订单所需的参数
            if ($this->request->scheme() == "http") {
                $link = "http://api2.yy2169.com:52888/creat_order/?{$query}"; //支付页面
            } else {
                $link = "https://codepay.fateqq.com/creat_order/?{$query}"; //支付页面
            }
        }
        $payArr = array(
            'uid' => $soft->uid,
            'orderno' => $orderNo,
            'money' => $price,
            'pay_mode' => $pay_mode,
            'status' => 1,//1 未支付订单
            'type' => 2,//收入类型
            'order_time' => time()

        );
        //写到数据库
        $pr = PayRecord::create($payArr);
        if ($pr) {
            //返回支付链接
            $data = [
                'pid' => $pr->id,
                'sid' => $soft->id,
                'sname' => $soft->name,
                'cardId' => $cardTypeId,
                'num' => $num,
                'money' => $price,
                'email' => input('email'),
                'authorid' => $soft->uid
            ];
            BuycardRecord::create($data);
            //return json([ "msg"=>"订单创建完成","code"=>0,"link"=>$link]);
            $this->redirect($link);
        } else {
            $this->error('订单创建完成', "/buyCard/?id=" . $sid);
        }
    }

    function tk($pid = "")
    {
        $orderNo = input('orderNo');
        if (empty($orderNo) && empty($pid)) return "订单ID错误";
        if (empty($pid)) {
            $pr = PayRecord::getByorderno($orderNo);
            if (empty($pr)) return "没有找到支付记录";
            $pid = $pr->id;
        }
        //找到售卡记录
        $br = BuycardRecord::getBypid($pid);
        if (!$br) return "没有找到售卡记录." . $pid;
        $list = Cards::where(["bid" => $br->id])->select();
        if (count($list) == 0) return "没有找到卡密记录,码支付可能会慢几秒,可点此 <button onclick='window.location.reload();'>刷新查看</button>";
        echo "以下为您购买的卡密信息<br />";
        echo "软件名称:" . $br->sname . "<hr />";
        foreach ($list as $data) {
            echo $data['card_no'] . "<br />";
        }
        echo "<hr>";
    }

    //支付记录ID提卡
    function tkByPid()
    {
        $pid = input('pid');
        if ($pid < 1) return "销售ID为空";
        return $this->tk($pid);
    }

    function sendCard($payId, $orderNo)
    {
        $br = BuycardRecord::getByPid($payId);
        if ($br->status == 1) {
            $list = Cards::where(["bid" => $br->id])->select();
            foreach ($list as $data) {
                echo $data['card_no'] . "<br/>";
            }
            return "已经发过货啦";
        }
        //取出软件ID,卡密类型ID bid,数量,邮箱
        //给卡密打上销售表ID
        $ct = CardType::get($br->cardId);
        $where = [
            'sid' => $br->sid,
            'type' => $ct->type,
            'status' => 0,
            'bid' => 0
        ];
        $list = Cards::where($where)->limit($br->num)->select();
        $arr = [];
        $key = "以下是您购买的卡密信息<br />订单号:" . $orderNo . "<br />";
        for ($i = 0; $i < count($list); $i++) {
            $tmp = [
                "id" => $list[$i]['id'],
                "bid" => $br->id,
                "sell" => 0
            ];
            $arr[$i] = $tmp;
            // $list[$i]['status']=1;
            //echo $list[$i]['id']."-".$list[$i]['card_no']."<br />";
            $key = $key . $list[$i]['card_no'] . "   <br />";
        }
        if (strlen($br->email) > 4) {
            //发送卡密到邮箱
            sendMail($br->email, "购买的卡密", $key);
        }
        $cards = new Cards;
        $cards->isUpdate()->saveAll($arr);
        $br->status = 1;
        $br->save();
    }

    //售卡日志页面
    function sellLog()
    {
        $user = Session::get('user');
        if (empty($user)) {
            $this->assign('title', "超时");
            $this->assign('keywords', "超时");
            return $this->fetch('index/timeout');
        }
        $this->assign('user', $user);
        $this->assign('title', $user->username . " - 登录记录");
        $this->assign('keywords', $user->username . "- 登录记录");
        return $this->fetch('card/sellLog');
    }

    //售卡日志json
    function getSellList()
    {
        $user = Session::get('user');
        if (empty($user))
            return json(["msg" => '登录超时', "code" => -1]);
        $pageSize = input('limit');
        $sname = input('sname');
        $id = input('id');
        $where = [
            'b.authorid' => $user->id,
        ];
        if (!empty($sname)) {
            $where['b.sname'] = ['like', '%' . $sname . '%'];
        }

        //查询销售记录和卡密类型
        $br = new BuycardRecord;
        // ->join('soft_list u', 'u.id=a.sid and a.authorid =2')
        $list = $br->alias("b")
            ->join("card_type c", "c.id=b.cardId", "LEFT")
            ->field("b.*,c.type,c.cardValue")
            ->where($where)
            ->limit(input('offset'), $pageSize)
            ->order(['b.id' => 'desc'])
            ->select();
        $total = $br->alias("b")->where($where)->count();
        return json(["total" => $total, "rows" => $list]);
    }

    //查找订单
    function searchOrder()
    {
        $email = input('email');
        $orderno = input('orderno');
        $list = [];
        if (empty($orderno) && strlen($email) < 5) {
            //没有传参的情况
        }
        if (!empty($orderno)) {
            $list = PayRecord::getByorderno($orderno);
            if (empty($list)) return "订单号不存在";
            $this->redirect('/tk.html?orderNo=' . $orderno);
            //订单号方式就查询出卡密 直接返回
        }
        if (strlen($email) > 5) {
            //邮件方式就查询出所有订单BID
            $br = BuycardRecord::where(['email' => $email, 'status' => 1])->select();
            if (empty($br)) return "该联系方式,没有任何完成订单";
            foreach ($br as $data) {
                echo "<a href='/admin/card/tkByPid?pid=" . $data['pid'] . "'>订单ID:" . $data['id'] . " 软件名称:" . $data['sname'] . " 购卡数量:" . $data['num'] . " 订单金额:" . $data['money'] . "</a><br />";
            }
            return;
        }
        $this->assign('title', "订单查询");
        $this->assign('keywords', "订单查询");
        return $this->fetch('card/searchOrder');
    }

    function test()
    {
        //卡密发货
        //$card = new Card();
        // $card->sendCard(97,"2019050522351110297995");
    }
}