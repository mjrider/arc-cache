<?php

/*
 * This file is part of the Ariadne Component Library.
 *
 * (c) Muze <info@muze.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace arc;

/**
 * Class cache
 * @package arc
 * @requires \arc\path
 * @requires \arc\context
 */
class cache
{
    /**
     * This method creates a new cache store ( \arc\cache\Store )
     * It will store the cache on disk in a folder defined by ARC_CACHE_DIR, or if not
     * defined in the system temp dir under arc/cache/.
     * @param string $prefix  Optional. A prefix name or path for subsequent cache images
     * @param mixed  $timeout Optional. Number of seconds (int) or string parseable by strtotime. Defaults to 7200.
     * @param object $context Optional. A context container (e.g. \arc\lambda\Prototype) from which the
     *                        starting path is retrieved ( $context->arcPath )
     */
    public static function create($prefix = null, $timeout = 7200)
    {
        if (!defined('ARC_CACHE_DIR')) {
            define( 'ARC_CACHE_DIR', sys_get_temp_dir().'/arc/cache' );
        }
        if (!file_exists( ARC_CACHE_DIR )) {
            @mkdir( ARC_CACHE_DIR, 0770, true );
        }
        if (!file_exists( ARC_CACHE_DIR )) {
            throw new \arc\ExceptionConfigError('Cache Directory does not exist ( '.ARC_CACHE_DIR.' )', \arc\exceptions::CONFIGURATION_ERROR);
        }
        if (!is_dir( ARC_CACHE_DIR )) {
            throw new \arc\ExceptionConfigError('Cache Directory is not a directory ( '.ARC_CACHE_DIR.' )', \arc\exceptions::CONFIGURATION_ERROR);
        }
        if (!is_writable( ARC_CACHE_DIR )) {
            throw new \arc\ExceptionConfigError('Cache Directory is not writable ( '.ARC_CACHE_DIR.' )', \arc\exceptions::CONFIGURATION_ERROR);
        }
        if (!$prefix) { // make sure you have a default prefix, so you won't clear other prefixes unintended
            $prefix = 'default';
        }
        $context = \arc\context::$context;
        $fileStore = new cache\FileStore( ARC_CACHE_DIR . '/' . $prefix, $context->arcPath );

        return new cache\Store( $fileStore, $context, $timeout );
    }

    /**
     * This method creates a new cache store, if one is not available in \arc\context yet, stores it in \arc\context
     * and returns it.
     * @return cache\Store
     */
    public static function getCacheStore()
    {
        $context = \arc\context::$context;
        if (!$context->arcCacheStore) {
            $context->arcCacheStore = self::create();
        }

        return $context->arcCacheStore;
    }

    /**
     * This reroutes any static calls to \arc\cache to the cache store instance in \arc\context
     * @param $name
     * @param $args
     * @return mixed
     * @throws ExceptionMethodNotFound
     */
    public static function __callStatic($name, $args)
    {
        $store = self::getCacheStore();
        if (method_exists( $store, $name )) {
            return call_user_func_array( array( $store, $name), $args);
        } else {
            throw new \arc\ExceptionMethodNotFound('Method ' . $name . ' not found in Cache Store', \arc\exceptions::OBJECT_NOT_FOUND);
        }
    }

    /**
     * Creates a new caching proxy object for any given object.
     * @param       $object The object to cache
     * @param mixed $cacheControl Either an integer with the number of seconds to cache stuff, or a closure that returns an int.
     *                            The closure is called with one argument, an array with the following information:
     *                            - target      The cached object a method was called on.
     *                            - method      The method called
     *                            - arguments   The arguments to the method
     *                            - result      The result of the method
     * @return cache\Proxy
     */
    public static function proxy($object, $cacheControl = 7200)
    {
        return new cache\Proxy( $object, self::getCacheStore(), $cacheControl );
    }
}
