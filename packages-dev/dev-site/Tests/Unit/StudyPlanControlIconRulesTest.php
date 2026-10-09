<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The stylesheet of the development instances sizes the control icons of the
 * study plan and switches the glyphs of a semester header.
 *
 * The study plan renders its icons as inline SVGs whose wrapper has no size of
 * its own, and a semester header renders both of its glyphs. The stylesheet
 * selects the glyphs by the class the icon markup derives from the identifier,
 * so a renamed identifier leaves it selecting nothing: both glyphs show, or
 * neither. It also sizes every inlined icon of the element, so a site that
 * registers a drawing without a size does not bring the 0 px icons back.
 *
 * Read from the compiled file, which is what the instances deliver.
 * `StylesheetClassesTest` checks the other direction, that the classes are
 * rendered.
 */
final class StudyPlanControlIconRulesTest extends TestCase
{
    private const STYLESHEET = __DIR__ . '/../../Resources/Public/Css/frontend/academic-extensions.css';

    #[Test]
    public function theStylesheetSwitchesAndSizesTheControlIconsOfTheStudyPlan(): void
    {
        $this->assertFileExists(self::STYLESHEET);
        $css = (string)file_get_contents(self::STYLESHEET);

        $this->assertStringContainsString('.ace-semester .icon-tx-academicbase-action-collapse {', $css);
        $this->assertStringContainsString('.ace-semester.open .icon-tx-academicbase-action-expand {', $css);
        $this->assertStringContainsString('.ace-semester.open .icon-tx-academicbase-action-collapse {', $css);
        $this->assertMatchesRegularExpression(
            '#\.academic-study-plan \.icon \{[^}]*\bwidth: 1\.25rem;[^}]*\bheight: 1\.25rem;#',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '#\.academic-study-plan \.icon svg \{[^}]*\bwidth: 100%;[^}]*\bheight: 100%;#',
            $css,
        );
    }
}
