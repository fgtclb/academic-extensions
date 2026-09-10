<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Templates;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Renders the partial `Job/PropertyIcon`, which maps the name of a job property to the
 * shared icon drawn in front of it in the list, the detail view and the contact block.
 *
 * The identifiers used to be built from the property name (`academic_jobs-{item}`), so a
 * property without a registered icon rendered the core "icon not found" placeholder. The
 * map is explicit now, and a property it does not know renders nothing at all. Every
 * property the shipped partials pass is listed here, spelled out rather than read back
 * from the partial, so a changed map has to be changed twice.
 */
final class PropertyIconPartialTest extends AbstractAcademicJobsTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function mappedPropertyDataProvider(): \Generator
    {
        yield 'employmentStartDate' => ['employmentStartDate', 'tx-academicbase-info-calendar'];
        yield 'endtime' => ['endtime', 'tx-academicbase-info-calendar'];
        yield 'companyName' => ['companyName', 'tx-academicbase-info-company'];
        yield 'sector' => ['sector', 'tx-academicbase-info-sector'];
        yield 'type' => ['type', 'tx-academicbase-info-employment'];
        yield 'employmentType' => ['employmentType', 'tx-academicbase-info-employment'];
        yield 'requiredDegree' => ['requiredDegree', 'tx-academicbase-info-degree'];
        yield 'contractualRelationship' => ['contractualRelationship', 'tx-academicbase-info-contract'];
        yield 'workLocation' => ['workLocation', 'tx-academicbase-info-location'];
        yield 'internationalsWelcome' => ['internationalsWelcome', 'tx-academicbase-info-international'];
        yield 'alumniRecommend' => ['alumniRecommend', 'tx-academicbase-info-recommendation'];
        yield 'link' => ['link', 'tx-academicbase-info-link'];
        yield 'contactPhone' => ['contactPhone', 'tx-academicbase-info-phone'];
        yield 'contactEmail' => ['contactEmail', 'tx-academicbase-info-email'];
    }

    #[Test]
    #[DataProvider('mappedPropertyDataProvider')]
    public function mappedPropertyRendersItsSharedIconInline(string $property, string $identifier): void
    {
        $content = $this->renderPropertyIcon($property);

        $this->assertStringContainsString('data-identifier="' . $identifier . '"', $content);
        $this->assertStringNotContainsString('default-not-found', $content);
        // The provider of the shared icons inlines the drawing, so it takes the text colour.
        $this->assertMatchesRegularExpression('#<span class="icon-markup">\s*<svg[^>]*currentColor#', $content);
        $this->assertStringNotContainsString('<img', $content);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unmappedPropertyDataProvider(): \Generator
    {
        // A job property the shipped partials do not list, as an overridden list might.
        yield 'starttime' => ['starttime'];
        yield 'description' => ['description'];
        yield 'unknown property' => ['notAJobProperty'];
    }

    #[Test]
    #[DataProvider('unmappedPropertyDataProvider')]
    public function unmappedPropertyRendersNoIcon(string $property): void
    {
        $this->assertSame('', $this->renderPropertyIcon($property));
    }

    private function renderPropertyIcon(string $property): string
    {
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/Fixtures/'],
            partialRootPaths: ['EXT:academic_jobs/Resources/Private/Partials/'],
            request: (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE),
        ));
        $view->assign('property', $property);

        return trim($view->render('PropertyIcon'));
    }
}
