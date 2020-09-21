<?php

namespace mag310\SimpleFileCache\exception;

use Psr\SimpleCache\CacheException;

/**
 * Ошибка десереализации
 * Class UnserializeException
 * @package cache
 */
class UnserializeException extends \RuntimeException implements CacheException
{
}
