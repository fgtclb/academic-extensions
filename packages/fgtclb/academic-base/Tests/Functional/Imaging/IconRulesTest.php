<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\IconFilesAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The checks of the icon rules every academic extension runs on its own icons, against
 * the fixture extension `tests/icon-rules`, which follows every rule with every shape
 * the rules allow: a record icon drawn from a file of its own, a record icon and a
 * frontend icon drawn from a file of the shared set of academic_base, a content element
 * and a page type sharing one file, a category type and a category group icon, and a
 * category type drawn from the shared set. Each check has to accept all of them, which
 * a single extension of this repository cannot show.
 */
final class IconRulesTest extends AbstractAcademicBaseTestCase
{
    use ColourSchemeAwareIconsTrait;
    use FrontendIconsAssertionTrait;
    use IconFilesAssertionTrait;

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'fgtclb/category-types',
        'tests/icon-rules',
    ];

    #[Test]
    public function backendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('test_icon_rules');
    }

    #[Test]
    public function frontendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertFrontendIconIdentifiersFollowTheNamingScheme('test_icon_rules', ['action', 'info']);
    }

    /**
     * Includes the type and the group icon of the category types and the record icon
     * drawn from the shared set.
     */
    #[Test]
    public function everyBackendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryIconOfTheExtensionIsInTheHouseFormat('test_icon_rules');
    }

    #[Test]
    public function everyFrontendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat('test_icon_rules');
    }

    /**
     * A file of the shared set that only another extension draws, in the backend
     * registry, is found by the walk of academic_base as well.
     */
    #[Test]
    public function aBackendIconDrawnFromTheSharedSetCountsForAcademicBase(): void
    {
        $this->assertEveryIconOfTheExtensionIsInTheHouseFormat('academic_base');
    }

    #[Test]
    public function everyTypeOfTheExtensionNamesAnIconOfItsOwn(): void
    {
        $this->assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn(
            'test_icon_rules',
            contentTypes: ['testiconrules_items'],
            pageTypes: [1791061950],
        );
    }

    /**
     * The group icon counts as registered: `EXT:category_types` registers it as
     * `category_types_group.testiconrules`.
     */
    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('test_icon_rules');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('test_icon_rules');
    }
}
