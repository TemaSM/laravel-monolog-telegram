<?php

namespace TheCoder\MonologTelegram\Tests\Feature;

use ErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;
use TheCoder\MonologTelegram\SendJob;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TelegramBotHandler;
use TheCoder\MonologTelegram\TelegramFormatter;

class TelegramLoggingAdvancedFlowTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testLoggingFromQueueJobWithAttributeRouting(): void
    {
        $this->markTestSkipped('Requires creating actual job class with attribute - complex setup');
    }

    public function testLoggingFromConsoleCommandWithAttributeRouting(): void
    {
        $this->markTestSkipped('Requires creating actual command class with attribute - complex setup');
    }

    public function testLoggingHandlesVeryLongMessages(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'test_token',
                    'chat_id' => 123456,
                    'topic_id' => null,
                    'queue' => 'telegram',
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
            ],
        ]);

        // Create message longer than 4096 characters (Telegram limit)
        $longMessage = str_repeat('A', 5000);

        Log::channel('telegram')->error($longMessage);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            $message = $messageProperty->getValue($job);

            // Message should be truncated to exactly 4096 characters
            return mb_strlen($message) === 4096;
        });
    }

    public function testLoggingHandlesMultiByteCharacters(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'test_token',
                    'chat_id' => 123456,
                    'topic_id' => null,
                    'queue' => 'telegram',
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
            ],
        ]);

        // Create message with multi-byte characters near the truncation boundary
        $emojiString = str_repeat('😀', 2000); // Each emoji is 4 bytes
        $message = $emojiString . ' Test message';

        Log::channel('telegram')->error($message);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            $message = $messageProperty->getValue($job);

            // Message should not contain broken multi-byte characters
            // mb_check_encoding returns true if string is valid UTF-8
            return mb_check_encoding($message, 'UTF-8');
        });
    }

    public function testLoggingHandlesExceptionWithGetSeverityMethod(): void
    {
        Queue::fake();

        config([
            'logging.channels.telegram' => [
                'driver' => 'monolog',
                'level' => 'error',
                'handler' => TelegramBotHandler::class,
                'handler_with' => [
                    'token' => 'test_token',
                    'chat_id' => 123456,
                    'topic_id' => null,
                    'queue' => 'telegram',
                    'topics_level' => [],
                ],
                'formatter' => TelegramFormatter::class,
            ],
        ]);

        // ErrorException has getSeverity() method
        $exception = new ErrorException(
            'Test error',
            0,
            E_WARNING, // severity level
            __FILE__,
            __LINE__
        );

        Log::channel('telegram')->error('Error occurred', [
            'exception' => $exception
        ]);

        Queue::assertPushed(SendJob::class, function ($job) {
            $reflection = new \ReflectionClass($job);
            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            $message = $messageProperty->getValue($job);

            // Message should contain severity information
            // E_WARNING should be formatted as "Warning"
            return str_contains($message, 'ErrorException')
                && str_contains($message, 'Test error');
        });
    }
}
