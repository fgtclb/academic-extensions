<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper;

use FGTCLB\AcademicBase\Tests\Functional\Service\AbstractArrayObjectMapperTestCase;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\NestedStructure\ArrayItem;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\NestedStructure\RootObject;
use PHPUnit\Framework\Attributes\Test;

final class NestedObjectStructureTest extends AbstractArrayObjectMapperTestCase
{
    #[Test]
    public function rootObjectIsCreatedWithExpectedNestedDataMatchingPropertyCase(): void
    {
        $data = [
            'single_property_object' => [
                'identifier' => 'single-property-object-1',
                'enabled' => true,
            ],
            'array_items' => [
                [
                    'identifier' => 'array-item-001',
                    'type' => 'type-one',
                ],
                [
                    'identifier' => 'array-item-002',
                    'type' => 'type-two',
                    'enabled' => true,
                ],
            ],
        ];
        $rootObject = $this->createSubject()->map($data, RootObject::class);
        $this->assertInstanceOf(RootObject::class, $rootObject);
        $this->assertSame('single-property-object-1', $rootObject->singlePropertyObject->identifier);
        $this->assertTrue($rootObject->singlePropertyObject->enabled);
        $this->assertCount(2, $rootObject->arrayItems);
        $firstItem = $rootObject->arrayItems[0];
        $this->assertInstanceOf(ArrayItem::class, $firstItem);
        $this->assertSame('array-item-001', $firstItem->identifier);
        $this->assertSame('type-one', $firstItem->type);
        $this->assertFalse($firstItem->enabled);
        $secondItem = $rootObject->arrayItems[1];
        $this->assertInstanceOf(ArrayItem::class, $secondItem);
        $this->assertSame('type-two', $secondItem->type);
        $this->assertTrue($secondItem->enabled);
    }
}
