<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Tca;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\InlineParentPointerAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The inline children of this extension keep their parent when it is edited in a
 * workspace and published (ACE-907), see {@see InlineParentPointerAssertionTrait}.
 */
final class InlineParentPointerTest extends AbstractAcademicContacts4PagesTestCase
{
    use InlineParentPointerAssertionTrait;

    #[Test]
    public function inlineChildrenKeepTheLiveParent(): void
    {
        $this->assertInlineParentPointersKeepTheLiveParent(['tx_academiccontacts4pages_'], [
            'pages.tx_academiccontacts4pages_contacts -> tx_academiccontacts4pages_domain_model_contact.page',
            'tx_academiccontacts4pages_domain_model_role.contacts -> tx_academiccontacts4pages_domain_model_contact.role',
            'tx_academicpersons_domain_model_contract.tx_academiccontacts4pages_contacts -> tx_academiccontacts4pages_domain_model_contact.contract',
        ]);
    }
}
