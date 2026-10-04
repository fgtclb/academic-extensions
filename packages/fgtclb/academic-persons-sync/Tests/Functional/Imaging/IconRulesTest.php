<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsSync\Tests\Functional\Imaging;

use FGTCLB\AcademicPersonsSync\Tests\Functional\AbstractAcademicPersonsSyncTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\IconFilesAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The extension ships no icon apart from `Extension.svg`, the icon of the extension
 * manager. An icon file added later has to be registered, see docs/architecture/icons.md,
 * and the rest of the icon rules apply to it from the moment it is.
 */
final class IconRulesTest extends AbstractAcademicPersonsSyncTestCase
{
    use IconFilesAssertionTrait;

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_persons_sync');
    }
}
