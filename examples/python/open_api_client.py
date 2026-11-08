"""
卡世界开放平台 API 客户端（依赖 requests）

用法见同目录 demo.py
接口文档：https://github.com/ksjhk/api
"""
import hashlib
import time

import requests


class OpenApiClient:
    def __init__(self, app_key: str = '', app_secret: str = '',
                 base: str = 'https://www.kashijie.com/open-api'):
        self.base = base.rstrip('/')
        self.app_key = app_key
        self.app_secret = app_secret

    # ---------- 业务接口 ----------

    def goods(self, category_id=None, current=1, size=20):
        """货源列表（公开，可不传密钥）"""
        params = {'current': current, 'size': size}
        if category_id:
            params['category_id'] = category_id
        return self._get('/v1/goods', params, need_sign=False)

    def goods_detail(self, goods_id):
        """商品详情（公开）"""
        return self._get('/v1/goods/detail', {'goods_id': goods_id}, need_sign=False)

    def balance(self):
        """余额查询（签名），返回分"""
        return self._get('/v1/balance', {}, need_sign=True)

    def order_create(self, goods_id, recharge_account, attach=''):
        """创建订单（签名）：余额扣费、自动发货、失败自动退款"""
        return self._post('/v1/order/create', {
            'goods_id': goods_id,
            'recharge_account': recharge_account,
            'attach': attach,
        })

    def order_query(self, order_no):
        """订单查询（签名）"""
        return self._post('/v1/order/query', {'order_no': order_no})

    def hotel_query(self, endpoint, params=None):
        """酒店查询：search / detail / rooms / validate（公开，无需密钥）"""
        timeout = 60 if endpoint == 'rooms' else 30
        return self._get('/v1/hotel/' + endpoint, params or {}, False, timeout)

    def hotel_order_create(self, params):
        return self._post('/v1/hotel/order/create', params, 90)

    def hotel_order_query(self, order_no):
        return self._post('/v1/hotel/order/query', {'order_no': order_no}, 40)

    def movie_query(self, endpoint, params=None):
        """电影查询：city / films / cinemas / schedule / show / seats 等（公开，无需密钥）"""
        return self._post('/v1/movie/' + endpoint, params or {}, 60, need_sign=False)

    def movie_order_create(self, params):
        """buy_seats 必须是座位对象 JSON 数组字符串，确保参与签名"""
        return self._post('/v1/movie/order/create', params, 90)

    def movie_order_query(self, order_no):
        return self._post('/v1/movie/order/query', {'order_no': order_no}, 40)

    def quick_start(self, mobile):
        """新手机号快速接入：自动注册、生成首组密钥并返回 7 天 token（公开）"""
        return self._post('/platform/quick-start', {'mobile': mobile}, need_sign=False)

    # ---------- 内部 ----------

    def _get(self, path, params, need_sign, timeout=30):
        if need_sign:
            params = self._signed(params)
        r = requests.get(self.base + path, params=params, timeout=timeout)
        return r.json()

    def _post(self, path, params, timeout=30, need_sign=True):
        body = self._signed(params) if need_sign else params
        r = requests.post(self.base + path, json=body, timeout=timeout)
        return r.json()

    def _signed(self, params: dict) -> dict:
        """
        签名：排除 sign、跳过空值与数组 → 按 key 升序 k=v 用 & 拼接
        → 末尾追加 &secret=app_secret → md5 小写
        """
        params = dict(params)
        params['app_key'] = self.app_key
        params['timestamp'] = int(time.time())
        params = {k: v for k, v in params.items()
                  if v not in ('', None) and not isinstance(v, (list, dict))}
        pairs = [f'{k}={params[k]}' for k in sorted(params)]
        raw = '&'.join(pairs) + '&secret=' + self.app_secret
        params['sign'] = hashlib.md5(raw.encode('utf-8')).hexdigest()
        return params
