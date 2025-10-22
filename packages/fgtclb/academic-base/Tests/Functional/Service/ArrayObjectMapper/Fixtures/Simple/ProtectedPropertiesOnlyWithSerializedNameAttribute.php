<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * DTO Object to test public property mapping.
 * {@see SimpleObjectTest::dataWithMatchingSerializedNameAttributeMapsToProtectedPropertiesOnlyWithSerializedNameAttribute()}
 */
final class ProtectedPropertiesOnlyWithSerializedNameAttribute
{
    #[SerializedName('protected_typed_string')]
    protected ?string $protectedTypedString = null;
    #[SerializedName('protected_typed_bool')]
    protected ?bool $protectedTypedBool = null;
    #[SerializedName('protected_typed_int')]
    protected ?int $protectedTypedInt = null;
    #[SerializedName('protected_typed_float')]
    protected ?float $protectedTypedFloat = null;
}
