<?php

namespace TheCoder\MonologTelegram\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use PHPUnit\Framework\TestCase;
use TheCoder\MonologTelegram\SendJob;

class SendJobHandleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testHandleSuccessfullySendsMessageToTelegram(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);
        $clientMock->shouldReceive('post')
            ->once()
            ->with(
                'https://api.telegram.org/bot123:token/sendMessage',
                Mockery::on(function ($options) {
                    return isset($options['form_params'])
                        && $options['form_params']['text'] === 'Test message'
                        && $options['form_params']['chat_id'] === 123456
                        && $options['form_params']['parse_mode'] === 'html'
                        && $options['form_params']['disable_web_page_preview'] === true;
                })
            )
            ->andReturn(new Response(200, [], '{"ok":true}'));

        $job = new SendJob(
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456
        );

        $job->handle();

        $this->assertTrue(true);
    }

    public function testHandleIncludesTopicIdWhenProvided(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);
        $clientMock->shouldReceive('post')
            ->once()
            ->with(
                'https://api.telegram.org/bot123:token/sendMessage',
                Mockery::on(function ($options) {
                    return isset($options['form_params']['message_thread_id'])
                        && $options['form_params']['message_thread_id'] === 789;
                })
            )
            ->andReturn(new Response(200));

        $job = new SendJob(
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456,
            789
        );

        $job->handle();

        $this->assertTrue(true);
    }

    public function testHandleOmitsTopicIdWhenNull(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);
        $clientMock->shouldReceive('post')
            ->once()
            ->with(
                'https://api.telegram.org/bot123:token/sendMessage',
                Mockery::on(function ($options) {
                    return !isset($options['form_params']['message_thread_id']);
                })
            )
            ->andReturn(new Response(200));

        $job = new SendJob(
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456,
            null
        );

        $job->handle();

        $this->assertTrue(true);
    }

    public function testHandleUsesProxyWhenConfigured(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);
        $clientMock->shouldReceive('__construct')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['proxy'])
                    && $options['proxy'] === 'http://proxy.example.com:8080';
            }));

        $clientMock->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200));

        $job = new SendJob(
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456,
            null,
            'http://proxy.example.com:8080'
        );

        $job->handle();

        $this->assertTrue(true);
    }

    public function testHandleSetsTimeoutCorrectly(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);
        $clientMock->shouldReceive('__construct')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['timeout'])
                    && $options['timeout'] === 30;
            }));

        $clientMock->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200));

        $job = new SendJob(
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456,
            null,
            null,
            30
        );

        $job->handle();

        $this->assertTrue(true);
    }

    public function testHandleEnablesSslVerificationByDefault(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);
        $clientMock->shouldReceive('__construct')
            ->once()
            ->with(Mockery::on(function ($options) {
                return isset($options['verify'])
                    && $options['verify'] === true;
            }));

        $clientMock->shouldReceive('post')
            ->once()
            ->andReturn(new Response(200));

        $job = new SendJob(
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456
        );

        $job->handle();

        $this->assertTrue(true);
    }

    public function testHandleCallsFailMethodOnGuzzleException(): void
    {
        $clientMock = Mockery::mock('overload:' . Client::class);

        $request = new Request('POST', 'https://api.telegram.org/bot123:token/sendMessage');
        $exception = new RequestException('Connection timeout', $request);

        $clientMock->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        $job = Mockery::mock(SendJob::class . '[fail]', [
            'https://api.telegram.org/bot123:token/sendMessage',
            'Test message',
            123456
        ]);

        $job->shouldReceive('fail')
            ->once()
            ->with($exception);

        $job->handle();

        $this->assertTrue(true);
    }
}
