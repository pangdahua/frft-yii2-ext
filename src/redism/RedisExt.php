<?php

namespace FRFT\Yiiext\redism;

use Yii;
use RuntimeException;

/**
 * php Redis wrapper for yii2-redis
 * 使用 PHPREDIS 扩展
 *
 * [
 *      'redis' => RedisExt::class,
 * ]
 */
class RedisExt extends \yii\base\Component
{
    public string $hostname;

    public int $port = 6379;

    public ?string $password = null;

    public int $database = 0;

    public float $timeout = 1.0;

    /**
     * 是否使用长连接。 默认开始
     * 使用长连接会触发
     */
    public bool $usePconnect = true;

    /**
     * 因为长链接会触发数据库切换，所以需要记录当前使用的数据库，防止切换
     *
     * @var int
     */
    private static int $curDatabase;

    /**
     * @var \Redis
     */
    private $_instance;

    private $_isConnected = false;


    public function init()
    {
        parent::init();

        if (!extension_loaded('redis')) {
            throw new RuntimeException('Redis extension is not loaded.');
        }

        $this->_instance = new \Redis();
        self::$curDatabase = $this->database;
    }

    private function connect()
    {
        if ($this->_isConnected) {
            return;
        } else if ($this->usePconnect) {
            $this->_instance->pconnect($this->hostname, $this->port, $this->timeout);
        } else {
            $this->_instance->connect($this->hostname, $this->port, $this->timeout);
        }
        if ($this->password !== null) {
            $this->_instance->auth($this->password);
        }
    }

    public function selectDatabase()
    {
        if (self::$curDatabase !== $this->database) {
            $this->_instance->select(self::$curDatabase);
        }
        self::$curDatabase = $this->database;
    }

    public function __call($name, $arguments)
    {
        $this->connect();
        return call_user_func_array([$this->_instance, $name], $arguments);
    }
}