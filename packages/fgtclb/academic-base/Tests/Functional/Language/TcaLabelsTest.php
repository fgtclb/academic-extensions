<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Language;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Pins the shared TCA labels that replaced the core labels TYPO3 v14 retired.
 *
 * Their whole point is that the wording stays the one editors know, so the
 * expected values are the upstream core texts, copied from the official
 * language packs. If someone rewords one of them, this test says so.
 */
final class TcaLabelsTest extends AbstractAcademicBaseTestCase
{
    private const LABEL_FILE = 'LLL:EXT:academic_base/Resources/Private/Language/locallang_tca.xlf:';

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function labelDataProvider(): \Generator
    {
        yield 'l18n_parent' => ['l18n_parent', 'Transl.Orig'];
        yield 'fe_group.hide_at_login' => ['fe_group.hide_at_login', 'Hide at login'];
        yield 'fe_group.any_login' => ['fe_group.any_login', 'Show at any login'];
        yield 'fe_group.usergroups' => ['fe_group.usergroups', '__Usergroups:__'];
        yield 'palette.general' => ['palette.general', 'Content Element'];
        yield 'palette.access' => ['palette.access', 'Publish Dates and Access Rights'];
        yield 'field.hidden' => ['field.hidden', 'Visibility of content element'];
    }

    #[DataProvider('labelDataProvider')]
    #[Test]
    public function labelResolvesToUpstreamSourceText(string $identifier, string $expected): void
    {
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        $this->assertSame($expected, $languageService->sL(self::LABEL_FILE . $identifier));
    }

    /**
     * The German file is the reason this issue existed: TYPO3 v14 dropped these
     * keys from its language packs, so a German backend silently fell back to
     * the English source. Shipping our own translation is only a fix as long as
     * the file actually carries every key.
     */
    #[DataProvider('labelDataProvider')]
    #[Test]
    public function germanTranslationIsShippedForLabel(string $identifier, string $sourceText): void
    {
        $file = __DIR__ . '/../../../Resources/Private/Language/de.locallang_tca.xlf';
        $this->assertFileExists($file);

        $document = new \DOMDocument();
        $this->assertTrue($document->load($file));

        $targets = [];
        foreach ($document->getElementsByTagName('trans-unit') as $unit) {
            $target = $unit->getElementsByTagName('target')->item(0);
            if ($target !== null) {
                $targets[(string)$unit->getAttribute('id')] = $target->textContent;
            }
        }

        $this->assertArrayHasKey($identifier, $targets, sprintf('No German translation for "%s".', $identifier));
        $this->assertNotSame(
            $sourceText,
            $targets[$identifier],
            sprintf('German translation of "%s" is still the English source text.', $identifier),
        );
    }
}
