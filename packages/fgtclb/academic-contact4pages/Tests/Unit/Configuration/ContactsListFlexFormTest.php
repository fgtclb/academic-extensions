<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Reads the shipped FlexForm of the contact list as XML. It needs no core: what is pinned
 * is the field an editor sees and the value a new content element starts with.
 */
final class ContactsListFlexFormTest extends UnitTestCase
{
    private function flexForm(): \SimpleXMLElement
    {
        $flexForm = simplexml_load_file(__DIR__ . '/../../../Configuration/FlexForms/ContactsList.xml');
        $this->assertInstanceOf(\SimpleXMLElement::class, $flexForm);

        return $flexForm;
    }

    /**
     * A new content element groups its contacts by role, which is what every content
     * element did before the option existed.
     */
    #[Test]
    public function contactListOffersRoleGroupingSwitchedOnByDefault(): void
    {
        $fields = $this->flexForm()->ROOT->el;

        $this->assertSame(
            ['settings.showHiddenRecords', 'settings.groupByRole'],
            array_map(static fn(\SimpleXMLElement $field): string => $field->getName(), iterator_to_array($fields->children(), false)),
        );
        $groupByRole = $fields->{'settings.groupByRole'};
        $this->assertSame('check', (string)$groupByRole->config->type);
        $this->assertSame('1', (string)$groupByRole->config->default);
    }
}
