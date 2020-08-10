<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/*
# Introduction
This is only example code for studying GC.

# Class Definition

<Main Class>
- ExampleGc extends Command: describe about GC. extends Laravel's Command Class

<Other Classes>
- Base
- AliveInScope extends Base
- CircularReference extends Base
*/

/**
 * Main Class
 */
class ExampleGc extends Command
{
    protected $signature = 'example:gc';
    protected $description = 'run example for study Garbage Collection of PHP
                                ./artisan example:gc | cut -d "$" -f 1';

    public function __construct()
    {
        parent::__construct();

        Log::debug(null, ['invoke' => __METHOD__]);
        $this->logMemUsage();
    }
    public function __destruct()
    {
        Log::debug(null, ['invoke' => __METHOD__]);
        $this->logMemUsage();
    }

    /**
     * Starting Point
     */
    public function handle()
    {
        $strPartition = str_repeat('-', 50);
        ini_set('memory_limit', '256M');
        Log::debug(null, ['memory_limit' => ini_get('memory_limit')]);

        Log::debug($strPartition);
        Log::debug(null, ['event' => 'doExampleZval', 'msg' => 'about Article 2']);
        $this->doExampleZval();
        Log::debug($strPartition);
        Log::debug(null, ['event' => 'doGcExampleBasic', 'msg' => 'about Article 3,4,5,6']);
        $this->doGcExampleBasic();
        Log::debug($strPartition);
        Log::debug(null, ['event' => 'doGcExampleCycle', 'msg' => 'about Article 6']);
        $this->doGcExampleCycle();

        Log::debug(null, ['event' => 'gc_collect_cycles', 'msg' => 'can call gc cycle forcibly. but be careful of using it']);
        Log::debug(null, ['event' => 'gc_collect_cycles', 'msg' => 'because gc cycle may can cause using many system resource & stopping program in doing gc cycle.']);
        gc_collect_cycles();
    }

    /**
     * Example to
     * 1. explain zval container & how to debug zval. debug zval of some data type.
     */
    public function doExampleZval()
    {
        Log::debug(null, ['event' => 'start', 'msg' => __METHOD__]);

        //Literal
        Log::debug(null, ['event' => 'set', 'msg' => 'value copy of string variable']);
        $str = 'sometext';
        $toBeCopiedAsString = $str;
        xdebug_debug_zval('str');
        xdebug_debug_zval('toBeCopiedAsString');

        //Object & Unset
        Log::debug(null, ['event' => 'set', 'msg' => 'reference copy of object']);
        $object = new \stdClass;
        $toBeRefCopyFromObject = $object;
        $toBeUnset = $object;
        xdebug_debug_zval('object');
        xdebug_debug_zval('toBeRefCopyFromObject');
        xdebug_debug_zval('toBeUnset');

        Log::debug(null, ['event' => 'unset', 'msg' => 'reference copy of object']);
        unset($toBeUnset);
        xdebug_debug_zval('object');
        xdebug_debug_zval('toBeRefCopyFromObject');
        xdebug_debug_zval('toBeUnset');

        //Array
        Log::debug(null, ['event' => 'set', 'msg' => 'reference copy of array']);
        $array = array_fill(0, 2, 0);
        $toBeRefFromArray = $array;
        xdebug_debug_zval('array');
        xdebug_debug_zval('toBeRefFromArray');

        Log::debug(null, ['event' => 'change', 'msg' => 'make to value copy of array']);
        $array[1] = 1;
        xdebug_debug_zval('array');
        xdebug_debug_zval('toBeRefFromArray');
    }

