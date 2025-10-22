<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\NestedStructure;

/**
 * Example array item object used for {@see RootObject::$arrayItems}.
 *
 * @see ArrayObjectMapperTest::rootObjectIsCreatedWithExpectedNestedDataMatchingPropertyCase()
 */
final class ArrayItem
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly bool $enabled = false,
    ) {}
}
