<?php

/*
 * This file is part of Icicle, a library for writing asynchronous code in PHP using coroutines built with awaitables.
 *
 * @copyright 2014-2015 Aaron Piotrowski. All rights reserved.
 * @license MIT See the LICENSE file that was distributed with this source code for more information.
 */

namespace Icicle\Tests\Loop\Watcher;

use Icicle\Loop\{Manager\TimerManager, Watcher\Timer};
use Icicle\Tests\TestCase;

class TimerTest extends TestCase
{
    const TIMEOUT = 0.1;
    
    protected $manager;
    
    public function setUp()
    {
        $this->manager = $this->getMock(TimerManager::class);
    }
    
    public function createTimer($interval, $periodic, callable $callback, $data = null)
    {
        return new Timer($this->manager, $interval, $periodic, $callback, $data);
    }

    public function testGetInterval()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));
        
        $this->assertSame(self::TIMEOUT, $timer->getInterval());
    }
    
    /**
     * @depends testGetInterval
     */
    public function testInvalidInterval()
    {
        $timer = $this->createTimer(-1, false, $this->createCallback(0));
        
        $this->assertGreaterThanOrEqual(0, $timer->getInterval());
    }
    
    public function testCall()
    {
        $callback = $this->createCallback(2);
        $callback->method('__invoke')
            ->with($this->isInstanceOf(Timer::class));

        $timer = $this->createTimer(self::TIMEOUT, false, $callback);
        
        $timer->call();
        $timer->call();
    }
    
    /**
     * @depends testCall
     */
    public function testInvoke()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(2));
        
        $timer();
        $timer();
    }
    
    public function testIsPending()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('isPending')
            ->with($this->identicalTo($timer))
            ->will($this->returnValue(true));
        
        $this->assertTrue($timer->isPending());
    }
    
    public function testIsPeriodic()
    {
        $timer = $this->createTimer(self::TIMEOUT, true, $this->createCallback(0));
        
        $this->assertTrue($timer->isPeriodic());
        
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));
        
        $this->assertFalse($timer->isPeriodic());
    }

    public function testStart()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));

        $this->manager->expects($this->once())
            ->method('start')
            ->with($this->identicalTo($timer));

        $timer->start();
    }

    public function testStop()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('stop')
            ->with($this->identicalTo($timer));
        
        $timer->stop();
    }

    /**
     * @depends testStart
     */
    public function testAgain()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));

        $this->manager->expects($this->once())
            ->method('isPending')
            ->with($this->identicalTo($timer))
            ->will($this->returnValue(true));

        $this->manager->expects($this->exactly(2))
            ->method('start')
            ->with($this->identicalTo($timer));

        $this->manager->expects($this->once())
            ->method('stop')
            ->with($this->identicalTo($timer));

        $timer->start();

        $timer->again();
    }

    /**
     * @depends testCall
     */
    public function testSetCallback()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));

        $callback = $this->createCallback(1);
        $callback->method('__invoke')
            ->with($this->isInstanceOf(Timer::class));

        $timer->setCallback($callback);

        $timer->call();
    }

    public function testUnreference()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('unreference')
            ->with($this->identicalTo($timer));
        
        $timer->unreference();
    }
    
    public function testReference()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));
        
        $this->manager->expects($this->once())
            ->method('reference')
            ->with($this->identicalTo($timer));
        
        $timer->reference();
    }

    /**
     * @depends testUnreference
     * @depends testStart
     */
    public function testStartAfterUnreference()
    {
        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0));

        $this->manager->expects($this->exactly(2))
            ->method('unreference')
            ->with($this->identicalTo($timer));

        $this->manager->expects($this->once())
            ->method('start')
            ->with($this->identicalTo($timer));

        $timer->unreference();

        $timer->start();
    }
    
    public function testData()
    {
        $data = 'data';

        $timer = $this->createTimer(self::TIMEOUT, false, $this->createCallback(0), $data);
        
        $this->assertSame($data, $timer->getData());

        $timer->setData($data = 'test');

        $this->assertSame($data, $timer->getData());
    }
}
