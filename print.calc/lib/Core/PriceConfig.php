<?php

namespace PrintCalc\Core;

class PriceConfig
{
    private array $config;

    public function __construct()
    {
        $this->config = require_once __DIR__ . '/../../config/prices.php';
    }

    /**
     * Получить значение из конфига по ключу
     * 
     * @param string $key Ключ конфигурации (может быть вложенным, например: "paper.80.0")
     * @param mixed $default Значение по умолчанию, если ключ не найден
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Установить значение в конфиге
     * 
     * @param string $key Ключ конфигурации
     * @param mixed $value Новое значение
     * @return void
     */
    public function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $lastKey = array_pop($keys);
        $current = &$this->config;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current[$lastKey] = $value;
    }

    /**
     * Получить весь конфиг
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }
}
