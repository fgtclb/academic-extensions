<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Tca;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\DeprecatedCoreLabelsTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * @see DeprecatedCoreLabelsTrait
 */
final class DeprecatedCoreLabelsTest extends AbstractAcademicContacts4PagesTestCase
{
    use DeprecatedCoreLabelsTrait;

    #[Group('not-core-13')]
    #[Test]
    public function tcaDoesNotReferenceCoreLabelsRetiredInV14(): void
    {
        $this->assertTcaHasNoDeprecatedCoreLabelReferences(['tx_academiccontacts4pages_']);
    }
}
