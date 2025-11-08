<?php

namespace TheCoder\MonologTelegram\Tests\Feature;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Symfony\Component\HttpFoundation\HeaderBag;
use TheCoder\MonologTelegram\SendJob;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TelegramBotHandler;
use TheCoder\MonologTelegram\TelegramFormatter;

class TelegramLoggingFlowTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testEndToEndLoggingFlowWithMessageFormattingAndJobCreation(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'test_bot_token_12345',
                    'chat_id' => -1001234567890,
                    'topic_id' => null,
                    'queue' => 'telegram-logs',
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
                'formatter_with' => [
                    'tags' => 'testing,feature',
                ],
            ],
        ]);

        $this->mockRequestContext('https://api.example.com/users', '192.168.1.100');

        $exception = new Exception('Database connection failed');

        Log::channel('telegram')->error('Critical database error', [
            'exception' => $exception,
        ]);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            $message = $messageProperty->getValue($job);

            $chatIdProperty = $reflection->getProperty('chatId');
            $chatIdProperty->setAccessible(true);
            $chatId = $chatIdProperty->getValue($job);

            $urlProperty = $reflection->getProperty('url');
            $urlProperty->setAccessible(true);
            $url = $urlProperty->getValue($job);

            $this->assertStringContainsString('test_bot_token_12345', $url);
            $this->assertEquals(-1001234567890, $chatId);
            $this->assertStringContainsString('Database connection failed', $message);
            $this->assertStringContainsString('#testing', $message);
            $this->assertStringContainsString('#feature', $message);
            $this->assertStringContainsString('<b>Exception:</b> Exception', $message);
            $this->assertStringContainsString('192.168.1.100', $message);
            $this->assertStringContainsString('https://api.example.com/users', $message);

            return true;
        });

        Queue::assertPushed(SendJob::class, 1);
    }

    public function testLoggingWithRuntimeParameterOverridesAndSensitiveDataMasking(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'default_token',
                    'chat_id' => 111111,
                    'topic_id' => null,
                    'queue' => 'telegram',
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
            ],
        ]);

        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('url')->andReturn('https://api.example.com/login');
        $request->shouldReceive('getClientIp')->andReturn('10.0.0.1');
        $request->shouldReceive('user')->andReturn(null);
        $request->shouldReceive('getMethod')->andReturn('POST');
        $request->shouldReceive('ajax')->andReturn(false);

        $sensitiveInput = [
            'email' => 'user@example.com',
            'password' => 'SuperSecret123!',
            'remember' => true,
        ];

        $request->shouldReceive('except')->andReturnUsing(function ($keys) use ($sensitiveInput) {
            return array_diff_key($sensitiveInput, array_flip($keys));
        });

        $request->shouldReceive('only')->andReturnUsing(function ($keys) use ($sensitiveInput) {
            return array_intersect_key($sensitiveInput, array_flip($keys));
        });

        $request->headers = new HeaderBag([]);

        $this->app->instance('request', $request);

        $exception = new Exception('Authentication failed');

        Log::channel('telegram')->error('Login error', [
            'exception' => $exception,
            'token' => 'override_token_999',
            'chat_id' => 999999,
            'topic_id' => 555,
        ]);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);

            $urlProperty = $reflection->getProperty('url');
            $urlProperty->setAccessible(true);
            $url = $urlProperty->getValue($job);

            $chatIdProperty = $reflection->getProperty('chatId');
            $chatIdProperty->setAccessible(true);
            $chatId = $chatIdProperty->getValue($job);

            $topicIdProperty = $reflection->getProperty('topicId');
            $topicIdProperty->setAccessible(true);
            $topicId = $topicIdProperty->getValue($job);

            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            $message = $messageProperty->getValue($job);

            $this->assertStringContainsString('override_token_999', $url);
            $this->assertEquals(999999, $chatId);
            $this->assertEquals(555, $topicId);

            $this->assertStringNotContainsString('SuperSecret123!', $message, 'Password should be masked');
            $this->assertStringContainsString('user@example.com', $message);

            return true;
        });
    }

    public function testLoggingDispatchesJobToNamedQueue(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'queue_test_token',
                    'chat_id' => 123456,
                    'topic_id' => null,
                    'queue' => 'telegram-logs',
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
            ],
        ]);

        $this->mockRequestContext();

        $exception = new Exception('Queued error message');

        Log::channel('telegram')->error('Testing queue dispatch', [
            'exception' => $exception,
        ]);

        Queue::assertPushed(SendJob::class, function ($job) {
            return $job->queue === 'telegram-logs';
        });

        Queue::assertPushed(SendJob::class, 1);
    }

    public function testSynchronousDispatchUsesDispatchSyncWhenQueueIsNull(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'sync_token',
                    'chat_id' => 123456,
                    'topic_id' => null,
                    'queue' => null,
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
            ],
        ]);

        $this->mockRequestContext();

        Log::channel('telegram')->error('Sync logging test');

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);

            $chatIdProperty = $reflection->getProperty('chatId');
            $chatIdProperty->setAccessible(true);
            $chatId = $chatIdProperty->getValue($job);

            $this->assertEquals(123456, $chatId);

            return true;
        });

        Queue::assertPushed(SendJob::class, 1);
    }

    protected function mockRequestContext(
        string $url = 'https://example.com/test',
        string $ip = '127.0.0.1'
    ): void {
        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('url')->andReturn($url);
        $request->shouldReceive('getClientIp')->andReturn($ip);
        $request->shouldReceive('user')->andReturn(null);
        $request->shouldReceive('getMethod')->andReturn('GET');
        $request->shouldReceive('ajax')->andReturn(false);
        $request->shouldReceive('except')->andReturn([]);
        $request->shouldReceive('only')->andReturn([]);
        $request->headers = new HeaderBag([]);

        $this->app->instance('request', $request);
    }
}
