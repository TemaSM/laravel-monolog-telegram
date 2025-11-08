<?php

namespace TheCoder\MonologTelegram\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TheCoder\MonologTelegram\Attributes\EmergencyAttribute;
use TheCoder\MonologTelegram\Attributes\CriticalAttribute;
use TheCoder\MonologTelegram\TopicDetector;

class TopicDetectorTest extends TestCase
{
    public function testConstructorAcceptsEmptyTopicsLevel(): void
    {
        $detector = new TopicDetector([]);

        $this->assertInstanceOf(TopicDetector::class, $detector);
    }

    public function testConstructorAcceptsTopicsLevelArray(): void
    {
        $topicsLevel = [
            EmergencyAttribute::class => 12345,
            CriticalAttribute::class => 67890,
        ];

        $detector = new TopicDetector($topicsLevel);

        $this->assertInstanceOf(TopicDetector::class, $detector);
    }

    public function testGetTopicByAttributeReturnsNullWhenNoContext(): void
    {
        $this->markTestSkipped('Requires Laravel application bootstrap');
    }

    public function testGetTopicByAttributeReturnsNullWhenNoException(): void
    {
        $this->markTestSkipped('Requires Laravel application bootstrap');
    }

    public function testClassConstantsAreDefined(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasConstant('JOBS_NAMESPACE'));
        $this->assertTrue($reflection->hasConstant('CONSOLE_COMMANDS_NAMESPACE'));
        $this->assertTrue($reflection->hasConstant('HANDLE_METHOD'));
        $this->assertTrue($reflection->hasConstant('APP_DIRECTORY'));
        $this->assertTrue($reflection->hasConstant('APP_NAMESPACE'));
        $this->assertTrue($reflection->hasConstant('ATTRIBUTE_REGEX'));
    }

    public function testClassConstantsHaveCorrectValues(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertSame('App\Jobs', $reflection->getConstant('JOBS_NAMESPACE'));
        $this->assertSame('Console\Commands', $reflection->getConstant('CONSOLE_COMMANDS_NAMESPACE'));
        $this->assertSame('handle', $reflection->getConstant('HANDLE_METHOD'));
        $this->assertSame('app', $reflection->getConstant('APP_DIRECTORY'));
        $this->assertSame('App', $reflection->getConstant('APP_NAMESPACE'));
        $this->assertIsString($reflection->getConstant('ATTRIBUTE_REGEX'));
    }

    public function testAttributeRegexPatternIsValid(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);
        $pattern = $reflection->getConstant('ATTRIBUTE_REGEX');

        $sampleCode = '#[Emergency] public function handle()';
        $result = preg_match($pattern, $sampleCode, $matches);

        $this->assertSame(1, $result, 'Regex pattern should match attribute syntax');
    }

    public function testGetTopicByReflectionMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getTopicIdByReflection'));
    }

    public function testGetTopicByRegexMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getTopicIdByRegex'));
    }

    public function testAppRunningWithRequestMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('appRunningWithRequest'));
    }

    public function testAppRunningWithJobMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('appRunningWithJob'));
    }

    public function testAppRunningWithCommandMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('appRunningWithCommand'));
    }

    public function testGetJobClassMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getJobClass'));
    }

    public function testGetCommandClassMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getCommandClass'));
    }

    public function testGetTopicByRouteMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getTopicByRoute'));
    }

    public function testIsLivewireMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('isLivewire'));
    }

    public function testGetMainLivewireClassMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getMainLivewireClass'));
    }

    public function testGetActionClassAndMethodExists(): void
    {
        $reflection = new \ReflectionClass(TopicDetector::class);

        $this->assertTrue($reflection->hasMethod('getActionClassAndMethod'));
    }
}
