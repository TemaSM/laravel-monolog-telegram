<?php

namespace TheCoder\MonologTelegram\Tests\Unit;

use InvalidArgumentException;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use TheCoder\MonologTelegram\TelegramBotHandler;

class TelegramBotHandlerTest extends TestCase
{
    public function testConstructorWithValidStringChatId(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: '@channel',
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug,
            bubble: true
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorWithValidIntegerChatId(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug,
            bubble: true
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorThrowsExceptionForEmptyToken(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Bot token must be a non-empty string');

        new TelegramBotHandler(
            token: '',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug
        );
    }

    public function testConstructorThrowsExceptionForNonStringToken(): void
    {
        $this->markTestSkipped('PHP 8.4 strict types prevent this test - type error occurs before validation');
    }

    public function testConstructorThrowsExceptionForInvalidChatIdType(): void
    {
        $this->markTestSkipped('PHP 8.4 strict types prevent this test - type error occurs before validation');
    }

    public function testConstructorThrowsExceptionForTimeoutTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be between 1 and 300 seconds');

        new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            timeout: 0,
            level: Level::Debug
        );
    }

    public function testConstructorThrowsExceptionForTimeoutTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Timeout must be between 1 and 300 seconds');

        new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            timeout: 301,
            level: Level::Debug
        );
    }

    public function testConstructorThrowsExceptionForInvalidProxyType(): void
    {
        $this->markTestSkipped('PHP 8.4 strict types prevent this test - type error occurs before validation');
    }

    public function testConstructorAcceptsValidProxy(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            proxy: 'http://proxy.example.com:8080',
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsNullProxy(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            proxy: null,
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsValidTimeout(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            timeout: 30,
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsCustomBotApi(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            bot_api: 'https://custom.api.com/bot',
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsSslVerificationTrue(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            verify_ssl: true,
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsSslVerificationFalse(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            verify_ssl: false,
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsStringTopicId(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: '789',
            queue: null,
            topics_level: [],
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsIntTopicId(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: 789,
            queue: null,
            topics_level: [],
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsQueueName(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: 'telegram-logs',
            topics_level: [],
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsTopicsLevelArray(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [
                'EmergencyAttribute' => 123,
                'CriticalAttribute' => 456,
            ],
            level: Level::Debug
        );

        $this->assertInstanceOf(TelegramBotHandler::class, $handler);
    }

    public function testConstructorAcceptsAllLogLevels(): void
    {
        $levels = [
            Level::Debug,
            Level::Info,
            Level::Notice,
            Level::Warning,
            Level::Error,
            Level::Critical,
            Level::Alert,
            Level::Emergency,
        ];

        foreach ($levels as $level) {
            $handler = new TelegramBotHandler(
                token: 'valid_token',
                chat_id: 123456,
                topic_id: null,
                queue: null,
                topics_level: [],
                level: $level
            );

            $this->assertInstanceOf(TelegramBotHandler::class, $handler);
        }
    }

    public function testConstructorDefaultsToHttpsApiTelegramOrg(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug
        );

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('botApi');
        $property->setAccessible(true);

        $this->assertSame('https://api.telegram.org/bot', $property->getValue($handler));
    }

    public function testConstructorDefaultTimeoutIs5Seconds(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug
        );

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('timeout');
        $property->setAccessible(true);

        $this->assertSame(5, $property->getValue($handler));
    }

    public function testConstructorDefaultSslVerificationIsTrue(): void
    {
        $handler = new TelegramBotHandler(
            token: 'valid_token',
            chat_id: 123456,
            topic_id: null,
            queue: null,
            topics_level: [],
            level: Level::Debug
        );

        $reflection = new \ReflectionClass($handler);
        $property = $reflection->getProperty('verifySsl');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($handler));
    }
}
