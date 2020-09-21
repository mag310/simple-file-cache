# Simple File Cache
Simple file`s cache component, implementing PSR-16

## Installation
- Run this command: `composer require mag310/simple-file-cache`

## Usages
Simple example
```php
        $cache = new FileCache();

        $cache->set('custom_key', 'sample data');
        $data = $cache->get('custom_key');
        $cache->delete('custom_key');
```

Using categories:
```php
    $category = 'category';
    $cache = new FileCache();

    $cache->set($category . '/key1', 'data1');
    $cache->set($category . '/key2', 'data2');

    $cache->delete($category);
```

### For Documentations
[psr/simple-cache-implementation](https://packagist.org/providers/psr/simple-cache-implementation)

## Running Tests

We are using `PHPUnit` for testing the module. Do the following: 

- Run `./vendor/bin/phpunit`
