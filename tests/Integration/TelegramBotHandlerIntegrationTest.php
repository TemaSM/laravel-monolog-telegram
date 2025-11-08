<?php

namespace TheCoder\MonologTelegram\Tests\Integration;

use Illuminate\Support\Facades\Queue;
use Monolog\Level;
use Mockery;
use TheCoder\MonologTelegram\SendJob;
use TheCoder\MonologTelegram\TelegramBotHandler;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TopicDetector;

class TelegramBotHandlerIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testWriteMethodExtractsTopicFromDetector(): void
    {
        $this->markTestSkipped('Requires complex mock setup - covered by feature tests');
    }

    public function testWriteMethodUsesContextOverridesForToken(): void
    {
        Queue::fake();

        $handler = new TelegramBotHandler(
            token: 'default_token',
            chat_id: 123456,
            topic_id: null,
            queue: 'telegram',
            topics_level: [],
            level: Level::Debug
        );

        $record = [
            'message' => 'Test message',
            'context' => [
                'token' => 'override_token'
            ],
            'level' => Level::Error,
            'level_name' => 'ERROR',
            'channel' => 'testing',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
            'formatted' => 'Formatted test message'
        ];

        // Call write() via reflection
        $reflection = new \ReflectionClass($handler);
        $writeMethod = $reflection->getMethod('write');
        $writeMethod->setAccessible(true);
        $writeMethod->invoke($handler, $record);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $urlProperty = $reflection->getProperty('url');
            $urlProperty->setAccessible(true);

            return str_contains($urlProperty->getValue($job), 'override_token');
        });
    }

    public function testWriteMethodUsesContextOverridesForChatId(): void
    {
        Queue::fake();

        $handler = new TelegramBotHandler(
            token: 'test_token',
            chat_id: 123456,
            topic_id: null,
            queue: 'telegram',
            topics_level: [],
            level: Level::Debug
        );

        $record = [
            'message' => 'Test message',
            'context' => [
                'chat_id' => 999999
            ],
            'level' => Level::Error,
            'level_name' => 'ERROR',
            'channel' => 'testing',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
            'formatted' => 'Formatted test message'
        ];

        // Call write() via reflection
        $reflection = new \ReflectionClass($handler);
        $writeMethod = $reflection->getMethod('write');
        $writeMethod->setAccessible(true);
        $writeMethod->invoke($handler, $record);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $chatIdProperty = $reflection->getProperty('chatId');
            $chatIdProperty->setAccessible(true);

            return $chatIdProperty->getValue($job) === 999999;
        });
    }

    public function testWriteMethodUsesContextOverridesForTopicId(): void
    {
        Queue::fake();

        $handler = new TelegramBotHandler(
            token: 'test_token',
            chat_id: 123456,
            topic_id: 100,
            queue: 'telegram',
            topics_level: [],
            level: Level::Debug
        );

        $record = [
            'message' => 'Test message',
            'context' => [
                'topic_id' => 555
            ],
            'level' => Level::Error,
            'level_name' => 'ERROR',
            'channel' => 'testing',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
            'formatted' => 'Formatted test message'
        ];

        // Call write() via reflection
        $reflection = new \ReflectionClass($handler);
        $writeMethod = $reflection->getMethod('write');
        $writeMethod->setAccessible(true);
        $writeMethod->invoke($handler, $record);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $topicIdProperty = $reflection->getProperty('topicId');
            $topicIdProperty->setAccessible(true);

            return $topicIdProperty->getValue($job) === 555;
        });
    }

    public function testSendMethodDispatchesSyncWhenQueueIsNull(): void
    {
        Queue::fake();

        $handler = new TelegramBotHandler(
            token: 'test_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug
        );

        $record = [
            'message' => 'Test message',
            'context' => [],
            'level' => Level::Error,
            'level_name' => 'ERROR',
            'channel' => 'testing',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
            'formatted' => 'Formatted test message'
        ];

        // Call write() via reflection
        $reflection = new \ReflectionClass($handler);
        $writeMethod = $reflection->getMethod('write');
        $writeMethod->setAccessible(true);
        $writeMethod->invoke($handler, $record);

        Queue::assertPushed(SendJob::class, function ($job) {
            return $job->queue === null;
        });
    }

    public function testSendMethodDispatchesToNamedQueueWhenConfigured(): void
    {
        Queue::fake();

        $handler = new TelegramBotHandler(
            token: 'test_token',
            chat_id: 123456,
            topic_id: null,
            queue: 'telegram-logs',
            topics_level: [],
            level: Level::Debug
        );

        $record = [
            'message' => 'Test message',
            'context' => [],
            'level' => Level::Error,
            'level_name' => 'ERROR',
            'channel' => 'testing',
            'datetime' => new \DateTimeImmutable(),
            'extra' => [],
            'formatted' => 'Formatted test message'
        ];

        // Call write() via reflection
        $reflection = new \ReflectionClass($handler);
        $writeMethod = $reflection->getMethod('write');
        $writeMethod->setAccessible(true);
        $writeMethod->invoke($handler, $record);

        Queue::assertPushed(SendJob::class, function ($job) {
            return $job->queue === 'telegram-logs';
        });
    }
}
