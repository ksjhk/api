# 卡世界开放平台 API 文档

API 根地址：`https://www.kashijie.com/open-api`

统一响应：`{"code": 0, "message": "...", "data": {...}}`，`code=0` 成功，其余失败。
**金额单位：接口中所有金额字段（cost_price / sell_price / balance）一律为「分」。**

---

## 一、签名算法（交易类接口必带）

所有签名请求必带三个参数：

| 参数 | 说明 |
|---|---|
| `app_key` | 控制台创建的密钥 Key |
| `timestamp` | 秒级时间戳，与服务器偏差超过 10 分钟拒绝 |
| `sign` | 签名串 |

计算方式：

```
1. 取全部请求参数（GET query 或 POST body），排除 sign 本身
2. 跳过空值与数组
3. 按参数名升序拼接：k1=v1&k2=v2
4. 末尾追加 &secret=你的app_secret
5. sign = md5(拼接串)（小写 32 位 hex）
```

---

## 二、货源接口（公开，无需签名）

> 匿名调用按底价（price_rate=1）返回，同 IP 限 60 次/分钟；带密钥签名调用则按该密钥的加价倍数返回 cost_price。

### GET /v1/goods 货源列表

参数：`category_id?`、`current=1`、`size=20`（匿名调用时签名参数可全不带）

响应 `data`：

```json
{
  "list": [{
    "goods_id": 4353, "goods_name": "饿了么外卖红包10元", "goods_img": "https://...",
    "face_value": "10元", "cost_price": 798, "sell_price": 999,
    "stock_num": 1234, "category_name": "外卖"
  }],
  "pagination": {"total": 106, "size": 20, "current": 1, "pages": 6},
  "amount_unit": "fen"
}
```

### GET /v1/goods/detail 商品详情

参数：`goods_id`。响应在列表字段基础上增加 `spec_value` / `tags` / `tabs`。

---

## 三、交易接口（需签名）

### GET /v1/balance 余额查询

响应 `data`：`{"balance": 597}`（分）

### POST /v1/order/create 创建订单

参数：`goods_id`、`recharge_account`（充值账号，如手机号）、`attach?`（透传字段，原样返回）

响应 `data`：`{"order_no": "OP...", "status": "recharging", "cost_price": 798, "balance": 202}`

> - `cost_price` 为底价 × 密钥加价倍数（对外报价）；实际扣款按真实底价。
> - 余额不足返回 `-9001 余额不足，请充值`。
> - 供货方发货失败自动退款，无需人工处理。

### POST /v1/order/query 订单查询

参数：`order_no`（仅可查本人订单）。充值中的订单会实时同步供货方状态；供货方失败自动退款（幂等）。

订单状态：`pending` 待提交 / `recharging` 充值中 / `success` 已到账 / `failed` 充值失败 / `refunded` 已退款。

---

## 四、完整接入流程示例

```
GET  /v1/goods?current=1&size=20                # 1. 浏览货源（公开）
GET  /v1/goods/detail?goods_id=4353             # 2. 看商品详情（公开）
GET  /v1/balance?app_key=..&timestamp=..&sign=..      # 3. 确认余额（签名）
POST /v1/order/create  {goods_id, recharge_account}   # 4. 下单扣费（签名）
POST /v1/order/query   {order_no}                     # 5. 轮询到 success
```

各语言可运行的示例代码见 [examples/](../examples)。

---

## 五、错误码

| code | 说明 |
|---|---|
| 0 | 成功 |
| 1 | 操作失败（通用） |
| 2 | 验证码错误或已过期 |
| 5 | 参数错误（含签名错误、限频） |
| 6 | 查无此数据 / 无权限 |
| 7 | token 为空 |
| 10 | 登录失效 |
| 11 | app_key 无效或已禁用 |
| -9001 | 余额不足，请充值 |
