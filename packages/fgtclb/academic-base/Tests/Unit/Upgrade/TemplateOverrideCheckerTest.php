<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Upgrade;

use FGTCLB\AcademicBase\Upgrade\TemplateOverrideChecker;
use FGTCLB\AcademicBase\Upgrade\TemplateOverrideFinding;
use FGTCLB\AcademicBase\Upgrade\TemplateOverrideFindingKind;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The checker answers one question per file of an override folder: does the
 * extension still ship a template at that relative path, and is the override
 * still different from it. The fixture folders next to this test hold one file
 * of every answer, so each rule is pinned on its own.
 */
final class TemplateOverrideCheckerTest extends UnitTestCase
{
    private const UPSTREAM = __DIR__ . '/Fixtures/Upstream';
    private const OVERRIDE = __DIR__ . '/Fixtures/Override';

    /**
     * `Templates/Profile/Show.html` is the shape three of the analysed projects
     * carry: a copy of a template that 3.0 removed. Fluid never resolves it, and
     * nothing in the installation says so.
     */
    #[Test]
    public function anOverrideWithoutUpstreamFileIsReported(): void
    {
        $this->assertFinding(
            'Templates/Profile/Show.html',
            TemplateOverrideFindingKind::MissingUpstream,
            null,
        );
    }

    /**
     * The file systems of the development machine and of the server disagree
     * about this one: `Academicprogram.html` resolves next to the upstream
     * `AcademicProgram.html` on a case insensitive file system and is dead on
     * every Linux server.
     */
    #[Test]
    public function anOverrideDifferingOnlyInCaseIsReportedWithTheUpstreamName(): void
    {
        $this->assertFinding(
            'Pages/Academicprogram.html',
            TemplateOverrideFindingKind::CaseMismatch,
            'Pages/AcademicProgram.html',
        );
    }

    /**
     * A byte identical copy renders, so it is not a defect - but it freezes the
     * upstream markup of that one file, which is why it is reported at all.
     */
    #[Test]
    public function aByteIdenticalCopyIsReported(): void
    {
        $this->assertFinding(
            'Templates/Profile/List.html',
            TemplateOverrideFindingKind::Identical,
            'Templates/Profile/List.html',
        );
    }

    /**
     * The changed copy is the case the command must stay quiet about: it is a
     * deliberate override of a template that still exists.
     */
    #[Test]
    public function aChangedCopyOfAnExistingTemplateIsNotReported(): void
    {
        $this->assertSame(
            [],
            $this->findingsFor('Templates/Profile/Detail.html'),
            'A changed override of a template the extension still ships is no finding',
        );
    }

    /**
     * The override folder of a project holds more than Fluid files - the
     * fixture carries an XLIFF file, which is overridden through
     * `locallangXMLOverride` and not through a view root path.
     */
    #[Test]
    public function onlyFluidFilesAreChecked(): void
    {
        $paths = array_map(
            static fn(TemplateOverrideFinding $finding): string => $finding->overridePath,
            $this->subject()->check(self::OVERRIDE, self::UPSTREAM),
        );

        $this->assertNotContains('Language/locallang.xlf', $paths);
        $this->assertSame(
            ['Pages/Academicprogram.html', 'Templates/Profile/List.html', 'Templates/Profile/Show.html'],
            $paths,
            'Every Fluid file of the override folder is reported once, in path order',
        );
    }

    /**
     * An upstream file the project did not copy is none of the checker's
     * business: it is not an override.
     */
    #[Test]
    public function anUpstreamFileWithoutOverrideIsNotReported(): void
    {
        $paths = array_map(
            static fn(TemplateOverrideFinding $finding): string => $finding->overridePath,
            $this->subject()->check(self::OVERRIDE, self::UPSTREAM),
        );

        $this->assertNotContains('Partials/Profile/Image.html', $paths);
    }

    /**
     * Comparing a folder with itself is what `--site` mode does for the
     * extension's own root paths before they are excluded, and it is the
     * cheapest proof that the identical branch does not depend on the file name
     * differing.
     */
    #[Test]
    public function everyFileOfAFolderComparedWithItselfIsIdentical(): void
    {
        $findings = $this->subject()->check(self::UPSTREAM, self::UPSTREAM);

        $this->assertCount(4, $findings);
        foreach ($findings as $finding) {
            $this->assertSame(TemplateOverrideFindingKind::Identical, $finding->kind);
        }
    }

    /**
     * An upstream folder that does not exist is not an error of the checker -
     * the command rejects that input. Every override file is then dead, which
     * is what an integrator sees who points the check at a removed folder.
     */
    #[Test]
    public function withoutAnUpstreamFolderEveryOverrideIsMissingUpstream(): void
    {
        $findings = $this->subject()->check(self::OVERRIDE, self::UPSTREAM . '/DoesNotExist');

        $this->assertCount(4, $findings, 'The changed copy is dead too once its upstream folder is gone');
        foreach ($findings as $finding) {
            $this->assertSame(TemplateOverrideFindingKind::MissingUpstream, $finding->kind);
        }
    }

    /**
     * Only the two problems gate a pipeline; the notice must not.
     */
    #[Test]
    public function aNoticeIsNoProblem(): void
    {
        $this->assertTrue(TemplateOverrideFindingKind::MissingUpstream->isProblem());
        $this->assertTrue(TemplateOverrideFindingKind::CaseMismatch->isProblem());
        $this->assertFalse(TemplateOverrideFindingKind::Identical->isProblem());
    }

    private function assertFinding(string $overridePath, TemplateOverrideFindingKind $kind, ?string $upstreamPath): void
    {
        $findings = $this->findingsFor($overridePath);

        $this->assertCount(1, $findings, sprintf('Exactly one finding for "%s"', $overridePath));
        $this->assertSame($kind, $findings[0]->kind);
        $this->assertSame($upstreamPath, $findings[0]->upstreamPath);
    }

    /**
     * @return list<TemplateOverrideFinding>
     */
    private function findingsFor(string $overridePath): array
    {
        return array_values(array_filter(
            $this->subject()->check(self::OVERRIDE, self::UPSTREAM),
            static fn(TemplateOverrideFinding $finding): bool => $finding->overridePath === $overridePath,
        ));
    }

    private function subject(): TemplateOverrideChecker
    {
        return new TemplateOverrideChecker();
    }
}
