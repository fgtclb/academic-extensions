<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;

/**
 * Simple DTO to test mapping on protected properties on object without constructor or setter.
 * {@see SimpleObjectTest::dataWithMatchingPropertyCasingMapsToNonPublicPropertiesWithoutGetterAndSetterAndConstructor()}
 */
final class NonPublicPropertiesWithoutGetterAndSetterAndConstructor
{
    protected ?string $protectedTypedString = null;
    protected ?bool $protectedTypedBool = null;
    protected ?int $protectedTypedInt = null;
    protected ?float $protectedTypedFloat = null;
}
