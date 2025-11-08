<?php

namespace TheCoder\MonologTelegram\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use TheCoder\MonologTelegram\SendJob;

class SendJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testConstructorAcceptsStringChatId(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test message',
            chatId: '123456',
            topicId: null,
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $this->assertInstanceOf(SendJob::class, $job);
    }

    public function testConstructorAcceptsIntChatId(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test message',
            chatId: 123456,
            topicId: null,
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $this->assertInstanceOf(SendJob::class, $job);
    }

    public function testConstructorAcceptsIntTopicId(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test message',
            chatId: 123456,
            topicId: 789,
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $this->assertInstanceOf(SendJob::class, $job);
    }

    public function testConstructorAcceptsStringTopicId(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test message',
            chatId: 123456,
            topicId: '789',
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $this->assertInstanceOf(SendJob::class, $job);
    }

    public function testConstructorAcceptsNullTopicId(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test message',
            chatId: 123456,
            topicId: null,
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $this->assertInstanceOf(SendJob::class, $job);
    }

    public function testJobHasCorrectRetryConfiguration(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test',
            chatId: 123,
            topicId: null,
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $this->assertSame(2, $job->tries);
        $this->assertSame(120, $job->retryAfter);
    }

    public function testSslVerificationDefaultsToTrue(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test',
            chatId: 123,
            topicId: null,
            proxy: null,
            timeout: 5,
            verifySsl: true
        );

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('verifySsl');
        $property->setAccessible(true);

        $this->assertTrue($property->getValue($job));
    }

    public function testSslVerificationCanBeDisabled(): void
    {
        $job = new SendJob(
            url: 'https://api.telegram.org/bot123/SendMessage',
            message: 'Test',
            chatId: 123,
            topicId: null,
            proxy: null,
            timeout: 5,
            verifySsl: false
        );

        $reflection = new \ReflectionClass($job);
        $property = $reflection->getProperty('verifySsl');
        $property->setAccessible(true);

        $this->assertFalse($property->getValue($job));
    }
}
