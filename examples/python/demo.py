"""
卡世界开放平台 调用 demo：查货源 → 查余额 → 下单 → 查订单
运行：pip install requests && python demo.py
"""
from open_api_client import OpenApiClient

# 控制台 → API 密钥 创建获得
APP_KEY = 'ak_你的app_key'
APP_SECRET = 'sk_你的app_secret'

api = OpenApiClient(APP_KEY, APP_SECRET)

# 1. 浏览货源（公开接口，不传密钥也能调）
goods = api.goods(current=1, size=5)
print('货源总数:', goods['data']['pagination']['total'])
first = goods['data']['list'][0]
print(f"第一个商品: #{first['goods_id']} {first['goods_name']} 底价 {first['cost_price']} 分")

# 2. 查余额（签名）
balance = api.balance()
print('账户余额:', balance['data']['balance'], '分')

# 3. 下单（真实扣费！demo 请选最便宜商品 + 自己的账号）
# order = api.order_create(first['goods_id'], '13800000000', 'my-attach-001')
# print('订单号:', order['data']['order_no'], '状态:', order['data']['status'])

# 4. 查订单（轮询到 success / failed）
# q = api.order_query(order['data']['order_no'])
# print('订单状态:', q['data']['status'])
