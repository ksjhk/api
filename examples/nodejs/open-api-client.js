/**
 * 卡世界开放平台 API 客户端（Node 14+，零依赖）
 *
 * 用法见同目录 demo.js
 * 接口文档：https://github.com/ksjhk/api
 */
const crypto = require('crypto');
const https = require('https');

class OpenApiClient {
  constructor(appKey = '', appSecret = '', base = 'https://www.kashijie.com/open-api') {
    this.base = base.replace(/\/+$/, '');
    this.appKey = appKey;
    this.appSecret = appSecret;
  }

  /** 货源列表（公开，可不传密钥） */
  goods(categoryId = null, current = 1, size = 20) {
    const params = { current, size };
    if (categoryId) params.category_id = categoryId;
    return this._get('/v1/goods', params, false);
  }

  /** 商品详情（公开） */
  goodsDetail(goodsId) {
    return this._get('/v1/goods/detail', { goods_id: goodsId }, false);
  }

  /** 余额查询（签名），返回分 */
  balance() {
    return this._get('/v1/balance', {}, true);
  }

  /** 创建订单（签名）：余额扣费、自动发货、失败自动退款 */
  orderCreate(goodsId, rechargeAccount, attach = '') {
    return this._post('/v1/order/create', {
      goods_id: goodsId,
      recharge_account: rechargeAccount,
      attach,
    });
  }

  /** 订单查询（签名） */
  orderQuery(orderNo) {
    return this._post('/v1/order/query', { order_no: orderNo });
  }

  // ------------------------------------------------------------------

  _get(path, params, needSign) {
    if (needSign) params = this._signed(params);
    const qs = new URLSearchParams(params).toString();
    return this._request('GET', `${path}?${qs}`, null);
  }

  _post(path, params) {
    return this._request('POST', path, this._signed(params));
  }

  /**
   * 签名：排除 sign、跳过空值与数组 → 按 key 升序 k=v 用 & 拼接
   * → 末尾追加 &secret=app_secret → md5 小写
   */
  _signed(params) {
    const p = { ...params, app_key: this.appKey, timestamp: Math.floor(Date.now() / 1000) };
    const pairs = Object.keys(p)
      .filter((k) => p[k] !== '' && p[k] !== null && p[k] !== undefined && typeof p[k] !== 'object')
      .sort()
      .map((k) => `${k}=${p[k]}`);
    p.sign = crypto.createHash('md5').update(pairs.join('&') + '&secret=' + this.appSecret, 'utf8').digest('hex');
    return p;
  }

  _request(method, path, body) {
    const url = new URL(this.base + path);
    const payload = body ? JSON.stringify(body) : null;
    const options = {
      method,
      hostname: url.hostname,
      path: url.pathname + url.search,
      headers: payload
        ? { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(payload) }
        : {},
      timeout: 30000,
    };
    return new Promise((resolve, reject) => {
      const req = https.request(options, (res) => {
        let raw = '';
        res.on('data', (c) => (raw += c));
        res.on('end', () => {
          try {
            resolve(JSON.parse(raw));
          } catch (e) {
            reject(new Error('响应非 JSON: ' + raw));
          }
        });
      });
      req.on('error', reject);
      req.on('timeout', () => req.destroy(new Error('请求超时')));
      if (payload) req.write(payload);
      req.end();
    });
  }
}

module.exports = OpenApiClient;
