# curl 手工签名调试

排查签名问题最直接的方式：手工算 sign，逐字段对比。

## 1. 公开接口（不用签名）

```bash
curl 'https://www.kashijie.com/open-api/v1/goods?current=1&size=5'
curl 'https://www.kashijie.com/open-api/v1/goods/detail?goods_id=4353'
```

## 2. 签名接口（以余额查询为例）

```bash
APP_KEY='ak_你的app_key'
APP_SECRET='sk_你的app_secret'
TS=$(date +%s)

# 拼接：参数按 key 升序 k=v 用 & 连接，末尾追加 &secret=xxx，取 md5
RAW="app_key=${APP_KEY}&timestamp=${TS}&secret=${APP_SECRET}"
SIGN=$(printf '%s' "$RAW" | md5sum | cut -d' ' -f1)
echo "待签名串: $RAW"
echo "sign: $SIGN"

curl "https://www.kashijie.com/open-api/v1/balance?app_key=${APP_KEY}&timestamp=${TS}&sign=${SIGN}"
```

## 3. 下单（POST JSON）

```bash
# 参数：attach、goods_id、recharge_account + app_key、timestamp（升序）
RAW="app_key=${APP_KEY}&attach=demo001&goods_id=4353&recharge_account=13800000000&timestamp=${TS}&secret=${APP_SECRET}"
SIGN=$(printf '%s' "$RAW" | md5sum | cut -d' ' -f1)

curl -X POST 'https://www.kashijie.com/open-api/v1/order/create' \
  -H 'Content-Type: application/json' \
  -d "{\"app_key\":\"${APP_KEY}\",\"timestamp\":${TS},\"sign\":\"${SIGN}\",\"goods_id\":4353,\"recharge_account\":\"13800000000\",\"attach\":\"demo001\"}"
```

## 常见坑

- **签名串里不要带 sign 本身**，空值参数和数组要跳过
- `timestamp` 是**秒级**，与服务器偏差超 10 分钟会被拒（服务器时间以响应头 Date 为准校时）
- POST 用 JSON body 时，签名参数取 body 里的字段，不是 query
- md5 结果是**小写** 32 位 hex
