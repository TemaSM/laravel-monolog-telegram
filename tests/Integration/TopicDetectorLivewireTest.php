<?php

namespace TheCoder\MonologTelegram\Tests\Integration;

use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\HeaderBag;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TopicDetector;

class TopicDetectorLivewireTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetMainLivewireClassParsesPayloadCorrectly(): void
    {
        $livewirePayload = [
            'components' => [
                [
                    'snapshot' => json_encode([
                        'memo' => [
                            'name' => 'user.profile',
                            'path' => 'user/profile',
                        ],
                        'data' => []
                    ]),
                    'calls' => [
                        [
                            'method' => 'updateProfile',
                            'params' => []
                        ]
                    ]
                ]
            ]
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($livewirePayload);
        $request->headers = new HeaderBag([]);

        $this->app->instance('request', $request);

        config(['livewire.class_namespace' => 'App\\Http\\Livewire']);

        $exception = new \Exception('Test exception');
        $record = [
            'context' => ['exception' => $exception],
            'level' => 500,
        ];

        $detector = new TopicDetector($record);

        $reflection = new \ReflectionClass($detector);
        $method = $reflection->getMethod('getMainLivewireClass');
        $method->setAccessible(true);

        [$class, $methodName] = $method->invoke($detector);

        $this->assertEquals('\\App\\Http\\Livewire\\User\\Profile', $class);
        $this->assertEquals('updateProfile', $methodName);
    }

    public function testGetMainLivewireClassHandlesMalformedJson(): void
    {
        $livewirePayload = [
            'components' => [
                [
                    'snapshot' => 'invalid json{[}',
                    'calls' => []
                ]
            ]
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($livewirePayload);
        $request->headers = new HeaderBag([]);

        $this->app->instance('request', $request);

        $exception = new \Exception('Test exception');
        $record = [
            'context' => ['exception' => $exception],
            'level' => 500,
        ];

        $detector = new TopicDetector($record);

        $reflection = new \ReflectionClass($detector);
        $method = $reflection->getMethod('getMainLivewireClass');
        $method->setAccessible(true);

        [$class, $methodName] = $method->invoke($detector);

        // Should return [null, null] when JSON is malformed
        // The error should be logged via error_log()
        $this->assertNull($class);
        $this->assertNull($methodName);
    }

    public function testGetMainLivewireClassHandlesMissingComponentsKey(): void
    {
        $livewirePayload = [
            'fingerprint' => 'some-fingerprint',
            // Missing 'components' key
        ];

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('all')->andReturn($livewirePayload);
        $request->headers = new HeaderBag([]);

        $this->app->instance('request', $request);

        $exception = new \Exception('Test exception');
        $record = [
            'context' => ['exception' => $exception],
            'level' => 500,
        ];

        $detector = new TopicDetector($record);

        $reflection = new \ReflectionClass($detector);
        $method = $reflection->getMethod('getMainLivewireClass');
        $method->setAccessible(true);

        [$class, $methodName] = $method->invoke($detector);

        // Should return [null, null] when components key is missing
        $this->assertNull($class);
        $this->assertNull($methodName);
    }
}
