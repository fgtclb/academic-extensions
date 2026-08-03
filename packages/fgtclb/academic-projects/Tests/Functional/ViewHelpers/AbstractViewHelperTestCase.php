<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Renders a fixture template from `Tests/Functional/Fixtures/Templates/` and returns the
 * result, so a view helper is asserted the way a template uses it rather than by calling
 * its methods.
 *
 * That also keeps the tests independent of how a view helper is entered - `render()` or
 * the deprecated `renderStatic()` - which is what makes them usable as the safety net of a
 * migration between the two. On branch `2`, where that migration happens, the same tests
 * run against a view built from a rendering context, because `ViewFactoryInterface` does
 * not exist on TYPO3 v12.
 */
abstract class AbstractViewHelperTestCase extends AbstractAcademicProjectsTestCase
{
    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = []): string
    {
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/'],
            request: (new ServerRequest())
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE),
        ));
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }
}
