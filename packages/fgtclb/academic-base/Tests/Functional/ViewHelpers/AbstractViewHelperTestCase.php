<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\ViewHelpers;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Renders a fixture template from `Tests/Functional/Fixtures/Templates/` and returns
 * the result, so a view helper is asserted the way a template uses it rather than by
 * calling its methods.
 *
 * That is what makes an argument such as `month="0"` observable at all: Fluid decides
 * how a literal reaches a registered `bool` argument, and calling the render method
 * with a prepared argument array would answer the question nobody asked.
 *
 * A locale is given by handing the request a `SiteLanguage`, which is where a view
 * helper of this repository reads it from. A view is built per render, because a
 * Fluid view carries the variables and the parsed template of the last one.
 */
abstract class AbstractViewHelperTestCase extends AbstractAcademicBaseTestCase
{
    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = [], ?string $locale = null): string
    {
        $request = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        if ($locale !== null) {
            $request = $request->withAttribute('language', new SiteLanguage(
                0,
                $locale,
                new Uri('https://www.acme.com/'),
                ['title' => $locale],
            ));
        }
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templateRootPaths: [__DIR__ . '/../Fixtures/Templates/'],
            request: $request,
        ));
        $view->assignMultiple($variables);

        return trim($view->render($template));
    }
}
