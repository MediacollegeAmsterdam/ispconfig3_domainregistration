<?php

namespace Domainregistration\Util;

class SettingsStore
{
    const TABLE = 'domainregistration_config';
    const COLUMN_CONFIG_KEY = 'config_key';
    const COLUMN_CONFIG_VALUE = 'config_value';

    /**
     * @var \db
     */
    private $db;

    /**
     * @param \db $db
     * @return void
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function get($key)
    {
        $data = $this->db->queryOneRecord(
            'SELECT ?? FROM ?? WHERE ?? = ?',
            self::COLUMN_CONFIG_VALUE,
            self::TABLE,
            self::COLUMN_CONFIG_KEY,
            $key
        );

        if (!empty($data['config_value'])) {
            return $data['config_value'];
        }

        return null;
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set($key, $value)
    {
        if (null === $this->get($key)) {
            $this->insert($key, $value);
        } else {
            $this->update($key, $value);
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    private function insert($key, $value)
    {
        $this->db->query(
            'INSERT INTO ?? (??, ??) VALUES (?, ?)',
            self::TABLE,
            self::COLUMN_CONFIG_KEY,
            self::COLUMN_CONFIG_VALUE,
            $key,
            $value
        );
    }

    /**
     * @param string $key
     * @param string $value
     * @return void
     */
    private function update($key, $value)
    {
        $this->db->query(
            'UPDATE ?? SET ?? = ? WHERE ?? = ?',
            self::TABLE,
            self::COLUMN_CONFIG_VALUE,
            $value,
            self::COLUMN_CONFIG_KEY,
            $key
        );
    }
}
