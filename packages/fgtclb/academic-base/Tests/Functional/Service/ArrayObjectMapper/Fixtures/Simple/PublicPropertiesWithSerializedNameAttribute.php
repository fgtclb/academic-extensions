<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * DTO Object to test public property mapping.
 * {@see SimpleObjectTest::dataWithMatchingSerializedNameAttributeMapsToPublicProperties()}
 */
final class PublicPropertiesWithSerializedNameAttribute
{
    #[SerializedName('protected_typed_string')]
    public ?string $protectedTypedString = null;
    #[SerializedName('protected_typed_bool')]
    public ?bool $protectedTypedBool = null;
    #[SerializedName('protected_typed_int')]
    public ?int $protectedTypedInt = null;
    #[SerializedName('protected_typed_float')]
    public ?float $protectedTypedFloat = null;
}
