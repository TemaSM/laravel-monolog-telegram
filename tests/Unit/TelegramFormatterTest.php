<?php

namespace TheCoder\MonologTelegram\Tests\Unit;

use DateTime;
use Exception;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use TheCoder\MonologTelegram\TelegramFormatter;

class TelegramFormatterTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $formatter = new TelegramFormatter();

        $this->assertInstanceOf(TelegramFormatter::class, $formatter);
    }

    public function testConstructorWithCustomFormat(): void
    {
        $formatter = new TelegramFormatter(
            html: false,
            format: '%level_name%: %message%',
            dateFormat: 'Y-m-d',
            separator: '=',
            tags: 'test,production'
        );

        $this->assertInstanceOf(TelegramFormatter::class, $formatter);
    }

    public function testFormatSimpleLogRecord(): void
    {
        $formatter = new TelegramFormatter();

        $record = [
            'message' => 'Test message',
            'context' => [],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('Test message', $result);
        $this->assertStringContainsString('INFO', $result);
        $this->assertStringContainsString('test', $result);
    }

    public function testFormatWithoutHtmlTags(): void
    {
        $formatter = new TelegramFormatter(html: false);

        $record = [
            'message' => 'Test message',
            'context' => [],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringNotContainsString('<b>', $result);
        $this->assertStringNotContainsString('</b>', $result);
    }

    public function testFormatWithContextData(): void
    {
        $formatter = new TelegramFormatter();

        $record = [
            'message' => 'Test message',
            'context' => ['user_id' => 123, 'action' => 'login'],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('Context', $result);
    }

    public function testFormatWithExtraData(): void
    {
        $formatter = new TelegramFormatter();

        $record = [
            'message' => 'Test message',
            'context' => [],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => ['ip' => '127.0.0.1', 'user_agent' => 'Mozilla'],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('Extra', $result);
    }

    public function testFormatBatchCombinesMultipleRecords(): void
    {
        $formatter = new TelegramFormatter();

        $records = [
            [
                'message' => 'First message',
                'context' => [],
                'level' => Logger::INFO,
                'level_name' => 'INFO',
                'channel' => 'test',
                'datetime' => new DateTime(),
                'extra' => [],
            ],
            [
                'message' => 'Second message',
                'context' => [],
                'level' => Logger::WARNING,
                'level_name' => 'WARNING',
                'channel' => 'test',
                'datetime' => new DateTime(),
                'extra' => [],
            ],
        ];

        $result = $formatter->formatBatch($records);

        $this->assertStringContainsString('First message', $result);
        $this->assertStringContainsString('Second message', $result);
        $this->assertStringContainsString('---------------', $result);
    }

    public function testFormatBatchWithCustomSeparator(): void
    {
        $formatter = new TelegramFormatter(separator: '*');

        $records = [
            [
                'message' => 'First',
                'context' => [],
                'level' => Logger::INFO,
                'level_name' => 'INFO',
                'channel' => 'test',
                'datetime' => new DateTime(),
                'extra' => [],
            ],
            [
                'message' => 'Second',
                'context' => [],
                'level' => Logger::INFO,
                'level_name' => 'INFO',
                'channel' => 'test',
                'datetime' => new DateTime(),
                'extra' => [],
            ],
        ];

        $result = $formatter->formatBatch($records);

        $this->assertStringContainsString('***************', $result);
    }

    public function testFormatStackTrace(): void
    {
        $formatter = new TelegramFormatter();

        $record = [
            'message' => "Error occurred\nStack trace:\n#0 test.php(10): func()\n#1 test.php(20): main()",
            'context' => [],
            'level' => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('Stack trace', $result);
        $this->assertStringContainsString('<code>', $result);
    }

    public function testCustomDateFormat(): void
    {
        $formatter = new TelegramFormatter(dateFormat: 'Y-m-d');

        $record = [
            'message' => 'Test',
            'context' => [],
            'level' => Logger::INFO,
            'level_name' => 'INFO',
            'channel' => 'test',
            'datetime' => new DateTime('2025-01-15 10:30:00'),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('2025-01-15', $result);
    }

    public function testTagsAreIncludedWhenProvided(): void
    {
        $this->markTestSkipped('Requires Laravel application context for exception formatting');
    }

    public function testEmptyTagsDoNotBreakFormatting(): void
    {
        $this->markTestSkipped('Requires Laravel application context for exception formatting');
    }

    public function testNullTagsHandledCorrectly(): void
    {
        $this->markTestSkipped('Requires Laravel application context for exception formatting');
    }
}
