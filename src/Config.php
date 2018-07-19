<?php

namespace biliboobrian\lumenAngularCodeGenerator;

use biliboobrian\lumenAngularCodeGenerator\Exception\GeneratorException;

/**
 * Class Config
 * @package biliboobrian\lumenAngularCodeGenerator
 */
class Config
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Config constructor.
     * @param array $inputConfig
     * @throws GeneratorException
     */
    public function __construct(array $inputConfig)
    {
        $inputConfig = $this->resolveKeys($inputConfig);

        if (isset($inputConfig['config'])) {
            if (isset($inputConfig['config']) && file_exists($inputConfig['config'])) {
                $fileConfig = require $inputConfig['config'];

                $userConfig = $this->merge($inputConfig, $fileConfig);
            } else {
                throw new GeneratorException('Config file does not exist');
            }

            unset($userConfig['config']);
        } else {
            $userConfig = $inputConfig;
        }

        $this->config = $this->merge($userConfig, $this->getBaseConfig());
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->config[$key] : $default;
    }
    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed|null
     */
    public function set($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->config[$key]);
    }

    /**
     * @param array $high
     * @param array $low
     * @return array
     */
    protected function merge(array $high, array $low)
    {
        foreach ($high as $key => $value)
        {
            if ($value !== null) {
                $low[$key] = $value;
            }
        }

        return $low;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function resolveKeys(array $array)
    {
        $resolved = [];
        foreach ($array as $key => $value) {
            $resolvedKey = $this->resolveKey($key);
            $resolved[$resolvedKey] = $value;
        }

        return $resolved;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function resolveKey($key)
    {
        return str_replace('-', '_', strtolower($key));
    }

    /**
     * @return array
     */
    protected function getBaseConfig()
    {
        return require __DIR__ . '/Resources/config.php';
    }
}
