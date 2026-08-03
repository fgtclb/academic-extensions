<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * Renders a fixture template from `Tests/Functional/Fixtures/Templates/` and returns the
 * result, so a view helper is asserted the way a template uses it rather than by calling
 * its methods.
 *
 * That also keeps the tests independent of how a view helper is entered - `render()` or
 * the deprecated `renderStatic()` - which is what makes them usable as the safety net of a
 * migration between the two.
 *
 * The view is built from a rendering context rather than through `ViewFactoryInterface`,
 * which does not exist on TYPO3 v12.
 */
abstract class AbstractViewHelperTestCase extends AbstractAcademicProjectsTestCase
{
    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = []): string
    {
        $renderingContext = $this->get(RenderingContextFactory::class)->create([
            'templateRootPaths' => [__DIR__ . '/../Fixtures/Templates/'],
        ]);

        $view = new TemplateView($renderingContext);
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }
}