    /**
     * Example to explain
     * 1. Variable Lifetime
     * 2. Memory Leak
     * 3. Root Buffer (root zval buffer)
     * 4. When Garbage Collector works
     * 5. How Garbage Collector works (basics)
     */
    private function doGcExampleBasic()
    {
        Log::debug(null, ['event' => 'start', 'msg' => __METHOD__]);
        $this->logMemUsage();

        Log::debug(null, ['event' => 'new', 'msg' => 'V']);
        $alive = new AliveInScope('V');
        xdebug_debug_zval('alive');
        $this->logMemUsage();

        Log::debug(null, ['event' => 'new', 'msg' => 'A, B']);
        $circleA = new CircularReference('A');
        $circleB = new CircularReference('B');

        Log::debug(null, ['event' => 'set', 'msg' => '$alive`s reference to A']);
        $circleA->addNode($alive);

        Log::debug(null, ['event' => 'set', 'msg' => 'circluar reference on A B']);
        $circleA->addNode($circleB);
        $circleB->addNode($circleA);

        xdebug_debug_zval('alive');
        xdebug_debug_zval('circleA');
        xdebug_debug_zval('circleB');
        $this->logMemUsage();

        Log::debug(null, ['event' => 'unset', 'msg' => 'A, B']);
        unset($circleA);
        unset($circleB);

        xdebug_debug_zval('alive');
        xdebug_debug_zval('circleA');
        xdebug_debug_zval('circleB');
        $this->logMemUsage();

        Log::debug(null, ['event' => 'invoke', 'msg' => 'garbage collection']);
        $this->makeGarbageReferenceTo10000CountWhichPHPDefaultRootBufferMax();

        xdebug_debug_zval('alive');
        $this->logMemUsage();

        Log::debug(null, ['event' => 'end', 'msg' => __METHOD__]);
    }

    /**
     * Example to
     * 1. How Garbage Collector works
     */
    private function doGcExampleCycle()
    {
        Log::debug(null, ['event' => 'start', 'msg' => __METHOD__]);
        $this->logMemUsage();

        Log::debug(null, ['event' => 'new & set', 'msg' => 'V, X & V→X']);
        $alive = new AliveInScope('V');
        $alive->addNode(new \stdClass('X'));

        Log::debug(null, ['event' => 'new & set', 'msg' => 'F, E & F←→E']);
        $circleB = new CircularReference('F');
        $circleA = new CircularReference('E');
        $circleB->addNode($circleA);
        $circleA->addNode($circleB);

        Log::debug(null, ['event' => 'new & set', 'msg' => 'D & D→E']);
        $circleB = new CircularReference('D');
        $circleB->addNode($circleA);

        Log::debug(null, ['event' => 'new & set', 'msg' => 'C & C←→D']);
        $circleA = new CircularReference('C');
        $circleB->addNode($circleA);
        $circleA->addNode($circleB);

        Log::debug(null, ['event' => 'set', 'msg' => 'V←→C']);
        $alive->addNode($circleA);
        $circleA->addNode($alive);

        Log::debug(null, ['event' => 'new & set', 'msg' => 'B & B→C']);
        $circleB = new CircularReference('B');
        $circleB->addNode($circleA);

        Log::debug(null, ['event' => 'new & set', 'msg' => 'A & A←→B']);
        $circleA = new CircularReference('A');
        $circleB->addNode($circleA);
        $circleA->addNode($circleB);

        $this->logMemUsage();
        Log::debug(null, ['event' => 'unset', 'msg' => 'A, B']);
        unset($circleA);
        unset($circleB);

        $this->logMemUsage();

        Log::debug(null, ['event' => 'invoke', 'msg' => 'garbage collection']);
        $this->makeGarbageReferenceTo10000CountWhichPHPDefaultRootBufferMax();
        $this->logMemUsage();
    }

    /**
     * invoke Garbage Collection to make & unset 10000 of self referenced object's zval.
     * it store to Root Buffer because refCount is 1 Altough life time is ended because refered by itself.
     * When Root Buffer is filled to 10000, Garbage Collection will occur if GC_ROOT_BUFFER_MAX_ENTRIES=10000
     */
    private function makeGarbageReferenceTo10000CountWhichPHPDefaultRootBufferMax()
    {
        $PHP_DEFAULT_GC_ROOT_BUFFER_MAX_ENTRIES = 10000;

        for ($i = 0; $i < $PHP_DEFAULT_GC_ROOT_BUFFER_MAX_ENTRIES; $i++) {
            $a = new \stdClass;
            $a->selfRef = $a;
        }
    }

    /**
     * print Memory Usage
     */
    private function logMemUsage()
    {
        Log::debug(null, ['Memory Usage(Bytes)' => number_format(memory_get_usage())]);
    }
}

/*
--- Other Classes
*/

/**
 * Make Dummy Data & Has childNodes as member.
 */
abstract class Base
{
    private $dummyData;
    private $tag = null;
    private $nodes = array();

    public function __construct($tag)
    {
        $this->tag = $tag;
        $this->dummyData = str_repeat('a', 20*1024*1024); //20M Byte size approximately
    }

    /**
     * add Reference as ChildNode
     */
    public function addNode(object $obj)
    {
        $this->nodes[] = $obj;
        return $this;
    }
}

class AliveInScope extends Base
{
}

class CircularReference extends Base
{
}
