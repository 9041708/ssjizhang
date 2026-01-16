<?php
namespace App\Service;

use App\Service\Config;

class WeChatMiniApp
{
    /**
     * 调用微信小程序 code2Session 接口，将前端传来的 jsCode 换成 openid/unionid。
     * 返回数组：['success' => bool, 'openid' => string|null, 'unionid' => string|null, 'raw' => array, 'error' => string|null]
     */
    public static function code2Session(string $jsCode): array
    {
        $appId = Config::get('wechat.miniapp_appid');
        $secret = Config::get('wechat.miniapp_secret');
        if (!$appId || !$secret) {
            return [
                'success' => false,
                'openid' => null,
                'unionid' => null,
                'raw' => [],
                'error' => 'WeChat miniapp appid/secret not configured',
            ];
        }

        $url = 'https://api.weixin.qq.com/sns/jscode2session'
            . '?appid=' . urlencode($appId)
            . '&secret=' . urlencode($secret)
            . '&js_code=' . urlencode($jsCode)
            . '&grant_type=authorization_code';

        $resp = @file_get_contents($url);
        if ($resp === false) {
            return [
                'success' => false,
                'openid' => null,
                'unionid' => null,
                'raw' => [],
                'error' => 'Failed to request WeChat API',
            ];
        }

        $data = json_decode($resp, true) ?: [];
        if (isset($data['errcode']) && $data['errcode'] !== 0) {
            return [
                'success' => false,
                'openid' => null,
                'unionid' => null,
                'raw' => $data,
                'error' => 'WeChat API error: ' . ($data['errmsg'] ?? 'unknown'),
            ];
        }

        return [
            'success' => true,
            'openid' => $data['openid'] ?? null,
            'unionid' => $data['unionid'] ?? null,
            'raw' => $data,
            'error' => null,
        ];
    }
}
