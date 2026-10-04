<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Imaging;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\IconFilesAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The icon rules of docs/architecture/icons.md, checked for every icon of this extension:
 * its content element and record icons, one of them drawn from the shared set of
 * academic_base. The other icon tests spell out the identifiers that exist today. These
 * checks read the registration files, the registries, the TCA and the icon directory
 * instead, so an icon added later is held to the same rules without a test naming it.
 */
final class IconRulesTest extends AbstractAcademicContacts4PagesTestCase
{
    use ColourSchemeAwareIconsTrait;
    use IconFilesAssertionTrait;

    #[Test]
    public function backendIdentifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_contacts4pages', ['plugin', 'record']);
    }

    #[Test]
    public function everyBackendIconOfTheExtensionIsInTheHouseFormat(): void
    {
        $this->assertEveryIconOfTheExtensionIsInTheHouseFormat('academic_contacts4pages');
    }

    /**
     * Every type of a table of this extension, and every content element named here, names
     * an icon of this extension in the group of its kind, drawn for the colour scheme. A
     * table added later is covered as it is. A content element added later has to be added
     * to the list.
     */
    #[Test]
    public function everyTypeOfTheExtensionNamesAnIconOfItsOwn(): void
    {
        $this->assertEveryTypeOfTheExtensionNamesAnIconOfItsOwn(
            'academic_contacts4pages',
            contentTypes: [
                'academiccontacts4pages_list',
            ],
        );
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_contacts4pages');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_contacts4pages');
    }
}
