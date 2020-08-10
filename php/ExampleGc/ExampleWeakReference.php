<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/*
# Introduction
This is only example code for studying GC & WeakReference.

# Class Definition

<Main Class>
- ExampleWeakReference extends Command: describe about WeakReference. extends Laravel's Command Class

<Other Classes>
- BlobCacheStorage : Cache Pattern Example using WeakReference. this is Mock.
*/

/**
 * Main Class
 */
class ExampleWeakReference extends Command
{
    protected $signature = 'example:weak';
    protected $description = 'run example for study WeakReference of PHP
                                ./artisan example:weak | cut -d "$" -f 1';

    /**
     * Starting Point
     *
     * Example to
     * 1. Basics. Strong Reference & Weak Reference
     * 2. Example Cache Pattern using Weak Reference
     */
    public function handle()
    {
        Log::debug(null, ['event' => 'ExampleWeak', 'msg' => 'about Article 8']);

        Log::debug(null, ['event' => 'doExampleWeakBasics', 'msg' => 'Strong Reference and Weak Reference']);
        $this->doExampleWeakBasics();

        Log::debug(null, ['event' => 'doExampleWeakReference', 'msg' => 'Strong Reference and Weak Reference']);
        $this->doExampleWeakToCachePattern();
    }

    /**
     * Example to explain
     * 1. WeakReference basics
     */
    private function doExampleWeakBasics()
    {
        //this is strong reference
        $objA = new \stdClass;
        $strongRefL1 = $objA;
        $strongRefL2 = $strongRefL1;

        //this is weak reference
        $objB = new \stdClass;
        $weakRefL1 = \WeakReference::create($objB);
        $weakRefL2 = $weakRefL1;

        xdebug_debug_zval('objA'); //to be refCount=3 because 3 strong reference
        xdebug_debug_zval('objB'); //to be refCount=1 because 1 strong reference & 2 weak reference. weak reference don't increse refCount of zval.

        //unset each Object's Origin Reference
        unset($objA);
        unset($objB);

        //NOT be cleaned up
        var_dump('*** strongRefL1', $strongRefL1);
        var_dump('*** strongRefL2', $strongRefL2);

        //BE cleaned up
        var_dump('*** weakRefL1->get()', $weakRefL1->get());
        var_dump('*** weakRefL2->get()', $weakRefL2->get());
    }

    private function doExampleWeakToCachePattern()
    {
        $storage = BlobCacheStorage::getInstance();

        $data1 = $storage->get('1');
        $data2 = $storage->get('2');
        Log::debug('datas of 1,2 are cached in BlobCacheStorage => ', $storage->getCachedKeysByArray());

        $data1 = $storage->get('1');
        Log::debug('data of 1 already cached. get from Cache => ', $storage->getCachedKeysByArray());

        unset($data1);
        Log::debug('unset $data1. cache in BlobCacheStorage will be removed because datas of 1 is not needed on anywhere. => ', $storage->getCachedKeysByArray());

        $data1 = $storage->get('1');
        Log::debug('data of 1 is cached in BloblStorage => ', $storage->getCachedKeysByArray());
    }
}

/*
--- Other Classes
*/

/**
 * Weak Reference Example of Cache Pattern.
 *
 * ※ lifeCycle of Caches is relied on reference on outside scope.
 * ※ Class Design can be changed by cases that how to handle to LifeCycle of Caches.
 */
class BlobCacheStorage
{
    private static $instance = null;

    private $cacheMap = null;

    private function __construct()
    {
        $this->cacheMap = array();
    }

    /**
     * get Instance of BlobCacheStorage
     *
     * @return object BlobCacheStorage
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new BlobCacheStorage();
        }

        return self::$instance;
    }

    /**
     * get Keys of caches available to use.
     */
    public function getCachedKeysByArray()
    {
        $keys = array();

        foreach ($this->cacheMap as $key => $weakObj) {
            if (!empty($weakObj->get())) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * get Data.
     *
     * if data is cached, return cache data.
     * if not, newly load data.
     *
     * @param string $key
     * @return object
     */
    public function get($key)
    {
        if ($this->isCached($key)) {
            Log::debug(null, ['event' => __CLASS__, 'msg' => 'cached data => '.$key]);
            return $this->cacheMap[$key];
        }

        Log::debug(null, ['event' => __CLASS__, 'msg' => 'NOT cached data => '.$key]);
        $blobObj = $this->getBlobDataObjct($key);
        $this->cacheMap[$key] = \WeakReference::create($blobObj);

        return $blobObj;
    }

    /**
     * determine requested data exists on cache
     *
     * @param string $key
     * @return bool
     */
    private function isCached($key)
    {
        return isset($this->cacheMap[$key]) && !empty($this->cacheMap[$key]) && !empty($this->cacheMap[$key]->get());
    }

    /**
     * get Blob Data from Persistant Storage.
     *
     * ※ this is example mocking code. Features you need can be implemented.
     */
    private function getBlobDataObjct($key)
    {
        $blobObject = new \stdClass;
        $blobObject->key = $key;

        return $blobObject;
    }
}
