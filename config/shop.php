<?php

return [
    'order_ttl' => 1800,
    'url' => 'http://shop.runrun520.cn/',
    // 分期费率, key 为期数, value 为费率
    'installment_fee_rate' => [
        3 => 1.5,
        6 => 2,
        12 => 2.5,
    ],
    'min_installment_amount' => 300, // 最低分期费用
    'installment_fine_rate' => 0.05, // 逾期日息 0.05%
];
