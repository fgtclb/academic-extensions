<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple;

use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\SimpleObjectTest;

/**
 * DTO Object to test readonly protected constructor property promotion.
 * {@see SimpleObjectTest::dataCanBeConstructorMappedToProtectedReadOnlyConstructorProperties()}
 */
final class ProtectedReadOnlyConstructorProperties
{
    public function __construct(
        protected readonly ?string $protectedTypedString = null,
        protected readonly ?bool $protectedTypedBool = null,
        protected readonly ?int $protectedTypedInt = null,
        protected readonly ?float $protectedTypedFloat = null,
    ) {}
}
