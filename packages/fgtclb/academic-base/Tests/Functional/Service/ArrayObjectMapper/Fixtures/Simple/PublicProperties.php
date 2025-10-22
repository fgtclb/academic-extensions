<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;

/**
 * DTO Object to test public property mapping.
 * {@see SimpleObjectTest::dataWithMatchingPropertyCasingMapsToPublicProperties()}
 */
final class PublicProperties
{
    public ?string $protectedTypedString = null;
    public ?bool $protectedTypedBool = null;
    public ?int $protectedTypedInt = null;
    public ?float $protectedTypedFloat = null;
}
