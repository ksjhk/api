<?php
/**
 * 卡世界开放平台 API 客户端（零依赖，PHP 7.2+）
 *
 * 用法见同目录 demo.php
 * 接口文档：https://github.com/ksjhk/api
 */
class OpenApiClient
{
    /** @var string API 根地址 */
    private $base;
    /** @var string */
    private $appKey;
    /** @var string */
    private $appSecret;

    public function __construct($appKey = '', $appSecret = '', $base = 'https://www.kashijie.com/open-api')
    {
        $this->base = rtrim($base, '/');
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
    }

    /** 货源列表（公开，可不传密钥） */
    public function goods($categoryId = null, $current = 1, $size = 20)
    {
        $params = ['current' => $current, 'size' => $size];
        if ($categoryId) $params['category_id'] = $categoryId;
        return $this->get('/v1/goods', $params, false);
    }

    /** 商品详情（公开） */
    public function goodsDetail($goodsId)
    {
        return $this->get('/v1/goods/detail', ['goods_id' => $goodsId], false);
    }

    /** 余额查询（签名），返回分 */
    public function balance()
    {
        return $this->get('/v1/balance', [], true);
    }

    /** 创建订单（签名）：余额扣费、自动发货、失败自动退款 */
    public function orderCreate($goodsId, $rechargeAccount, $attach = '')
    {
        return $this->post('/v1/order/create', [
            'goods_id' => $goodsId,
            'recharge_account' => $rechargeAccount,
            'attach' => $attach,
        ]);
    }

    /** 订单查询（签名） */
    public function orderQuery($orderNo)
    {
        return $this->post('/v1/order/query', ['order_no' => $orderNo]);
    }

    // ------------------------------------------------------------------

    private function get($path, $params, $needSign)
    {
        if ($needSign) $params = $this->signedParams($params);
        return $this->request('GET', $path . '?' . http_build_query($params), null);
    }

    private function post($path, $params)
    {
        return $this->request('POST', $path, $this->signedParams($params));
    }

    /**
     * 签名：排除 sign、跳过空值与数组 → 按 key 升序 k=v 用 & 拼接
     * → 末尾追加 &secret=app_secret → md5 小写
     */
    private function signedParams(array $params)
    {
        $params['app_key'] = $this->appKey;
        $params['timestamp'] = time();
        $params = array_filter($params, function ($v) {
            return $v !== '' && $v !== null && !is_array($v);
        });
        ksort($params, SORT_STRING);
        $pairs = [];
        foreach ($params as $k => $v) $pairs[] = $k . '=' . $v;
        $params['sign'] = md5(implode('&', $pairs) . '&secret=' . $this->appSecret);
        return $params;
    }

    private function request($method, $path, array $body = null)
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        $raw = curl_exec($ch);
        if ($raw === false) throw new \RuntimeException('HTTP 请求失败: ' . curl_error($ch));
        curl_close($ch);
        $res = json_decode($raw, true);
        if (!is_array($res)) throw new \RuntimeException('响应非 JSON: ' . $raw);
        return $res; // ['code'=>0, 'message'=>'...', 'data'=>...]
    }
}
