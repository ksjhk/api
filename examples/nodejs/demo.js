/**
 * 卡世界开放平台 调用 demo：查货源 → 查余额 → 下单 → 查订单
 * 运行：node demo.js
 */
const OpenApiClient = require('./open-api-client');

// 控制台 → API 密钥 创建获得
const APP_KEY = 'ak_你的app_key';
const APP_SECRET = 'sk_你的app_secret';

const api = new OpenApiClient(APP_KEY, APP_SECRET);

(async () => {
  // 1. 浏览货源（公开接口，不传密钥也能调）
  const goods = await api.goods(null, 1, 5);
  console.log('货源总数:', goods.data.pagination.total);
  const first = goods.data.list[0];
  console.log(`第一个商品: #${first.goods_id} ${first.goods_name} 底价 ${first.cost_price} 分`);

  // 2. 查余额（签名）
  const balance = await api.balance();
  console.log('账户余额:', balance.data.balance, '分');

  // 3. 下单（真实扣费！demo 请选最便宜商品 + 自己的账号）
  // const order = await api.orderCreate(first.goods_id, '13800000000', 'my-attach-001');
  // console.log('订单号:', order.data.order_no, '状态:', order.data.status);

  // 4. 查订单（轮询到 success / failed）
  // const q = await api.orderQuery(order.data.order_no);
  // console.log('订单状态:', q.data.status);
})();
