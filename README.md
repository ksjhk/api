# 卡世界开放平台 API

卡世界开放平台（[kashijie.com](https://www.kashijie.com)）对外接口的官方文档与调用示例。

接入后你的系统可以直接调用 **200+ 品牌会员权益卡券货源**：视频会员、音乐会员、外卖红包、电影票、酒店民宿等，成本价结算，下单、发货、失败退款全自动。

- 货源查询**免登录公开**，不注册也能先看货、先比价
- 注册登录、创建密钥后，即可签名调用下单接口，余额扣费、随充随用
- 每个密钥可设**加价倍数**（1.00~99.99），你拿到的报价 = 底价 × 倍数，方便你转售时统一加价

本仓库面向**调用方**。如果你想自己部署一套这样的分销平台（自带收款、自带供货），看配套仓库 [ksjhk/kaquan](https://github.com/ksjhk/kaquan)。

## 快速开始

```
1. 注册    https://www.kashijie.com 注册开放平台账号（手机号 + 验证码，30 秒）
2. 建密钥  控制台 → API 密钥 → 创建，拿到 app_key / app_secret
3. 充值    控制台 → 余额充值（微信 / 支付宝扫码）
4. 调用    按下方签名规则拼 sign，调 /v1/order/create 下单
```

API 根地址：`https://www.kashijie.com/open-api`

## 接口一览

| 接口 | 方法 | 鉴权 | 说明 |
|---|---|---|---|
| `/v1/goods` | GET | 公开 | 货源列表（分类、分页） |
| `/v1/goods/detail` | GET | 公开 | 商品详情 |
| `/v1/balance` | GET | 签名 | 余额查询 |
| `/v1/order/create` | POST | 签名 | 创建订单（余额扣费、自动发货、失败自动退款） |
| `/v1/order/query` | POST | 签名 | 订单查询（实时同步供货状态） |

统一响应：`{"code":0,"message":"...","data":{...}}`，`code=0` 为成功。**金额单位一律为分。**

完整字段说明：[docs/api.md](docs/api.md)

## 签名规则（30 秒看懂）

```
1. 取全部请求参数，排除 sign 本身
2. 跳过空值与数组
3. 按参数名升序拼接：k1=v1&k2=v2
4. 末尾追加 &secret=你的app_secret
5. sign = md5(拼接串)，小写 32 位 hex
```

每个签名请求必带：`app_key`、`timestamp`（秒级，与服务器偏差超 10 分钟拒绝）、`sign`。

## 示例代码

| 语言 | 文件 | 说明 |
|---|---|---|
| PHP | [examples/php](examples/php) | 零依赖 Client 类 + 下单完整 demo |
| Python | [examples/python](examples/python) | requests 版 Client + demo |
| Node.js | [examples/nodejs](examples/nodejs) | 原生 https 零依赖 + demo |
| curl | [examples/curl](examples/curl) | 命令行手工签名调试 |

每个 demo 都演示：查货源（公开）→ 查余额（签名）→ 下单 → 查订单 的完整链路。

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
