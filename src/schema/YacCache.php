<?php

namespace Frft\Yiiext\schema;

/**
 * YAC 缓存 实现 schema 的进程缓存。 需要搭配主动清理脚本
 * 参见 Yac 的文档
 *
 * [
 *  'schemaCache' => [
 *  'class' => YacCache::class,
 *  'prefix' => 'schema_',
 *  ],
 * ]
 */
class YacCache extends \yii\caching\Cache implements \yii\caching\CacheInterface
{

    private ?\Yac $yacInstance = null;

    public string $prefix = '';

    public function init()
    {
        parent::init();
        if (!extension_loaded('yac')) {
            throw new \RuntimeException('YAC extension is required.');
        }
        if (null === $this->yacInstance) {
            $this->yacInstance = new \Yac($this->prefix);
        }
    }

    protected function getValue($key)
    {
        return $this->yacInstance->get($key);
    }

    protected function setValue($key, $value, $duration)
    {
        $this->yacInstance->set($key, $value, $duration);
    }

    protected function addValue($key, $value, $duration)
    {
        throw new \RuntimeException('NO Implement');
    }

    protected function deleteValue($key)
    {
        $this->yacInstance->delete($key);
    }

    protected function flushValues()
    {
        $this->yacInstance->flush();
    }
}