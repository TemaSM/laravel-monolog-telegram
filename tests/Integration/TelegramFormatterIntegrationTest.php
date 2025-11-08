<?php

namespace TheCoder\MonologTelegram\Tests\Integration;

use DateTime;
use Exception;
use Illuminate\Http\Request;
use Mockery;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\HeaderBag;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TelegramFormatter;

class TelegramFormatterIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function mockRequest(
        string $url = 'https://example.com/test',
        string $ip = '127.0.0.1',
        ?object $user = null,
        string $method = 'GET',
        bool $ajax = false,
        array $headers = [],
        array $input = []
    ): void {
        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('url')->andReturn($url);
        $request->shouldReceive('getClientIp')->andReturn($ip);
        $request->shouldReceive('user')->andReturn($user);
        $request->shouldReceive('getMethod')->andReturn($method);
        $request->shouldReceive('ajax')->andReturn($ajax);
        $request->shouldReceive('all')->andReturn($input);

        $request->shouldReceive('except')->andReturnUsing(function ($keys) use ($input) {
            return array_diff_key($input, array_flip($keys));
        });

        $request->shouldReceive('only')->andReturnUsing(function ($keys) use ($input) {
            return array_intersect_key($input, array_flip($keys));
        });

        $headerBag = new HeaderBag($headers);
        $request->headers = $headerBag;

        $this->app->instance('request', $request);
    }

    protected function mockUser(int $id = 1, string $fullName = 'Test User'): object
    {
        return (object) [
            'id' => $id,
            'fullName' => $fullName
        ];
    }

    public function testTagsAreIncludedWhenProvided(): void
    {
        $this->mockRequest(
            url: 'https://example.com/api/test',
            ip: '192.168.1.1',
            user: $this->mockUser(123, 'John Doe'),
            method: 'POST',
            ajax: true,
            headers: ['referer' => 'https://example.com'],
            input: ['action' => 'test']
        );

        $formatter = new TelegramFormatter(tags: 'production,error');
        $exception = new Exception('Test exception');

        $record = [
            'message' => 'Error occurred',
            'context' => ['exception' => $exception],
            'level' => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('#production', $result);
        $this->assertStringContainsString('#error', $result);
        $this->assertStringContainsString('Test exception', $result);
        $this->assertStringContainsString('https://example.com/api/test', $result);
        $this->assertStringContainsString('192.168.1.1', $result);
        $this->assertStringContainsString('John Doe', $result);
    }

    public function testEmptyTagsDoNotBreakFormatting(): void
    {
        $this->mockRequest(
            url: 'https://example.com',
            ip: '127.0.0.1'
        );

        $formatter = new TelegramFormatter(tags: '');
        $exception = new Exception('Empty tags test');

        $record = [
            'message' => 'Error',
            'context' => ['exception' => $exception],
            'level' => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertIsString($result);
        $this->assertStringContainsString('Empty tags test', $result);
        $this->assertStringNotContainsString('##', $result);
    }

    public function testNullTagsHandledCorrectly(): void
    {
        $this->mockRequest(
            url: 'https://example.com',
            ip: '127.0.0.1'
        );

        $formatter = new TelegramFormatter(tags: null);
        $exception = new Exception('Null tags test');

        $record = [
            'message' => 'Error',
            'context' => ['exception' => $exception],
            'level' => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'test',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertIsString($result);
        $this->assertStringContainsString('Null tags test', $result);
    }

    public function testExceptionFormattingWithUserContext(): void
    {
        $user = $this->mockUser(456, 'Jane Smith');

        $this->mockRequest(
            url: 'https://api.example.com/endpoint',
            ip: '10.0.0.1',
            user: $user,
            method: 'POST',
            ajax: false,
            headers: ['referer' => 'https://dashboard.example.com'],
            input: ['user_id' => 456, 'password' => 'secret123', 'action' => 'login']
        );

        $formatter = new TelegramFormatter(tags: 'staging');
        $exception = new Exception('Login failed', 401);

        $record = [
            'message' => 'Authentication error',
            'context' => ['exception' => $exception],
            'level' => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'auth',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('Login failed', $result);
        $this->assertStringContainsString('Jane Smith', $result);
        $this->assertStringContainsString('456', $result);
        $this->assertStringContainsString('401', $result);
        $this->assertStringContainsString('#staging', $result);
        $this->assertStringContainsString('https://api.example.com/endpoint', $result);
        $this->assertStringContainsString('10.0.0.1', $result);
        $this->assertStringContainsString('POST', $result);
        $this->assertStringContainsString('https://dashboard.example.com', $result);

        $this->assertStringNotContainsString('secret123', $result, 'Password should be masked');
    }

    public function testExceptionFormattingWithAjaxRequest(): void
    {
        $this->mockRequest(
            url: 'https://api.example.com/ajax',
            ip: '192.168.1.100',
            method: 'POST',
            ajax: true,
            input: ['data' => 'test']
        );

        $formatter = new TelegramFormatter();
        $exception = new Exception('Ajax error');

        $record = [
            'message' => 'AJAX request failed',
            'context' => ['exception' => $exception],
            'level' => Logger::ERROR,
            'level_name' => 'ERROR',
            'channel' => 'ajax',
            'datetime' => new DateTime(),
            'extra' => [],
        ];

        $result = $formatter->format($record);

        $this->assertStringContainsString('Ajax error', $result);
        $this->assertStringContainsString('POST', $result);
        $this->assertStringContainsString('(Ajax)', $result);
    }
}
