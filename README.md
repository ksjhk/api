# 卡世界开放平台 API

卡世界开放平台（[kashijie.com](https://www.kashijie.com)）对外接口的官方文档与调用示例。

接入后你的系统可以直接调用 **200+ 品牌会员权益卡券货源**，也可以接入**酒店实时预订**与**电影票在线选座出票**。统一使用开放平台余额结算，履约失败自动退款。

- 会员权益、酒店、电影票**只读查询免登录公开**，不注册也能先看货、先比价
- 注册登录、创建密钥后，即可签名调用下单接口，余额扣费、随充随用
- 每个密钥可设**加价倍数**（1.00~99.99），你拿到的报价 = 底价 × 倍数，方便你转售时统一加价

本仓库面向**调用方**。如果你想自己部署一套这样的分销平台（自带收款、自带供货），看配套仓库 [ksjhk/kaquan](https://github.com/ksjhk/kaquan)。

## 快速开始

```
1. 获取密钥 让用户提供本人未注册手机号，程序调用 /platform/quick-start 自动注册并生成首组密钥
2. 充值    控制台 → 余额充值（微信 / 支付宝扫码）
3. 查询    会员权益、酒店、电影只读接口可匿名调用
4. 下单    按下方签名规则拼 sign，调对应 order/create 接口
```

API 根地址：`https://www.kashijie.com/open-api`

## 接口一览

| 接口 | 方法 | 鉴权 | 说明 |
|---|---|---|---|
| `/v1/goods` | GET | 公开 | 货源列表（分类、分页） |
| `/v1/goods/detail` | GET | 公开 | 商品详情 |
| `/platform/quick-start` | POST | 公开 | 快速获取密钥：新手机号自动注册、生成首组密钥并自动登录 |
| `/v1/balance` | GET | 签名 | 余额查询 |
| `/v1/order/create` | POST | 签名 | 创建订单（余额扣费、自动发货、失败自动退款） |
| `/v1/order/query` | POST | 签名 | 订单查询（实时同步供货状态） |
| `/v1/hotel/search` 等 | GET / POST | 公开 | 酒店搜索、详情、房型报价、可订检查 |
| `/v1/hotel/order/create` | POST | 签名 | 创建酒店订单（余额扣费、酒店确认） |
| `/v1/hotel/order/query` | POST | 签名 | 查询酒店确认状态 |
| `/v1/movie/city` 等 | POST | 公开 | 城市、影片、影院、场次、座位查询 |
| `/v1/movie/order/create` | POST | 签名 | 锁座并创建电影票订单（余额扣费、自动出票） |
| `/v1/movie/order/query` | POST | 签名 | 查询出票状态与取票码 |

卡券与下单接口统一响应：`{"code":0,"message":"...","data":{...}}`，`code=0` 为成功；酒店查询保留 `success` 字段，电影浏览查询成功时 `code=200`。**金额单位一律为分。**

完整字段说明：[docs/api.md](docs/api.md)

## 签名规则（30 秒看懂）

```
1. 取全部请求参数，排除 sign 本身；query 与 body 混用时两处参数必须合并
2. 跳过空值、数组与对象
3. 按参数名升序拼接：k1=v1&k2=v2
4. 末尾追加 &secret=你的app_secret
5. sign = md5(拼接串)，小写 32 位 hex

query、JSON body、表单三种方式均可；鉴权参数放 query、业务参数放 body 时，业务参数也必须参与签名。
```

每个签名请求必带：`app_key`、`timestamp`（秒级，与服务器偏差超 10 分钟拒绝）、`sign`。

## 示例代码

| 语言 | 文件 | 说明 |
|---|---|---|
| PHP | [examples/php](examples/php) | 零依赖 Client 类 + 下单完整 demo |
| Python | [examples/python](examples/python) | requests 版 Client + demo |
| Node.js | [examples/nodejs](examples/nodejs) | 原生 https 零依赖 + demo |
| curl | [examples/curl](examples/curl) | 命令行手工签名调试 |

每个 demo 都演示：查货源（公开）→ 查余额（签名）→ 下单 → 查订单 的完整链路。客户端同时提供 `quickStart`、`hotelQuery / hotelOrderCreate / hotelOrderQuery` 与 `movieQuery / movieOrderCreate / movieOrderQuery` 方法（Python 为下划线命名）。酒店、电影查询不签名，交易方法继续签名。

酒店／电影票完整参数见 [docs/api.md](docs/api.md#六酒店预订-api查询公开交易需签名) 和 [电影接口说明](docs/api.md#七电影票-api查询公开交易需签名)。

## 错误码

| code | 说明 |
|---|---|
| 0 | 成功 |
| 2 | 验证码错误或已过期 |
| 5 | 参数错误（含签名错误、限频） |
| 11 | app_key 无效或已禁用 |
| -9001 | 余额不足，请充值 |

完整错误码表见 [docs/api.md](docs/api.md)。

## License

[MIT](LICENSE)
