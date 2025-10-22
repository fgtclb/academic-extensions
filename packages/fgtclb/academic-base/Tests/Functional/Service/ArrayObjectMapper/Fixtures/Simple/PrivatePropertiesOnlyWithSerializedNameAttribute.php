<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * DTO Object to test public property mapping.
 * {@see SimpleObjectTest::dataWithMatchingSerializedNameAttributeMapsToPrivatePropertiesOnlyWithSerializedNameAttribute()}
 */
final class PrivatePropertiesOnlyWithSerializedNameAttribute
{
    #[SerializedName('private_typed_string')]
    private ?string $privateTypedString = null;
    #[SerializedName('private_typed_bool')]
    private ?bool $privateTypedBool = null;
    #[SerializedName('private_typed_int')]
    private ?int $privateTypedInt = null;
    #[SerializedName('private_typed_float')]
    private ?float $privateTypedFloat = null;

    /**
     * Not used for testing anything use-full, only added to keep PHPStan happy.
     */
    public function isNotDefault(): bool
    {
        return
            $this->privateTypedString !== null
            || $this->privateTypedBool !== null
            || $this->privateTypedInt !== null
            || $this->privateTypedFloat !== null
        ;
    }
}
