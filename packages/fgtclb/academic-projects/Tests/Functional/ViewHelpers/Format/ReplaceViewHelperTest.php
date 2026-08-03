<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\ViewHelpers\Format;

use FGTCLB\AcademicProjects\Tests\Functional\ViewHelpers\AbstractViewHelperTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `ViewHelpers\Format\ReplaceViewHelper` had no test when it was migrated from the
 * deprecated `renderStatic()` to `render()`, and it is used by no template of this
 * repository - only by project templates, which is why the deprecation stayed unnoticed.
 *
 * The cases below pin what the view helper promised before that migration: the content
 * comes from the argument or from the children, `substring` and `replacement` accept a
 * list, and `caseSensitive` decides between `str_replace()` and `str_ireplace()`. They pass
 * against the removed `renderStatic()` implementation as well, which is what makes them the
 * safety net of the migration rather than a description of its result.
 *
 * `returnCount` is the exception - it is asserted here for the first time, having been
 * unreachable until it was registered.
 */
final class ReplaceViewHelperTest extends AbstractViewHelperTestCase
{
    #[Test]
    public function substringOfTheContentArgumentIsReplaced(): void
    {
        $output = $this->render('Replace', [
            'content' => 'A research project',
            'substring' => 'research',
            'replacement' => 'teaching',
        ]);

        $this->assertSame('A teaching project', $output);
    }

    /**
     * The content argument is the first optional one, which is what the removed
     * `CompileWithContentArgumentAndRenderStatic` trait resolved it as - so the children
     * are rendered only while it is not given.
     */
    #[Test]
    public function childrenAreUsedWithoutAContentArgument(): void
    {
        $output = $this->render('ReplaceChildren', [
            'content' => 'A research project',
            'substring' => 'research',
            'replacement' => 'teaching',
        ]);

        $this->assertSame('A teaching project', $output);
    }

    #[Test]
    public function contentArgumentTakesPrecedenceOverTheChildren(): void
    {
        $output = $this->render('ReplaceChildrenAndContent', [
            'content' => 'From the children',
            'substring' => 'the',
            'replacement' => 'THE',
        ]);

        $this->assertSame('From THE argument', $output);
    }

    /**
     * An empty content argument counts as given. `isset()` is what the trait used to decide
     * it with, and `??` is what the migrated `render()` uses - both step aside for `null`
     * only.
     */
    #[Test]
    public function emptyContentArgumentIsNotFilledFromTheChildren(): void
    {
        $output = $this->render('ReplaceChildren', [
            'content' => '',
            'substring' => 'research',
            'replacement' => 'teaching',
        ]);

        $this->assertSame('', $output);
    }

    /**
     * The three value arguments are registered as `mixed`, because TYPO3 v14 rejected the
     * list while they were registered as `string`: Fluid 5 validates a registered type with
     * `StrictArgumentProcessor`, where Fluid 4 is lenient and lets anything through.
     */
    #[Test]
    public function substringAndReplacementCanBeLists(): void
    {
        $output = $this->render('Replace', [
            'content' => 'A research project',
            'substring' => ['A', 'research'],
            'replacement' => ['One', 'teaching'],
        ]);

        $this->assertSame('One teaching project', $output);
    }

    /**
     * `mixed` rather than a `string|array` union, and this is the case that decides it.
     * Fluid 5 matches its scalar coercion on the whole registered type string, so a union
     * falls through uncoerced and is then validated against both members - an integer is
     * neither, and `string` used to cast it. The view helper casts it itself.
     */
    #[Test]
    public function nonStringScalarsAreAccepted(): void
    {
        $output = $this->render('Replace', [
            'content' => 'Project 2025 report',
            'substring' => 2025,
            'replacement' => 2026,
        ]);

        $this->assertSame('Project 2026 report', $output);
    }

    #[Test]
    public function replacementIsCaseSensitiveByDefault(): void
    {
        $output = $this->render('Replace', [
            'content' => 'A Research project',
            'substring' => 'research',
            'replacement' => 'teaching',
        ]);

        $this->assertSame('A Research project', $output);
    }

    #[Test]
    public function replacementIgnoresTheCaseOnDemand(): void
    {
        $output = $this->render('ReplaceCaseSensitive', [
            'content' => 'A Research project',
            'substring' => 'research',
            'replacement' => 'teaching',
            'caseSensitive' => false,
        ]);

        $this->assertSame('A teaching project', $output);
    }

    /**
     * Without a `replacement` the substring is dropped rather than kept - the argument
     * defaults to an empty string.
     */
    #[Test]
    public function substringIsRemovedWithoutAReplacement(): void
    {
        $output = $this->render('ReplaceWithoutReplacement', [
            'content' => 'A research project',
            'substring' => 'research ',
        ]);

        $this->assertSame('A project', $output);
    }

    /**
     * The count was computed and returned from the start, but the argument deciding it was
     * never registered - and Fluid rejects an undeclared argument while parsing, so no
     * template could ask for it:
     *
     * `Undeclared arguments passed to ViewHelper [...]: returnCount. Valid arguments are:
     * content, substring, replacement, caseSensitive`
     */
    #[Test]
    public function numberOfReplacementsIsReturnedOnDemand(): void
    {
        $output = $this->render('ReplaceReturnCount', [
            'content' => 'A research project of the research group',
            'substring' => 'research',
            'replacement' => 'teaching',
            'returnCount' => true,
        ]);

        $this->assertSame('2', $output);
    }

    #[Test]
    public function contentIsReturnedWithoutReturnCount(): void
    {
        $output = $this->render('ReplaceReturnCount', [
            'content' => 'A research project',
            'substring' => 'research',
            'replacement' => 'teaching',
            'returnCount' => false,
        ]);

        $this->assertSame('A teaching project', $output);
    }

    /**
     * A substring that does not occur is counted as well - as zero, rather than as no
     * result at all.
     */
    #[Test]
    public function countIsZeroWithoutAMatch(): void
    {
        $output = $this->render('ReplaceReturnCount', [
            'content' => 'A research project',
            'substring' => 'teaching',
            'replacement' => 'learning',
            'returnCount' => true,
        ]);

        $this->assertSame('0', $output);
    }

    #[Test]
    public function outputIsEscaped(): void
    {
        $output = $this->render('Replace', [
            'content' => 'A <b>research</b> project',
            'substring' => 'research',
            'replacement' => 'teaching & learning',
        ]);

        $this->assertSame('A &lt;b&gt;teaching &amp; learning&lt;/b&gt; project', $output);
    }
}
