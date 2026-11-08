# 卡世界开放平台 API 文档

文档版本：`v2026.11.08`

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

## 四、手机号快速接入与完整流程

### POST /platform/quick-start 新手机号快速接入（公开）

未登录用户只需提交新的 11 位手机号，系统会自动注册账号、生成首组 `app_key / app_secret` 并返回 7 天 `token`，返回后即为登录状态。账号初始余额为 0。

- 同 IP 每小时最多 3 次，同手机号并发创建会被拦截。
- 已注册手机号返回「该手机号已注册，请登录后在控制台创建密钥」，不会读取或追加他人账号的密钥。
- 此接口不需要 `app_key / timestamp / sign`。

```bash
curl -X POST "https://www.kashijie.com/open-api/platform/quick-start" \
  -H "Content-Type: application/json" \
  -d '{"mobile":"你的11位手机号"}'
```

成功响应 `data`：`token`、`mobile`、`app_key`、`app_secret`、`balance`。请妥善保存密钥，后续下单、订单查询和余额查询仍需完整密钥参数。

### 完整调用流程

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


---

## 六、酒店预订 API（查询公开，交易需签名）

酒店搜索、详情、房型报价和可订检查均可匿名查询，同 IP 限 60 次/分钟；主动携带 `app_key` 时必须带完整有效的 `timestamp / sign`。酒店订单从当前 `app_key` 对应的开放平台余额扣费，不使用 C 端支付宝收银台；酒店确认失败自动退回余额。金额字段单位为分。

### 1. 查询接口（公开，无需密钥）

| 接口 | 方法 | 说明 |
|---|---|---|
| `/v1/hotel/search` | GET | 酒店搜索 |
| `/v1/hotel/detail` | GET | 酒店详情 |
| `/v1/hotel/rooms` | GET | 房型与实时报价 |
| `/v1/hotel/validate` | POST | 可订检查 |

常用业务参数：

| 参数 | 适用接口 | 必填 | 说明 |
|---|---|---|---|
| `cityName` | search | 是 | 城市名，如「北京」 |
| `checkInDate` / `checkOutDate` | search / detail / rooms / validate | 是 | 入住 / 离店日期，YYYY-MM-DD |
| `pageIndex` / `pageSize` | search | 否 | 页码与每页数量 |
| `queryText` | search | 否 | 酒店名、商圈等关键词 |
| `hotelID` | detail / rooms / validate | 是 | 搜索接口返回的酒店 ID |
| `ratePlanID` / `roomNum` | validate | 是 | 报价产品 ID 与间数 |

酒店查询保留上游 `success / data / msg` 响应结构，不以卡券接口的 `code=0` 判断。

匿名搜索示例（日期请替换为实际入住计划）：

```bash
curl -G "https://www.kashijie.com/open-api/v1/hotel/search" \
  --data-urlencode "cityName=北京" \
  --data-urlencode "checkInDate=2026-11-10" \
  --data-urlencode "checkOutDate=2026-11-11" \
  --data-urlencode "pageIndex=1" \
  --data-urlencode "pageSize=15"
```

### 2. 创建酒店订单（需密钥）

`POST /v1/hotel/order/create`，JSON body。

| 参数 | 必填 | 说明 |
|---|---|---|
| `hotelID` / `ratePlanID` | 是 | 酒店 ID / 房型报价产品 ID |
| `checkInDate` / `checkOutDate` | 是 | 入住 / 离店日期 |
| `roomNum` | 是 | 间数，1-9 |
| `guest_names` | 是 | 入住人姓名，多个用英文逗号分隔 |
| `contactName` / `contactMobile` | 是 | 联系人姓名 / 11 位手机号 |
| `arriveHour` / `remark` | 否 | 预计到店小时 / 订单备注 |
| `attach` | 否 | 自定义附加信息 |

平台先实时询价并创建未支付订单，再扣余额并提交酒店履约。

### 3. 查询酒店订单（需密钥）

`POST /v1/hotel/order/query`，参数为 `order_no` 与鉴权参数。`supplier_data` 中包含酒店确认状态和失败原因，处理中建议每 10-30 秒轮询。

---

## 七、电影票 API（查询公开，交易需签名）

电影城市、影片、影院、场次、放映日期、详情和座位图均可匿名查询，同 IP 限 60 次/分钟；主动携带 `app_key` 时必须带完整有效的 `timestamp / sign`。电影订单先锁座取得实际成本，再扣开放平台余额并出票；出票失败自动退款。金额字段单位为分。

### 1. 查询接口（均为 POST，公开无需密钥）

| 业务 | 路径 |
|---|---|
| 城市 | `/v1/movie/city` |
| 热映 / 待映影片 | `/v1/movie/films`、`/v1/movie/wait-list` |
| 行政区 / 影院品牌 | `/v1/movie/region`、`/v1/movie/brand` |
| 影院列表 | `/v1/movie/cinemas` |
| 场次 / 放映日期 | `/v1/movie/schedule`、`/v1/movie/showdate` |
| 影片 / 影院详情 | `/v1/movie/film-info`、`/v1/movie/cinema-info` |
| 场次详情 / 座位图 | `/v1/movie/show`、`/v1/movie/seats` |

业务参数使用上一级查询返回的 ID，如 `cityId`、`filmId`、`cinemaId`、`showId`。电影浏览查询成功时 `code=200`。

```bash
curl -X POST "https://www.kashijie.com/open-api/v1/movie/city" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### 2. 创建电影票订单（需密钥）

`POST /v1/movie/order/create`，JSON body。

| 参数 | 必填 | 说明 |
|---|---|---|
| `showId` | 是 | 场次 ID |
| `buy_seats` | 是 | 座位对象 JSON 数组字符串，1-5 个座位 |
| `phoneNo` | 是 | 11 位订票手机号 |
| `attach` | 否 | 自定义附加信息 |

`buy_seats` 示例：`[{"seatId":"seat_xxx"}]`。**不要直接传数组**，数组会按签名规则跳过；JSON 字符串可确保座位数据参与签名。

### 3. 查询电影票订单（需密钥）

`POST /v1/movie/order/query`，参数为 `order_no` 与鉴权参数。出票成功后，取票序列号和验证码位于 `supplier_data.tickets`。

### 酒店／电影订单统一状态

| status | 说明 |
|---|---|
| `recharging` | 已扣款，酒店确认中 / 电影出票中 |
| `success` | 预订成功 / 已出票 |
| `refunded` | 履约失败，余额已退回 |
