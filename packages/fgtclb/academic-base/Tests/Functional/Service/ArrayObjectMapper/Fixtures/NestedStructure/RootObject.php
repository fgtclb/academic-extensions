<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\NestedStructure;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapperTest;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Fixture class demonstrating mapping an array to a nested object structure.
 *
 * @see ArrayObjectMapperTest::rootObjectIsCreatedWithExpectedNestedDataMatchingPropertyCase()
 */
final class RootObject
{
    /**
     * @param SinglePropertyObject $singlePropertyObject
     * @param ArrayItem[] $arrayItems
     */
    public function __construct(
        #[SerializedName('single_property_object')]
        public SinglePropertyObject $singlePropertyObject,
        #[SerializedName('array_items')]
        public readonly array $arrayItems,
    ) {}
}
