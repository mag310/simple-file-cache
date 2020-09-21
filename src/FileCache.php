<?php

namespace mag310\SimpleFileCache;

use DateInterval;
use DateTime;
use mag310\SimpleFileCache\exception\InvalidArgumentException;
use mag310\SimpleFileCache\exception\UnserializeException;
use mag310\SimpleFileCache\helpers\FileHelper;
use Psr\SimpleCache\CacheInterface;

/**
 * Simple file`s cache
 * Class Cache
 *
 * @package helper
 */
class FileCache implements CacheInterface
{
    /** @var string */
    private $path;

    /**
     * @param string $key
     * @return string
     */
    private function getCacheFileName($key)
    {
        $path = str_replace('|', '/', $key);
        $path = str_replace('//', '/null/', $path);
        return $this->path . '/' . $path . '.json';
    }

    /**
     * FileCache constructor.
     *
     * @param string $path
     */
    public function __construct($path = null)
    {
        if ($path === null) {
            $path = sys_get_temp_dir() . '/cache';
        }

        if (!file_exists($path) && !mkdir($path, 0755, true)) {
            throw new InvalidArgumentException("Error creating directory $path");
        }

        $path = realpath($path);
        if (!is_dir($path)) {
            throw new InvalidArgumentException("$path is not directory");
        }

        $this->path = $path;
    }

    /**
     * Fetches a value from the cache.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get($key, $default = null)
    {
        $fileName = $this->getCacheFileName($key);
        if (!file_exists($fileName)) {
            return $default;
        }

        $raw = file_get_contents($fileName);
        $raw = json_decode($raw, true);
        if ($raw === null) {
            throw new UnserializeException('Unserialize error');
        }

        if (array_key_exists('expire', $raw) && $raw['expire'] != 0) {
            if ($raw['expire'] < time()) {
                $this->delete($key);
                return $default;
            }
        }

        if (!array_key_exists('data', $raw)) {
            return $default;
        }

        $res = unserialize($raw['data']);
        if ($res === false) {
            throw new UnserializeException('Unserialize error');
        }

        return $res;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store. Must be serializable.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function set($key, $value, $ttl = null)
    {
        $fileName = $this->getCacheFileName($key);

        $dirName = dirname($fileName);
        if (!file_exists($dirName) && !mkdir($dirName, 0755, true)) {
            throw new InvalidArgumentException("Error creating directory $dirName");
        }

        if (!is_dir($dirName)) {
            throw new InvalidArgumentException("$dirName is not directory");
        }

        if ($ttl === null) {
            $expire = 0;
        } elseif (is_int($ttl)) {
            $expire = time() + $ttl;
        } elseif ($ttl instanceof DateInterval) {
            $secs = DateTime::createFromFormat('U', '0')->add($ttl)->format('U');
            $expire = intval($secs);
        } else {
            throw new InvalidArgumentException("Invalid TTL type");
        }

        $raw = json_encode(array('expire' => $expire, 'data' => serialize($value)));

        $res = file_put_contents($fileName, $raw, LOCK_EX);

        return $res !== false;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if the $key string is not a legal value.
     */
    public function delete($key)
    {
        $fileName = $this->getCacheFileName($key);

        if (file_exists($fileName) && is_file($fileName)) {
            return unlink($fileName);
        }

        $dirName = str_replace('.json', '', $fileName);
        if (is_dir($dirName)) {
            return FileHelper::rmdir($dirName);
        }

        throw new InvalidArgumentException("$dirName is not valid");
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success and false on failure.
     */
    public function clear()
    {
        return FileHelper::rmdir($this->path);
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     *
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as
     *     value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function getMultiple($keys, $default = null)
    {
        $values = array();
        foreach ($keys AS $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     *
     * @return bool True on success and false on failure.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $values is neither an array nor a Traversable,
     *   or if any of the $values are not a legal value.
     */
    public function setMultiple($values, $ttl = null)
    {
        $return = false;
        foreach ($values AS $key => $value) {
            $return = $this->set($key, $value, $ttl) || $return;
        }
        return $return;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *   MUST be thrown if $keys is neither an array nor a Traversable,
     *   or if any of the $keys are not a legal value.
     */
    public function deleteMultiple($keys)
    {
        $return = false;
        foreach ($keys AS $key) {
            $return = $this->delete($key) || $return;
        }
        return $return;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it, making the state of your app out of date.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     */
    public function has($key)
    {
        $fileName = $this->getCacheFileName($key);
        return file_exists($fileName);
    }
}
