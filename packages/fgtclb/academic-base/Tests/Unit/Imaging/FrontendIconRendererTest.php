<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FrontendIconRendererTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{string, IconSize|null}>
     */
    public static function sizes(): \Generator
    {
        yield 'default' => ['default', IconSize::DEFAULT];
        yield 'small' => ['small', IconSize::SMALL];
        yield 'medium' => ['medium', IconSize::MEDIUM];
        yield 'large' => ['large', IconSize::LARGE];
        yield 'mega' => ['mega', IconSize::MEGA];
        yield 'overlay, the size of an overlay icon' => ['overlay', null];
        yield 'upper case' => ['SMALL', null];
        yield 'empty' => ['', null];
        yield 'a number' => ['16', null];
    }

    #[DataProvider('sizes')]
    #[Test]
    public function sizeFromAcceptsTheSizesOfAStandaloneIcon(string $value, ?IconSize $expected): void
    {
        $this->assertSame($expected, FrontendIconRenderer::sizeFrom($value));
    }

    /**
     * @return \Generator<string, array{string, bool}>
     */
    public static function identifiers(): \Generator
    {
        yield 'a shared icon' => ['tx-academicbase-action-add', true];
        yield 'a category type icon' => ['category_types.group.type', true];
        yield 'one character' => ['a', true];
        yield '100 characters' => [str_repeat('a', 100), true];
        yield '101 characters' => [str_repeat('a', 101), false];
        yield 'empty' => ['', false];
        yield 'upper case' => ['Tx-academicbase-action-add', false];
        yield 'a leading dot' => ['.tx-academicbase-action-add', false];
        yield 'a comma' => ['tx-academicbase-action-add,tx-academicbase-action-edit', false];
        yield 'a space' => ['tx-academicbase action-add', false];
        yield 'a slash' => ['tx-academicbase/action-add', false];
        yield 'a trailing line feed' => ["tx-academicbase-action-add\n", false];
        yield '100 characters and a trailing line feed' => [str_repeat('a', 100) . "\n", false];
        yield 'a leading line feed' => ["\ntx-academicbase-action-add", false];
    }

    /**
     * `$` of a PCRE pattern also matches before a final line feed; the pattern has to
     * refuse one all the same.
     */
    #[DataProvider('identifiers')]
    #[Test]
    public function identifierPatternAcceptsExactlyAnIdentifier(string $identifier, bool $expected): void
    {
        $this->assertSame($expected, preg_match(FrontendIconRenderer::IDENTIFIER_PATTERN, $identifier) === 1);
    }
}
