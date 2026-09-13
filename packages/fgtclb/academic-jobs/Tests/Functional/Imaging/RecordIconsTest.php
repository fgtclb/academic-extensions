<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Imaging;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
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
final class RecordIconsTest extends AbstractAcademicJobsTestCase
{
    use ColourSchemeAwareIconsTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            'tx-academicjobs-record-job',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    /**
     * The list below pins what is registered; this pins that the TCA actually names it. A
     * registration nothing points at would pass every assertion below while the record list
     * and the page tree still showed another icon for a job.
     */
    #[Test]
    public function jobRecordTypeResolvesToTheRegisteredIcon(): void
    {
        $this->assertSame(
            'tx-academicjobs-record-job',
            $GLOBALS['TCA']['tx_academicjobs_domain_model_job']['ctrl']['typeicon_classes']['default'] ?? null,
        );
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
            'academic_jobs',
            contentTypes: ['academicjobs_newjobform', 'academicjobs_list', 'academicjobs_detail'],
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
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_jobs', ['plugin', 'record']);
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_jobs');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_jobs');
    }
}
