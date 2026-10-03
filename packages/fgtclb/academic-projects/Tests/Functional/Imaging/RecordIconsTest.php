<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicProjectsTestCase
{
    use ColourSchemeAwareIconsTrait;
    use FrontendIconsAssertionTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            'category_types.projects.competence_field',
            'category_types.projects.cooperation',
            'category_types.projects.funding_partner',
            'category_types.projects.project_department',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function categoryTypeIconIdentifiers(): \Generator
    {
        foreach (self::recordIconIdentifiers() as $name => $arguments) {
            if (str_starts_with($arguments[0], 'category_types.')) {
                yield $name => $arguments;
            }
        }
    }

    /**
     * The frontend renders the category type icons too, from the frontend icon registry,
     * with the file and the provider of the backend.
     */
    #[Test]
    #[DataProvider('categoryTypeIconIdentifiers')]
    public function categoryTypeIconIsTheSameFrontendIcon(string $identifier): void
    {
        $this->assertIconIsRegisteredInBothRegistries($identifier);
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
        $this->assertFrontendIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function renderedRecordIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * The identifiers above are hand maintained, so they cannot catch a record icon that is
     * added later and never converted. This one is derived from the TCA and does.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware('academic_projects');
    }
}
