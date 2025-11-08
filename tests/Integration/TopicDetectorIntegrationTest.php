<?php

namespace TheCoder\MonologTelegram\Tests\Integration;

use Illuminate\Support\Facades\Route;
use Mockery;
use TheCoder\MonologTelegram\Attributes\EmergencyAttribute;
use TheCoder\MonologTelegram\Tests\TestCase;
use TheCoder\MonologTelegram\TopicDetector;

class TopicDetectorIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testGetTopicByAttributeReturnsNullWhenNoRouteContext(): void
    {
        Route::shouldReceive('current')->andReturn(null);

        $detector = new TopicDetector([
            EmergencyAttribute::class => 12345,
        ]);

        $record = [
            'message' => 'Test message',
            'context' => [],
            'level_name' => 'INFO',
        ];

        $result = $detector->getTopicByAttribute($record);

        $this->assertNull($result, 'Should return null when no route context exists');
    }

    public function testGetTopicByAttributeReturnsNullWhenNoException(): void
    {
        Route::shouldReceive('current')->andReturn(null);

        $detector = new TopicDetector([
            EmergencyAttribute::class => 12345,
        ]);

        $record = [
            'message' => 'Test message',
            'context' => ['user_id' => 123],
            'level_name' => 'INFO',
        ];

        $result = $detector->getTopicByAttribute($record);

        $this->assertNull($result, 'Should return null when no exception in context');
    }

    public function testGetTopicByAttributeWithRouteContext(): void
    {
        $route = Mockery::mock();
        $route->shouldReceive('getAction')->andReturn([
            'controller' => 'App\Http\Controllers\TestController@index',
            'uses' => 'App\Http\Controllers\TestController@index',
        ]);

        Route::shouldReceive('current')->andReturn($route);

        $this->app->singleton('livewire', function () {
            $livewire = Mockery::mock();
            $livewire->shouldReceive('isLivewireRequest')->andReturn(false);
            return $livewire;
        });

        $detector = new TopicDetector([
            EmergencyAttribute::class => 999,
        ]);

        $record = [
            'message' => 'Test',
            'context' => [],
            'level_name' => 'INFO',
        ];

        $result = $detector->getTopicByAttribute($record);

        $this->assertNull($result);
    }

    public function testGetTopicByAttributeWithoutControllerInRoute(): void
    {
        $route = Mockery::mock();
        $route->shouldReceive('getAction')->andReturn([
            'uses' => function () {
                return 'closure response';
            },
        ]);

        Route::shouldReceive('current')->andReturn($route);

        $detector = new TopicDetector([]);

        $record = [
            'message' => 'Closure route test',
            'context' => [],
            'level_name' => 'INFO',
        ];

        $result = $detector->getTopicByAttribute($record);

        $this->assertNull($result, 'Should return null for closure routes without controller');
    }

    public function testAppRunningInConsoleDetection(): void
    {
        $this->app['env'] = 'testing';

        Route::shouldReceive('current')->andReturn(null);

        $detector = new TopicDetector([]);

        $record = [
            'message' => 'Console test',
            'context' => [],
            'level_name' => 'INFO',
        ];

        $result = $detector->getTopicByAttribute($record);

        $this->assertNull($result);
    }
}
