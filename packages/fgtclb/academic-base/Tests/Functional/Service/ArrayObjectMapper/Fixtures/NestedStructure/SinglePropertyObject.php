<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\NestedStructure;

/**
 * Example object used as direct property type for {@see RootObject::$singlePropertyObject}.
 *
 * @see ArrayObjectMapperTest::rootObjectIsCreatedWithExpectedNestedDataMatchingPropertyCase()
 */
final class SinglePropertyObject
{
    public function __construct(
        public readonly string $identifier,
        public readonly bool $enabled = false,
    ) {}
}
