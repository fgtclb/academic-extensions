<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Tca;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Pins that the inline parent and child of this extension agree about workspaces.
 *
 * "tx_academiccontacts4pages_domain_model_contact" has always been declared
 * workspace aware, while the table it is an inline child of,
 * "tx_academicpersons_domain_model_contract", was not - the direction TYPO3 does not
 * repair. "TcaMigration::addWorkspaceAwarenessToInlineChildren()" only ever adds the
 * flag to a child, never to a parent, and TYPO3 v13 has no such migration at all, so
 * the mismatch went unreported on both supported core versions until ACE-477 flagged
 * the parent.
 *
 * Asserted from this extension rather than from "academic_persons" because the inline
 * field is added here, in
 * "Configuration/TCA/Overrides/tx_academicpersons_domain_model_contract.php".
 *
 * Only the TYPO3 v13 run is strict about the parent. The assertion reads the migrated
 * TCA, and on v14 "tx_academicpersons_domain_model_contract" is itself an inline child
 * of the workspace aware "tx_academicpersons_domain_model_organisational_unit", so the
 * migration would put a removed flag back and leave a deprecation in its place.
 */
final class WorkspaceAwarenessTest extends AbstractAcademicContacts4PagesTestCase
{
    private const CONTACT_TABLE = 'tx_academiccontacts4pages_domain_model_contact';
    private const PARENT_TABLE = 'tx_academicpersons_domain_model_contract';
    private const PARENT_FIELD = 'tx_academiccontacts4pages_contacts';

    #[Test]
    public function contactTableIsDeclaredWorkspaceAware(): void
    {
        $this->assertTrue(
            $GLOBALS['TCA'][self::CONTACT_TABLE]['ctrl']['versioningWS'] ?? false,
            sprintf('Table "%s" is not declared workspace aware in its TCA "ctrl" section.', self::CONTACT_TABLE),
        );
    }

    /**
     * The relation this extension adds, pinned so that a rename or a switch away from
     * "inline" does not leave the assertion below passing for the wrong reason.
     */
    #[Test]
    public function contactTableIsRegisteredAsInlineChildOfContract(): void
    {
        $config = $GLOBALS['TCA'][self::PARENT_TABLE]['columns'][self::PARENT_FIELD]['config'] ?? [];

        $this->assertSame('inline', $config['type'] ?? null);
        $this->assertSame(self::CONTACT_TABLE, $config['foreign_table'] ?? null);
    }

    #[Test]
    public function inlineParentOfTheContactTableIsDeclaredWorkspaceAware(): void
    {
        $this->assertTrue(
            $GLOBALS['TCA'][self::PARENT_TABLE]['ctrl']['versioningWS'] ?? false,
            sprintf(
                'Table "%s" is an inline parent of the workspace aware "%s" but is not declared'
                . ' workspace aware itself. TYPO3 does not repair this direction.',
                self::PARENT_TABLE,
                self::CONTACT_TABLE,
            ),
        );
    }
}
