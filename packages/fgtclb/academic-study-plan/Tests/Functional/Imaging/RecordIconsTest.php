<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Imaging;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523). The content
 * element icon is one of them: it is the `tt_content` record type icon of the page module.
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicStudyPlanTestCase
{
    use ColourSchemeAwareIconsTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            'tx-academicstudyplan-plugin-study-plan',
            'tx-academicstudyplan-record-category',
            'tx-academicstudyplan-record-module',
            'tx-academicstudyplan-record-semester',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
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
     * The identifiers above are hand maintained. This walks the TCA instead: every type of
     * a table of this extension, and every content element type named here, has to name
     * a registered, colour scheme aware identifier of this extension - not a core or a
     * foreign one, and not none. A table added later is covered as it is; a content
     * element added later has to be added to the list.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware(
            'academic_study_plan',
            contentTypes: ['academic_study_plan'],
        );
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function recordTypeIconDataProvider(): \Generator
    {
        yield 'content element' => ['tt_content', 'academic_study_plan', 'tx-academicstudyplan-plugin-study-plan'];
        yield 'category' => ['tx_academicstudyplan_domain_model_category', 'default', 'tx-academicstudyplan-record-category'];
        yield 'module' => ['tx_academicstudyplan_domain_model_module', 'default', 'tx-academicstudyplan-record-module'];
        yield 'semester' => ['tx_academicstudyplan_domain_model_semester', 'default', 'tx-academicstudyplan-record-semester'];
    }

    /**
     * The list above pins what is registered; this pins that the TCA actually names it.
     * A registration nothing points at would pass every assertion above while the backend
     * still showed the previous icon.
     */
    #[Test]
    #[DataProvider('recordTypeIconDataProvider')]
    public function recordTypeResolvesToTheRegisteredIcon(string $table, string $type, string $identifier): void
    {
        $this->assertSame($identifier, $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] ?? null);
    }

    /**
     * The new content element wizard names its icon on its own, in page TSconfig, so it
     * can drift from the icon the page module shows for the same content element. Compared
     * with the TCA rather than with a literal, so the two have to be changed together.
     */
    #[Test]
    public function newContentElementWizardShowsTheIconOfTheContentElement(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIcons/pagesWithRegisteredPageTsConfig.csv');

        $elements = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        $this->assertArrayHasKey('academic_study_plan.', $elements, 'The wizard entry is not delivered.');
        $this->assertSame(
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['academic_study_plan'] ?? null,
            $elements['academic_study_plan.']['iconIdentifier'] ?? null,
        );
        $this->assertSame(
            'tx-academicstudyplan-plugin-study-plan',
            $elements['academic_study_plan.']['iconIdentifier'] ?? null,
        );
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInTheHouseFormat(string $identifier): void
    {
        $this->assertIconIsInTheHouseFormat($identifier);
    }

    #[Test]
    public function identifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_study_plan', ['plugin', 'record']);
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_study_plan');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_study_plan');
    }
}
