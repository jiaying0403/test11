<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '__pattern__' => [
        'name' => '\w+',
    ],
    '[hello]'     => [
        ':id'   => ['index/index', ['method' => 'get'], ['id' => '\d+']],
        ':name' => ['index/index', ['method' => 'post']],
    ],
    '' => 'admin/index/index', //首页
    'index' => 'admin/index/index', //首页
    'index_v1'=>'admin/index/index_v1',
    'userInfo'=>'admin/index/userInfo',
    'doLogin'=>'admin/index/doLogin',
    'doRegister'=>'admin/index/doRegister',
    'doSendMsg'=>'admin/index/doSendMsg',
    'form_avatar'=>'admin/index/form_avatar',
    'profile'=>'admin/user/profile',
    'doChangePwd'=>'admin/user/doChangePwd',
    'forgotpassword'=>'admin/user/forgotpassword',
    'moneylist'=>'admin/user/moneylist',
     'soft_list'=>'admin/soft/softList',
    'webgateway'=>'admin/api/webgateway',
    'buyCard'=>'admin/card/buyCard',
    'tk'=>'admin/card/tk',
    'moneylistGM'=>'admin/user/moneylistGM',
];
