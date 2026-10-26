<?php
/**
 * 卡世界开放平台 调用 demo：查货源 → 查余额 → 下单 → 查订单
 * 运行：php demo.php
 */
require __DIR__ . '/OpenApiClient.php';

// 控制台 → API 密钥 创建获得
$appKey    = 'ak_你的app_key';
$appSecret = 'sk_你的app_secret';

$api = new OpenApiClient($appKey, $appSecret);

// 1. 浏览货源（公开接口，不传密钥也能调）
$goods = $api->goods(null, 1, 5);
echo "货源总数: {$goods['data']['pagination']['total']}\n";
$first = $goods['data']['list'][0];
echo "第一个商品: #{$first['goods_id']} {$first['goods_name']} 底价 {$first['cost_price']} 分\n";

// 2. 查余额（签名）
$balance = $api->balance();
echo "账户余额: {$balance['data']['balance']} 分\n";

// 3. 下单（真实扣费！demo 请选最便宜商品 + 自己的账号）
// $order = $api->orderCreate($first['goods_id'], '13800000000', 'my-attach-001');
// echo "订单号: {$order['data']['order_no']} 状态: {$order['data']['status']}\n";

// 4. 查订单（轮询到 success / failed）
// $q = $api->orderQuery($order['data']['order_no']);
// echo "订单状态: {$q['data']['status']}\n";
