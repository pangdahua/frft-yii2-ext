<?php

namespace Frft\Yiiext\captcha;

use Yii;

/**
 * 随机验证码验证器
 */
class RandomCaptcha extends \yii\base\Component
{
    /**
     * Redis 组件 ID
     * @var string
     */
    public string $redis;

    /**
     * @var int 最大验证次数 防止暴力破解
     */
    public int $maxValidateCount = 5;

    /**
     * @var int 验证码长度
     */
    public int $length = 6;

    /**
     * @var int 验证码过期时间 (S)
     */
    public int $expire = 300;

    /**
     * @var int 验证码间隔时间 (S)
     */
    public int $interval = 60;

    /**
     * @var string 验证码键前缀
     */
    public string $keyPrefix = 'random_captcha_';

    public function make(string $phone): string|false
    {
        /**
         * @var \Redis
         */
        $redis = Yii::$app->get($this->redis);
        $key = $this->getKey($phone);
        $data = $redis->hGetAll($key);
        if ($data) {
            if (time() < $data['time'] + $this->interval) { // 间隔时间内不能重复获取
                return false;
            }
        }
        $data = [
            'code' => (string)mt_rand(100000, 999999),
            'validCount' => 0,
            'time' => time(),
        ];
        $redis->hset($key, $data);
        $redis->expire($key, $this->expire);

        return $data['code'];
    }

    public function check(string $phone, string $code, bool $deleteCode = false): bool
    {
        try {
            /**
             * @var \Redis
             */
            $redis = Yii::$app->get($this->redis);
            $key = $this->getKey($phone);
            $data = $redis->hGetAll($key);
            if (!$data) {
                return false;
            } else {
                $validCount = $redis->hincrby($key, 'validCount', 1);
                if ($validCount > $this->maxValidateCount) {
                    $deleteCode = true;
                    return false;
                }

                return $data['code'] == $code;
            }

        } finally {
            if ($deleteCode) {
                $redis->del($key);
            }
        }
    }

    private function getKey(string $phone): string
    {
        return $this->keyPrefix . $phone;
    }
}