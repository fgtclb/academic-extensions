<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * The shared icon set every academic extension renders: action, state and info glyphs,
 * rendered in the frontend as much as in the backend. Each one has to follow the text
 * colour, which is what the colour scheme aware provider and `currentColor` give it,
 * and each one has to size itself by the font size, because a frontend page has no
 * backend stylesheet that would.
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class SharedIconsTest extends AbstractAcademicBaseTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const IDENTIFIERS = [
        'tx-academicbase-action-add',
        'tx-academicbase-action-back',
        'tx-academicbase-action-clear',
        'tx-academicbase-action-close',
        'tx-academicbase-action-collapse',
        'tx-academicbase-action-delete',
        'tx-academicbase-action-drag',
        'tx-academicbase-action-edit',
        'tx-academicbase-action-expand',
        'tx-academicbase-action-help',
        'tx-academicbase-action-move-down',
        'tx-academicbase-action-move-up',
        'tx-academicbase-action-save',
        'tx-academicbase-action-undo',
        'tx-academicbase-action-upload-image',
        'tx-academicbase-action-view',
        'tx-academicbase-action-view-close',
        'tx-academicbase-state-hidden',
        'tx-academicbase-state-visible',
        'tx-academicbase-info-calendar',
        'tx-academicbase-info-company',
        'tx-academicbase-info-contract',
        'tx-academicbase-info-degree',
        'tx-academicbase-info-department',
        'tx-academicbase-info-email',
        'tx-academicbase-info-employment',
        'tx-academicbase-info-information',
        'tx-academicbase-info-international',
        'tx-academicbase-info-link',
        'tx-academicbase-info-location',
        'tx-academicbase-info-partnership',
        'tx-academicbase-info-phone',
        'tx-academicbase-info-recommendation',
        'tx-academicbase-info-role',
        'tx-academicbase-info-room',
        'tx-academicbase-info-sector',
    ];

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sharedIconIdentifiers(): \Generator
    {
        foreach (self::IDENTIFIERS as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconIsInTheHouseFormat(string $identifier): void
    {
        $this->assertIconIsInTheHouseFormat($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function renderedSharedIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * The list above cannot see an identifier that is registered and missing from it, so the
     * registered `tx-academicbase-*` set is compared against it as a whole.
     */
    #[Test]
    public function everyRegisteredSharedIconIsListed(): void
    {
        $registered = array_values(array_filter(
            $this->get(IconRegistry::class)->getAllRegisteredIconIdentifiers(),
            static fn(string $identifier): bool => str_starts_with($identifier, 'tx-academicbase-'),
        ));
        $listed = self::IDENTIFIERS;
        sort($registered);
        sort($listed);

        $this->assertSame($listed, $registered);
    }

    #[Test]
    public function identifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_base', ['action', 'state', 'info']);
    }

    #[Test]
    public function iconFilesAreNamedAfterTheirIdentifiers(): void
    {
        $this->assertIconFilesAreNamedAfterTheirIdentifiers('academic_base');
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_base');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_base');
    }
}
