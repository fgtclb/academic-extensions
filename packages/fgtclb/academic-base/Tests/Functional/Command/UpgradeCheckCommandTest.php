<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Command;

use FGTCLB\AcademicBase\Command\UpgradeCheckCommand;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Console\CommandRegistry;

/**
 * The command is what an integrator runs against a project before and after an
 * upgrade, so the tests here are written from its command line: what it prints,
 * and which exit status a pipeline sees.
 *
 * Two fixture extensions stand in for an installation. `test_upgrade_check` is
 * the academic extension, `test_upgrade_check_project` the site package of the
 * project, and its override folder holds one file of every outcome: a template
 * the extension does not have, an unchanged copy, a changed copy and a name
 * that differs from the upstream one only in case.
 */
final class UpgradeCheckCommandTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/test-upgrade-check-shared',
        'tests/test-upgrade-check',
        'tests/test-upgrade-check-project',
    ];

    private const OVERRIDES = 'EXT:test_upgrade_check_project/Resources/Private/Extensions/TestUpgradeCheck/';

    /**
     * What `vendor/bin/typo3 list` reads: the `#[AsCommand]` attribute alone
     * makes the class a service, and only the command registry makes it an
     * invocable command. A missing `autoconfigure` would pass every test below
     * and leave the console without the command.
     */
    #[Test]
    public function theCommandIsRegisteredInTheConsole(): void
    {
        $registry = $this->get(CommandRegistry::class);

        $this->assertTrue($registry->has('academic:upgrade:check'));
        $this->assertInstanceOf(UpgradeCheckCommand::class, $registry->get('academic:upgrade:check'));
    }

    /**
     * The whole point of the command in one run: the dead override is named, the
     * frozen copy is named, and the changed copy is not.
     */
    #[Test]
    public function theThreeFindingsOfAnOverrideFolderAreReported(): void
    {
        $tester = $this->execute(['extension' => 'test_upgrade_check', '--override-path' => [self::OVERRIDES]]);

        $output = $tester->getDisplay();
        $this->assertStringContainsString('x missing-upstream  Templates/Profile/Show.html', $output);
        $this->assertStringContainsString('= identical         Templates/Profile/List.html', $output);
        $this->assertStringContainsString(
            '! case-mismatch     Partials/Profile/image.html -> Partials/Profile/Image.html',
            $output,
        );
        $this->assertStringNotContainsString('Templates/Profile/Detail.html', $output);
        $this->assertStringContainsString('2 problems and 2 notices in 1 override folder.', $output);
    }

    /**
     * A problem has to gate a pipeline.
     */
    #[Test]
    public function aProblemMakesTheCommandFail(): void
    {
        $tester = $this->execute(['extension' => 'test_upgrade_check', '--override-path' => [self::OVERRIDES]]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /**
     * A notice must not. The `Layouts/` folder of the project is nothing but an
     * unchanged copy, so the run reports it and still succeeds.
     */
    #[Test]
    public function aNoticeAloneLetsTheCommandSucceed(): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => [self::OVERRIDES . 'Layouts/'],
            '--upstream-path' => 'EXT:test_upgrade_check/Resources/Private/Layouts/',
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('= identical         Default.html', $tester->getDisplay());
        $this->assertStringContainsString('0 problems and 1 notice in 1 override folder.', $tester->getDisplay());
    }

    /**
     * `--upstream-path` is what an override folder mirroring a single upstream
     * folder needs; without it the same folder is compared with
     * `Resources/Private/` and every file in it looks dead.
     */
    #[Test]
    public function withoutTheMatchingUpstreamPathTheSameFolderLooksDead(): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => [self::OVERRIDES . 'Layouts/'],
        ]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('x missing-upstream  Default.html', $tester->getDisplay());
    }

    #[Test]
    public function anUnknownExtensionIsInvalidInput(): void
    {
        $tester = $this->execute(['extension' => 'no_such_extension', '--override-path' => [self::OVERRIDES]]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('The extension "no_such_extension" is not active.', $tester->getDisplay());
    }

    #[Test]
    public function aMissingOverrideFolderIsInvalidInput(): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => ['EXT:test_upgrade_check_project/Resources/Private/DoesNotExist/'],
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('does not exist', $tester->getDisplay());
    }

    /**
     * The likeliest mistake of all: a typo in the extension key. TYPO3 answers
     * the same empty string for it as for a path outside the project, so
     * without telling the two apart the command asks for an "EXT:" path from
     * somebody who just gave one.
     */
    #[Test]
    public function anOverridePathNamingAnUnknownExtensionSaysSo(): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => ['EXT:test_upgrade_chekc_project/Resources/Private/'],
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString(
            'names an extension that is not installed or not active',
            $tester->getDisplay(),
        );
    }

    #[Test]
    public function aMissingUpstreamFolderIsInvalidInput(): void
    {
        $tester = $this->execute([
            'extension' => 'test_upgrade_check',
            '--override-path' => [self::OVERRIDES],
            '--upstream-path' => 'EXT:test_upgrade_check/Resources/Private/DoesNotExist/',
        ]);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString(
            'The upstream folder "EXT:test_upgrade_check/Resources/Private/DoesNotExist/" does not exist.',
            $tester->getDisplay(),
        );
    }

    /**
     * Naming neither an override folder nor a site leaves nothing to compare,
     * and silently succeeding on that would be the worst answer in a pipeline.
     */
    #[Test]
    public function namingNeitherAFolderNorASiteIsInvalidInput(): void
    {
        $tester = $this->execute(['extension' => 'test_upgrade_check']);

        $this->assertSame(Command::INVALID, $tester->getStatusCode());
        $this->assertStringContainsString('Name at least one --override-path', $tester->getDisplay());
    }

    /**
     * @param array<string, string|list<string>> $parameters
     */
    private function execute(array $parameters): CommandTester
    {
        $tester = new CommandTester($this->get(UpgradeCheckCommand::class));
        $tester->execute($parameters);

        return $tester;
    }
}
