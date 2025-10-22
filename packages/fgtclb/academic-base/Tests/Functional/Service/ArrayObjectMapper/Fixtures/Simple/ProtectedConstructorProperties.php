<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;

/**
 * DTO Object to test protect constructor property promotion.
 * {@see SimpleObjectTest::dataCanBeConstructorMappedToProtectedConstructorProperties()}
 */
final class ProtectedConstructorProperties
{
    public function __construct(
        protected ?string $protectedTypedString = null,
        protected ?bool $protectedTypedBool = null,
        protected ?int $protectedTypedInt = null,
        protected ?float $protectedTypedFloat = null,
    ) {}
}
