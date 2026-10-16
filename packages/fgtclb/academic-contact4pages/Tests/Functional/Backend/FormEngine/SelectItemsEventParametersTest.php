<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * What a listener of `ModifyTcaSelectFieldItemsEvent` receives in the backend form, for
 * the contract select of a page contact that is edited on its own, on a page outside any
 * site.
 *
 * The parameters are recorded as the type alias the event declares, so PHPStan checks
 * every assertion below against that shape. Before ACE-927 the shape declared the inline
 * uids as `int`, the site as `Site` and every item as an array, and PHPStan reported
 * these assertions as always false, while TYPO3 passes exactly these values.
 *
 * @phpstan-import-type ItemsProcFuncParameters from ModifyTcaSelectFieldItemsEvent
 */
final class SelectItemsEventParametersTest extends AbstractAcademicContacts4PagesTestCase
{
    /**
     * @var list<ItemsProcFuncParameters>
     */
    private array $recordedParameters = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SelectItemsEventParameters/contact.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'select-items-event-parameters-recorder',
            function (ModifyTcaSelectFieldItemsEvent $event): void {
                $parameters = $event->getParameters();
                if ($parameters['table'] === 'tx_academiccontacts4pages_domain_model_contact'
                    && $parameters['field'] === 'contract'
                ) {
                    // On TYPO3 v12 and v13 `items` is a reference to the list that
                    // `AbstractItemProvider` replaces by `SelectItem` objects once the
                    // provider returned. The copy keeps the items as the listener
                    // received them.
                    $items = $parameters['items'];
                    unset($parameters['items']);
                    $parameters['items'] = $items;
                    $this->recordedParameters[] = $parameters;
                }
            }
        );
        $container->get(ListenerProvider::class)->addListener(
            ModifyTcaSelectFieldItemsEvent::class,
            'select-items-event-parameters-recorder',
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    #[Test]
    public function theInlineParentUidsAreEmptyStringsForARecordThatIsNotAnInlineChild(): void
    {
        $parameters = $this->parametersOfTheContractSelect();

        $this->assertSame('', $parameters['inlineParentUid'] ?? null);
        $this->assertSame('', $parameters['inlineTopMostParentUid'] ?? null);
    }

    #[Test]
    public function theSiteIsANullSiteForARecordOutsideAnySite(): void
    {
        $this->assertInstanceOf(NullSite::class, $this->parametersOfTheContractSelect()['site']);
    }

    /**
     * The field declares an empty item, which TYPO3 hands to the item provider as a
     * `SelectItem`. The contract the provider appends is an array.
     */
    #[Test]
    public function theDeclaredItemIsASelectItemAndTheAddedItemAnArray(): void
    {
        $items = $this->parametersOfTheContractSelect()['items'];

        $this->assertCount(2, $items);
        $this->assertInstanceOf(SelectItem::class, $items[0] ?? null);
        $this->assertIsArray($items[1] ?? null);
        $this->assertSame(1, $items[1]['value'] ?? null);
    }

    /**
     * @return ItemsProcFuncParameters
     */
    private function parametersOfTheContractSelect(): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'tx_academiccontacts4pages_domain_model_contact',
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            // Not `$this->get()`: on TYPO3 v12 `TcaDatabaseRecord` is not a public
            // service, so the test container cannot hand it over.
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );

        $this->assertCount(1, $this->recordedParameters);

        return $this->recordedParameters[0];
    }
}
