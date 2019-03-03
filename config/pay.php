<?php
/**
 * Created by PhpStorm.
 * User: run
 * Date: 2019/3/3
 * Time: 13:18
 */


return [
    'alipay' => [
        'app_id' => '',
        'ali_public_key' => '',
        'private_key' => '',
        'log' => [
            'file' => storage_path('logs/alipay.log')
        ]
    ],
    'wechat' => [
        'app_id' => '',
        'mch_id' => '',
        'key' => '',
        'cert_client' => '',
        'cert_key' => '',
        'log' => [
            'file' => storage_path('logs/wechat_pay.log')
        ]
    ],

];