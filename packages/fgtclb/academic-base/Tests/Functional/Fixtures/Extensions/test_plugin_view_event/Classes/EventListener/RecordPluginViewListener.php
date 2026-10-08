<?php

declare(strict_types=1);

namespace TESTS\TestPluginViewEvent\EventListener;

use FGTCLB\AcademicBase\Event\ModifyPluginViewEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;

/**
 * Records every rendering an academic plugin announces, as `<extension>/<plugin>/<action>`,
 * and the names of the variables the action had assigned by then. It assigns `probe`, which
 * the template overrides of this extension print. A test resets it before it renders.
 */
final class RecordPluginViewListener
{
    /**
     * @var list<string>
     */
    public static array $renderings = [];

    /**
     * @var list<list<string>>
     */
    public static array $assignedBefore = [];

    /**
     * Per rendering whose action assigned a `jobs` query result, whether the action had
     * fetched all its records by then: `true` fetched, `false` not fetched.
     *
     * @var list<bool>
     */
    public static array $jobsFetched = [];

    /**
     * Set by a test to try to replace the validations of the job form.
     */
    public static bool $replaceValidations = false;

    /**
     * Set by a test to replace `data`, which the action assigned before the event.
     */
    public static bool $replaceData = false;

    #[AsEventListener(identifier: 'test-plugin-view-event/record')]
    public function __invoke(ModifyPluginViewEvent $event): void
    {
        $context = $event->getPluginControllerActionContext();
        $view = $event->getView();
        $rendering = sprintf(
            '%s/%s/%s',
            $context->getControllerExtensionName(),
            $context->getPluginName(),
            $context->getActionName(),
        );
        self::$renderings[] = $rendering;
        // TYPO3 v13 and v14 both hand an Extbase action this adapter.
        self::$assignedBefore[] = $view instanceof FluidViewAdapter
            ? array_values($view->getRenderingContext()->getVariableProvider()->getAllIdentifiers())
            : [];
        if ($view instanceof FluidViewAdapter) {
            $jobs = $view->getRenderingContext()->getVariableProvider()->get('jobs');
            if ($jobs instanceof QueryResult) {
                // The records a query result fetched are kept in this property, which
                // stays `null` while only its count or a slice of it was asked for.
                self::$jobsFetched[] = (new \ReflectionProperty(QueryResult::class, 'queryResult'))->getValue($jobs) !== null;
            }
        }
        $view->assign('probe', 'probe of ' . $rendering);
        if (self::$replaceValidations) {
            $view->assign('validations', []);
        }
        if (self::$replaceData) {
            $view->assign('data', ['header' => 'header of the listener']);
        }
    }
}
