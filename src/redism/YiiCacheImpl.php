<?php

namespace Frft\Yiiext\redism;

use Yii;

class YiiCacheImpl extends \yii\caching\Cache
{
    /**
     * @var RedisExtPsrCache|array
     */
    public $psrCache;

    public function init()
    {
        $this->psrCache = Yii::createObject($this->psrCache);
    }

    protected function getValue($key)
    {
        return $this->psrCache->get($key);
    }

    protected function setValue($key, $value, $duration)
    {
        return $this->psrCache->set($key, $value, $duration);
    }

    protected function addValue($key, $value, $duration)
    {
        return $this->psrCache->set($key, $value, $duration);
    }

    protected function deleteValue($key)
    {
        return $this->psrCache->delete($key);
    }

    protected function flushValues()
    {
        return false;
    }
}