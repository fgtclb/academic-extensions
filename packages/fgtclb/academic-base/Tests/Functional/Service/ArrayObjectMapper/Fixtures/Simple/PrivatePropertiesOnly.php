<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;

/**
 * DTO Object to test public property mapping.
 * {@see SimpleObjectTest::dataWithMatchingPropertyCasingMapsToPrivateProperties()}
 */
final class PrivatePropertiesOnly
{
    private ?string $privateTypedString = null;
    private ?bool $privateTypedBool = null;
    private ?int $privateTypedInt = null;
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
