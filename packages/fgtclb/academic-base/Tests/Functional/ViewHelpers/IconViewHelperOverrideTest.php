<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * A site package replaces a frontend icon of another extension, and the placeholder of
 * academic_base, by registering the same identifiers in its own
 * `Configuration/FrontendIcons.php`. That works from a package that loads after both.
 *
 * In a site, composer decides that order from the requirements. A TYPO3 v14 test
 * instance does not: it drops every requirement on a package the root composer install
 * knows, `fgtclb/academic-base` included, and falls back to the order of the extension
 * keys. The fixture `tests/frontend-icons-override` therefore requires academic_base only
 * and sorts after `test_frontend_icons` by its key, and the first test asserts the
 * order, so a change of it fails here rather than in the replacement.
 */
final class IconViewHelperOverrideTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/frontend-icons',
        'tests/frontend-icons-override',
    ];

    #[Test]
    public function theSitePackageLoadsAfterTheExtensionAndAcademicBase(): void
    {
        $packageKeys = array_keys($this->get(PackageManager::class)->getActivePackages());
        $sitePackagePosition = array_search('test_frontend_icons_override', $packageKeys, true);

        $this->assertGreaterThan(array_search('test_frontend_icons', $packageKeys, true), $sitePackagePosition);
        $this->assertGreaterThan(array_search('academic_base', $packageKeys, true), $sitePackagePosition);
    }

    #[Test]
    public function theSitePackageReplacesAnIconAndThePlaceholder(): void
    {
        [$replaced, $unknown] = $this->renderParts('Replaced');

        $this->assertStringContainsString('data-identifier="test-frontend-replaced"', $replaced);
        $this->assertStringContainsString('d="M8 1l7 14H1z"', $replaced);
        $this->assertStringContainsString('data-identifier="default-not-found"', $unknown);
        $this->assertStringContainsString('d="M3 3l10 10M13 3L3 13"', $unknown);
        $this->assertStringNotContainsString('default-not-found.svg', $unknown);
    }

    /**
     * @return list<string>
     */
    private function renderParts(string $template): array
    {
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: ['EXT:test_frontend_icons/Resources/Private/Templates/'],
            request: $request,
        ));

        return array_map(trim(...), explode("\n===\n", trim($view->render($template))));
    }
}
