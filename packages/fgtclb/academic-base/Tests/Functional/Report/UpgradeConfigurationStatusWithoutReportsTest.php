<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Report;

use FGTCLB\AcademicBase\Command\UpgradeCheckCommand;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * EXT:reports stays optional: an installation without it boots, checks and
 * reports on the command line exactly as one with it.
 *
 * What this cannot show is the compiler pass guard of
 * `Configuration/Services.php` itself. The guard exists because Symfony
 * reflects every service class while it compiles the container, and
 * `UpgradeConfigurationStatus` implements an interface that only EXT:reports
 * ships - registering it where that extension is absent is a fatal error
 * during the container build. "Absent" means absent from the autoloader, which
 * is a classic installation or a Composer project that removed
 * `typo3/cms-reports`; a functional test instance always has the package in
 * `.Build/vendor`, so removing the guard changes nothing observable here. The
 * registration was verified in the other direction instead: with EXT:reports
 * active, {@see UpgradeConfigurationStatusTest} finds exactly one provider in
 * the status registry.
 */
final class UpgradeConfigurationStatusWithoutReportsTest extends AbstractAcademicBaseTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
    ];

    #[Test]
    public function theCheckAndTheCommandWorkWithoutTheReportsExtension(): void
    {
        $this->assertFalse(
            $this->getContainer()->has('TYPO3\\CMS\\Reports\\Registry\\StatusRegistry'),
            'EXT:reports is not part of this instance',
        );
        $this->assertSame([], $this->get(ConfigurationChecker::class)->check());

        $tester = new CommandTester($this->get(UpgradeCheckCommand::class));
        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('nothing to report', $tester->getDisplay());
    }
}
