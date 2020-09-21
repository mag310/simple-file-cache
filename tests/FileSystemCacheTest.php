<?php

namespace Tests;

use Exception;
use mag310\SimpleFileCache\FileCache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException;

class FileSystemCacheTest extends TestCase
{
    /**
     * @test
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function checking_data_existence_in_cache()
    {
        $cache = new FileCache();
        $cache->clear();

        $this->assertFalse($cache->has('custom_key'));

        $cache->set('custom_key', 'sample data');
        $this->assertTrue($cache->has('custom_key'));

        $cache->delete('custom_key');
        $this->assertFalse($cache->has('custom_key'));
    }

    /**
     * @test
     * @throws InvalidArgumentException
     */
    public function checking_data_storage()
    {
        $cache = new FileCache();
        $cache->clear();
        $this->assertFalse($cache->has('custom_key'));

        $cache->set('custom_key', array('key1' => '1', 'key2' => 2));
        $this->assertTrue($cache->has('custom_key'));

        $this->assertArrayHasKey('key1', $cache->get('custom_key'));
        $this->assertArrayHasKey('key2', $cache->get('custom_key'));

        // Set Cache key.
        $cache->delete('custom_key');
        $this->assertFalse($cache->has('custom_key'));
    }

    /**
     * @test
     * @throws InvalidArgumentException
     */
    public function checking_category_using()
    {
        $category = 'category';
        $cache = new FileCache();

        $cache->set($category . '/key1', 'data1');
        $cache->set($category . '/key2', 'data2');

        $this->assertTrue($cache->has($category . '/key1'));
        $this->assertTrue($cache->has($category . '/key2'));

        $cache->delete($category);

        $this->assertFalse($cache->has($category . '/key1'));
        $this->assertFalse($cache->has($category . '/key2'));
    }

    /**
     * @test
     * @throws InvalidArgumentException
     */
    public function checking_the_given_directory()
    {
        $cache = new FileCache('./tmp');
        $cache->clear();
        $this->assertFalse($cache->has('custom_key'));

        $cache->set('custom_key', 'sample data');
        $this->assertTrue($cache->has('custom_key'));

        $cache->delete('custom_key');
        $this->assertFalse($cache->has('custom_key'));

        $cache->delete('../tmp');
    }
}
