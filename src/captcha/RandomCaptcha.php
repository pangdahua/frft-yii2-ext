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

    const ERR_NONE = 0;

    /**
     * 验证码间隔时间内不能重复获取
     */
    const ERR_INTERVAL = 1;

    const ERR_EXPIRE = 2;

    const ERR_MAX_VALIDATE_COUNT = 3;

    /**
     * @var int
     */
    private $_errCode;

    public function getErrorCode()
    {
        return $this->_errCode;
    }

    /**
     * 如果在固定周期内已经获取过验证码，则返回 false
     *
     * @param string $phone
     * @return string|false
     * @throws \yii\base\InvalidConfigException
     */
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
                $this->_errCode = self::ERR_INTERVAL;
                return false;
            }
        }
        $data = [
            'code' => $this->genCode(),
            'validCount' => 0,
            'time' => time(),
        ];
        $redis->hset($key, $data);
        $redis->expire($key, $this->expire);

        return $data['code'];
    }

    private function genCode(): string
    {
        $code = '';
        for ($i = 0; $i < $this->length; $i++) {
            $code .= mt_rand(0, 9);
        }
        return $code;
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
                $this->_errCode = self::ERR_EXPIRE;
                return false;
            } else {
                $validCount = $redis->hincrby($key, 'validCount', 1);
                if ($validCount > $this->maxValidateCount) {
                    $deleteCode = true;
                    $this->_errCode = self::ERR_MAX_VALIDATE_COUNT;
                    return false;
                }

                if ($data['time'] + $this->expire < time()) { // 时间过期
                    $deleteCode = true;
                    $this->_errCode = self::ERR_EXPIRE;
                    return false;
                }

                return $data['code'] == $code;
            }

        } finally {
            if ($deleteCode) {
                $this->clean($phone);
            }
        }
    }

    public function clean(string $phone)
    {
        /**
         * @var \Redis
         */
        $redis = Yii::$app->get($this->redis);
        $key = $this->getKey($phone);
        $redis->del($key);
    }

    private function getKey(string $phone): string
    {
        return $this->keyPrefix . $phone;
    }
}