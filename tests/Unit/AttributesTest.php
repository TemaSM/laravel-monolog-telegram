<?php

namespace TheCoder\MonologTelegram\Tests\Unit;

use PHPUnit\Framework\TestCase;
use TheCoder\MonologTelegram\Attributes\AbstractTopicLevelAttribute;
use TheCoder\MonologTelegram\Attributes\CriticalAttribute;
use TheCoder\MonologTelegram\Attributes\DebugAttribute;
use TheCoder\MonologTelegram\Attributes\EmergencyAttribute;
use TheCoder\MonologTelegram\Attributes\ImportantAttribute;
use TheCoder\MonologTelegram\Attributes\InformationAttribute;
use TheCoder\MonologTelegram\Attributes\LowPriorityAttribute;
use TheCoder\MonologTelegram\Attributes\TopicLogInterface;

class AttributesTest extends TestCase
{
    public function testEmergencyAttributeImplementsInterface(): void
    {
        $attribute = new EmergencyAttribute();

        $this->assertInstanceOf(TopicLogInterface::class, $attribute);
        $this->assertInstanceOf(AbstractTopicLevelAttribute::class, $attribute);
    }

    public function testCriticalAttributeImplementsInterface(): void
    {
        $attribute = new CriticalAttribute();

        $this->assertInstanceOf(TopicLogInterface::class, $attribute);
        $this->assertInstanceOf(AbstractTopicLevelAttribute::class, $attribute);
    }

    public function testImportantAttributeImplementsInterface(): void
    {
        $attribute = new ImportantAttribute();

        $this->assertInstanceOf(TopicLogInterface::class, $attribute);
        $this->assertInstanceOf(AbstractTopicLevelAttribute::class, $attribute);
    }

    public function testDebugAttributeImplementsInterface(): void
    {
        $attribute = new DebugAttribute();

        $this->assertInstanceOf(TopicLogInterface::class, $attribute);
        $this->assertInstanceOf(AbstractTopicLevelAttribute::class, $attribute);
    }

    public function testInformationAttributeImplementsInterface(): void
    {
        $attribute = new InformationAttribute();

        $this->assertInstanceOf(TopicLogInterface::class, $attribute);
        $this->assertInstanceOf(AbstractTopicLevelAttribute::class, $attribute);
    }

    public function testLowPriorityAttributeImplementsInterface(): void
    {
        $attribute = new LowPriorityAttribute();

        $this->assertInstanceOf(TopicLogInterface::class, $attribute);
        $this->assertInstanceOf(AbstractTopicLevelAttribute::class, $attribute);
    }

    public function testGetTopicIdReturnsCorrectTopicWhenClassFound(): void
    {
        $attribute = new EmergencyAttribute();
        $topicsLevel = [
            EmergencyAttribute::class => '12345',
            CriticalAttribute::class => '67890',
        ];

        $result = $attribute->getTopicId($topicsLevel);

        $this->assertSame('12345', $result);
    }

    public function testGetTopicIdReturnsNullWhenClassNotFound(): void
    {
        $attribute = new EmergencyAttribute();
        $topicsLevel = [
            CriticalAttribute::class => 67890,
        ];

        $result = $attribute->getTopicId($topicsLevel);

        $this->assertNull($result);
    }

    public function testGetTopicIdReturnsStringTopicId(): void
    {
        $attribute = new CriticalAttribute();
        $topicsLevel = [
            CriticalAttribute::class => '999',
        ];

        $result = $attribute->getTopicId($topicsLevel);

        $this->assertSame('999', $result);
    }

    public function testGetTopicIdHandlesEmptyArray(): void
    {
        $attribute = new DebugAttribute();
        $topicsLevel = [];

        $result = $attribute->getTopicId($topicsLevel);

        $this->assertNull($result);
    }

    public function testDifferentAttributesReturnDifferentTopicIds(): void
    {
        $emergency = new EmergencyAttribute();
        $critical = new CriticalAttribute();

        $topicsLevel = [
            EmergencyAttribute::class => '111',
            CriticalAttribute::class => '222',
        ];

        $this->assertSame('111', $emergency->getTopicId($topicsLevel));
        $this->assertSame('222', $critical->getTopicId($topicsLevel));
    }

    public function testAttributeClassesAreFinal(): void
    {
        $reflection = new \ReflectionClass(EmergencyAttribute::class);
        $this->assertTrue($reflection->isFinal());

        $reflection = new \ReflectionClass(CriticalAttribute::class);
        $this->assertTrue($reflection->isFinal());

        $reflection = new \ReflectionClass(ImportantAttribute::class);
        $this->assertTrue($reflection->isFinal());

        $reflection = new \ReflectionClass(DebugAttribute::class);
        $this->assertTrue($reflection->isFinal());

        $reflection = new \ReflectionClass(InformationAttribute::class);
        $this->assertTrue($reflection->isFinal());

        $reflection = new \ReflectionClass(LowPriorityAttribute::class);
        $this->assertTrue($reflection->isFinal());
    }

    public function testAttributeClassesHaveAttributeAttribute(): void
    {
        $reflection = new \ReflectionClass(EmergencyAttribute::class);
        $attributes = $reflection->getAttributes(\Attribute::class);

        $this->assertCount(1, $attributes);
    }
}
