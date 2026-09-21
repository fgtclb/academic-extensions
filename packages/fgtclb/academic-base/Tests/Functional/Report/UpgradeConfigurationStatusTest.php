<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Report;

use FGTCLB\AcademicBase\Report\UpgradeConfigurationStatus;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Reports\Registry\StatusRegistry;
use TYPO3\CMS\Reports\Status;

/**
 * With EXT:reports loaded, the compiler pass of `Services.php` registers the
 * status provider and the autoconfiguration of EXT:reports tags it, so it is
 * one of the providers the status registry collects.
 *
 * The registry is asked rather than the report that renders it: the two core
 * versions aggregate the providers in different classes -
 * `Report\Status\Status` on v13, `Service\StatusService` on v14 - while
 * `StatusRegistry`, `StatusProviderInterface` and `Status` are identical on
 * both.
 */
final class UpgradeConfigurationStatusTest extends AbstractAcademicBaseTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-reports',
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/academic-test-configuration',
    ];

    private const FIXTURES = __DIR__ . '/../Upgrade/Fixtures/ConfigurationCheck/';

    #[Test]
    public function everyFindingBecomesOneStatusEntry(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $statuses = $this->provider()->getStatus();

        $this->assertSame(
            ['Static template', 'Static template', 'Static template'],
            array_map(static fn(Status $status): string => $status->getTitle(), $statuses),
        );
        $this->assertSame(
            ['sys_template:1', 'sys_template:2', 'sys_template:7'],
            array_map(static fn(Status $status): string => $status->getValue(), $statuses),
        );
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $statuses[0]->getSeverity());
        $this->assertStringContainsString('holds no TypoScript', $statuses[0]->getMessage());
    }

    /**
     * An installation with nothing to fix has to say so, or an empty section
     * reads like a check that did not run.
     */
    #[Test]
    public function anInstallationWithoutFindingsGetsASingleOkEntry(): void
    {
        $statuses = $this->provider()->getStatus();

        $this->assertCount(1, $statuses);
        $this->assertSame('Upgrade configuration', $statuses[0]->getTitle());
        $this->assertSame('Nothing stale', $statuses[0]->getValue());
        $this->assertSame(ContextualFeedbackSeverity::OK, $statuses[0]->getSeverity());
    }

    /**
     * The report resolves the provider label itself, so it has to be a language
     * reference and that reference has to resolve.
     */
    #[Test]
    public function theProviderLabelIsAResolvingLanguageReference(): void
    {
        $label = 'LLL:EXT:academic_base/Resources/Private/Language/locallang_reports.xlf:status.label';

        $this->assertSame($label, $this->provider()->getLabel());
        $this->assertSame(
            'Academic Base',
            $this->get(LanguageServiceFactory::class)->create('default')->sL($label),
        );
    }

    private function provider(): UpgradeConfigurationStatus
    {
        $providers = array_filter(
            $this->get(StatusRegistry::class)->getProviders(),
            static fn(object $provider): bool => $provider instanceof UpgradeConfigurationStatus,
        );
        $this->assertCount(1, $providers, 'The provider is registered and tagged as a status provider');

        return array_pop($providers);
    }
}
