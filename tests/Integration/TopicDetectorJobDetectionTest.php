<?php

namespace TheCoder\MonologTelegram\Tests\Integration;

use Mockery;
use TheCoder\MonologTelegram\Attributes\CriticalAttribute;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TopicDetector;

class TopicDetectorJobDetectionTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testAppRunningWithJobReturnsTrueWhenQueueWorkerBound(): void
    {
        $this->app->bind('queue.worker', function () {
            return new \stdClass();
        });

        $detector = new TopicDetector([]);

        $reflection = new \ReflectionClass($detector);
        $method = $reflection->getMethod('appRunningWithJob');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        $this->assertTrue($result);
    }

    public function testAppRunningWithJobReturnsFalseWhenQueueWorkerNotBound(): void
    {
        // Ensure queue.worker is not bound
        if ($this->app->bound('queue.worker')) {
            $this->app->offsetUnset('queue.worker');
        }

        $detector = new TopicDetector([]);

        $reflection = new \ReflectionClass($detector);
        $method = $reflection->getMethod('appRunningWithJob');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        $this->assertFalse($result);
    }

    public function testGetJobClassExtractsJobFromStackTrace(): void
    {
        $this->app->bind('queue.worker', function () {
            return new \stdClass();
        });

        $trace = [
            [
                'file' => '/app/Jobs/TestJob.php',
                'line' => 42,
                'function' => 'handle',
                'class' => 'App\Jobs\TestJob',
                'type' => '->',
                'args' => []
            ],
            [
                'file' => '/vendor/laravel/framework/Queue/Worker.php',
                'line' => 123,
                'function' => 'process',
                'class' => 'Illuminate\Queue\Worker',
                'type' => '->',
                'args' => []
            ]
        ];

        $detector = new TopicDetector([]);

        $reflection = new \ReflectionClass($detector);

        // Initialize trace property
        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($detector, $trace);

        $method = $reflection->getMethod('getJobClass');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        $this->assertEquals('App\Jobs\TestJob', $result);
    }

    public function testGetJobClassReturnsNullWhenNoJobInTrace(): void
    {
        $this->app->bind('queue.worker', function () {
            return new \stdClass();
        });

        $trace = [
            [
                'file' => '/app/Http/Controllers/TestController.php',
                'line' => 42,
                'function' => 'index',
                'class' => 'App\Http\Controllers\TestController',
                'type' => '->',
                'args' => []
            ]
        ];

        $detector = new TopicDetector([]);

        $reflection = new \ReflectionClass($detector);

        // Initialize trace property
        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($detector, $trace);

        $method = $reflection->getMethod('getJobClass');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        $this->assertNull($result);
    }

    public function testGetTopicIdByJobUsesReflectionWhenAttributeExists(): void
    {
        $this->markTestSkipped('Anonymous classes do not work reliably with PHP attribute reflection');

        $this->app->bind('queue.worker', function () {
            return new \stdClass();
        });

        $jobClass = new class {
            #[CriticalAttribute]
            public function handle()
            {
            }
        };

        $trace = [
            [
                'file' => '/app/Jobs/TestJob.php',
                'line' => 42,
                'function' => 'handle',
                'class' => get_class($jobClass),
                'type' => '->',
                'args' => []
            ]
        ];

        $detector = new TopicDetector([CriticalAttribute::class => 999]);

        $reflection = new \ReflectionClass($detector);

        // Initialize trace property
        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($detector, $trace);

        $method = $reflection->getMethod('getTopicIdByJob');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        $this->assertEquals(999, $result);
    }

    public function testGetTopicIdByJobReturnsNullWhenNoAttributeFound(): void
    {
        $this->app->bind('queue.worker', function () {
            return new \stdClass();
        });

        $jobClass = new class {
            public function handle()
            {
            }
        };

        $trace = [
            [
                'file' => '/app/Jobs/TestJob.php',
                'line' => 42,
                'function' => 'handle',
                'class' => get_class($jobClass),
                'type' => '->',
                'args' => []
            ]
        ];

        $detector = new TopicDetector([]);

        $reflection = new \ReflectionClass($detector);

        // Initialize trace property
        $traceProperty = $reflection->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($detector, $trace);

        $method = $reflection->getMethod('getTopicIdByJob');
        $method->setAccessible(true);

        $result = $method->invoke($detector);

        $this->assertNull($result);
    }
}
