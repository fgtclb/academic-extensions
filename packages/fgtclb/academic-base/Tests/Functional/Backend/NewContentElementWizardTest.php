<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Backend;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Renders the new content element wizard the way the page module opens it, and reads the
 * groups and elements back from the data the wizard hands to its web component.
 *
 * Every test pins one statement of the section "New content element wizard" in the
 * manual of academic_base (`Documentation/Integration/NewContentElementWizard.rst`), with the page TSconfig
 * that section shows. Each statement has a page of its own, so the cached page TSconfig of
 * one test can never answer another one.
 *
 * The fixture extension `test_wizard_content_elements` registers three elements in the
 * `academic` group, in the order first, second, third, and one each in the groups "Plugins"
 * and "Form elements".
 */
final class NewContentElementWizardTest extends AbstractAcademicBaseTestCase
{
    private const GROUP_FIRST_PAGE = 2;
    private const GROUP_RENAMED_PAGE = 3;
    private const ELEMENT_RENAMED_PAGE = 4;
    private const ELEMENT_HIDDEN_PAGE = 5;
    private const GROUP_HIDDEN_PAGE = 6;
    private const ELEMENTS_ORDERED_PAGE = 7;
    private const ELEMENTS_REDEFINED_PAGE = 8;
    private const ALTERNATIVE_LABEL_PAGE = 9;
    private const AFTER_SPECIAL_PAGE = 10;
    private const TYPE_HIDDEN_PAGE = 11;

    private const PAGE_TSCONFIG = [
        self::GROUP_FIRST_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.before = default
            TSCONFIG,
        self::GROUP_RENAMED_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.header = University
            TSCONFIG,
        self::ELEMENT_RENAMED_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.elements.testwizard_second {
              title = Renamed second element
              description = LLL:EXT:academic_base/Resources/Private/Language/locallang_be.xlf:content.ctype.group.label
            }
            TSCONFIG,
        self::ELEMENT_HIDDEN_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.removeItems := addToList(testwizard_second)
            TSCONFIG,
        self::GROUP_HIDDEN_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.removeItems := addToList(academic)
            TSCONFIG,
        self::ELEMENTS_ORDERED_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.elements {
              testwizard_third.before = testwizard_first
            }
            TSCONFIG,
        self::ELEMENTS_REDEFINED_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.elements {
              testwizard_third {
                iconIdentifier = content-text
                title = Third academic element
                tt_content_defValues.CType = testwizard_third
              }
              testwizard_first {
                iconIdentifier = content-text
                title = First academic element
                tt_content_defValues.CType = testwizard_first
              }
              testwizard_second {
                iconIdentifier = content-text
                title = Second academic element
                tt_content_defValues.CType = testwizard_second
              }
            }
            TSCONFIG,
        self::ALTERNATIVE_LABEL_PAGE => <<<'TSCONFIG'
            TCEFORM.tt_content.CType.altLabels.testwizard_second = Alternative label
            TSCONFIG,
        self::AFTER_SPECIAL_PAGE => <<<'TSCONFIG'
            mod.wizards.newContentElement.wizardItems.academic.after = special
            TSCONFIG,
        self::TYPE_HIDDEN_PAGE => <<<'TSCONFIG'
            TCEFORM.tt_content.CType.removeItems := addToList(testwizard_second)
            TSCONFIG,
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/wizard-content-elements',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewContentElementWizard.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        foreach (self::PAGE_TSCONFIG as $pageId => $tsConfig) {
            $connection->update('pages', ['TSconfig' => $tsConfig], ['uid' => $pageId]);
        }
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function theGroupIsLabelledAcademic(): void
    {
        $this->assertSame('Academic', $this->wizardOn(1)['academic']['label']);
    }

    /**
     * The always loaded page TSconfig of academic_base labels the group and sets no
     * position, so the groups follow the order of their TCA registration, which ends with
     * the group academic_base registers.
     */
    #[Test]
    public function theGroupComesLastAfterTheGroupsOfTypo3(): void
    {
        $this->assertSame(
            ['default', 'lists', 'menu', 'forms', 'special', 'plugins', 'academic'],
            array_keys($this->wizardOn(1)),
        );
    }

    /**
     * The position academic_base shipped up to ACE-792. A single `before` or `after` makes
     * TYPO3 order every group by those settings instead of by TCA: the two groups move to
     * the front, and the groups without a position follow in the alphabetical order of
     * their identifiers. The fixture adds the element in "Plugins" that shows the
     * difference, as a group behind "Special elements", and the one in "Form elements"
     * that shows the alphabetical order.
     */
    #[Test]
    public function afterSpecialPutsSpecialElementsAndTheAcademicGroupFirst(): void
    {
        $this->assertSame(
            ['special', 'academic', 'default', 'forms', 'lists', 'menu', 'plugins'],
            array_keys($this->wizardOn(self::AFTER_SPECIAL_PAGE)),
        );
    }

    #[Test]
    public function theElementsOfTheGroupFollowTheOrderOfTheirRegistration(): void
    {
        $this->assertSame(
            [
                'academic_testwizard_first' => 'First academic element',
                'academic_testwizard_second' => 'Second academic element',
                'academic_testwizard_third' => 'Third academic element',
            ],
            $this->wizardOn(1)['academic']['items'],
        );
    }

    #[Test]
    public function theGroupMovesToTheFrontWithBefore(): void
    {
        $this->assertSame(
            ['academic', 'default', 'forms', 'lists', 'menu', 'plugins', 'special'],
            array_keys($this->wizardOn(self::GROUP_FIRST_PAGE)),
        );
    }

    #[Test]
    public function theGroupTakesTheHeaderOfThePageTsConfig(): void
    {
        $this->assertSame('University', $this->wizardOn(self::GROUP_RENAMED_PAGE)['academic']['label']);
    }

    #[Test]
    public function anElementTakesTheTitleAndDescriptionOfThePageTsConfig(): void
    {
        $groups = $this->wizardOn(self::ELEMENT_RENAMED_PAGE);

        $this->assertSame('Renamed second element', $groups['academic']['items']['academic_testwizard_second']);
        // An `LLL:` reference, resolved like the label of the group it points to.
        $this->assertSame('Academic', $groups['academic']['descriptions']['academic_testwizard_second']);
    }

    #[Test]
    public function removeItemsOfTheGroupHidesOneElement(): void
    {
        $this->assertSame(
            ['academic_testwizard_first', 'academic_testwizard_third'],
            array_keys($this->wizardOn(self::ELEMENT_HIDDEN_PAGE)['academic']['items']),
        );
    }

    /**
     * The way every academic extension hides its own content elements until a site enables
     * them: a type the select field does not offer is not offered by the wizard either.
     */
    #[Test]
    public function aTypeRemovedFromTheSelectFieldIsMissingFromTheWizardToo(): void
    {
        $this->assertSame(
            ['academic_testwizard_first', 'academic_testwizard_third'],
            array_keys($this->wizardOn(self::TYPE_HIDDEN_PAGE)['academic']['items']),
        );
    }

    #[Test]
    public function removeItemsOfTheWizardHidesTheWholeGroup(): void
    {
        $groups = $this->wizardOn(self::GROUP_HIDDEN_PAGE);

        $this->assertArrayHasKey('special', $groups);
        $this->assertArrayNotHasKey('academic', $groups);
    }

    /**
     * Feature #87435 of TYPO3 14.2: `before` and `after` order the elements of a group.
     */
    #[Test]
    #[Group('not-core-13')]
    public function beforeAndAfterOrderTheElementsOfTheGroup(): void
    {
        $this->assertSame(
            ['academic_testwizard_third', 'academic_testwizard_first', 'academic_testwizard_second'],
            array_keys($this->wizardOn(self::ELEMENTS_ORDERED_PAGE)['academic']['items']),
        );
    }

    /**
     * TYPO3 v13 reads `before` and `after` of a group, never of an element.
     */
    #[Test]
    #[Group('not-core-14')]
    public function beforeAndAfterOfAnElementChangeNothingOnTypo3V13(): void
    {
        $this->assertSame(
            ['academic_testwizard_first', 'academic_testwizard_second', 'academic_testwizard_third'],
            array_keys($this->wizardOn(self::ELEMENTS_ORDERED_PAGE)['academic']['items']),
        );
    }

    /**
     * An element defined in page TSconfig with the same `CType` replaces the one TYPO3
     * builds from TCA, and comes after the elements that are not defined again. Defining
     * all of them again therefore orders them on every version.
     */
    #[Test]
    public function elementsDefinedAgainTakeTheOrderOfThePageTsConfig(): void
    {
        $this->assertSame(
            [
                'academic_testwizard_third' => 'Third academic element',
                'academic_testwizard_first' => 'First academic element',
                'academic_testwizard_second' => 'Second academic element',
            ],
            $this->wizardOn(self::ELEMENTS_REDEFINED_PAGE)['academic']['items'],
        );
    }

    /**
     * `altLabels` renames the type in the select field of the record only.
     */
    #[Test]
    public function anAlternativeLabelOfTheTypeLeavesTheWizardTitleAlone(): void
    {
        $this->assertSame(
            'Second academic element',
            $this->wizardOn(self::ALTERNATIVE_LABEL_PAGE)['academic']['items']['academic_testwizard_second'],
        );
    }

    /**
     * The groups of the wizard in the order it shows them, each with its label, the titles of
     * its elements and their descriptions, keyed by the identifiers of the wizard.
     *
     * @return array<string, array{label: string, items: array<string, string>, descriptions: array<string, string>}>
     */
    private function wizardOn(int $pageId): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/content/wizard/new'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('route', new Route('/record/content/wizard/new', ['packageName' => 'typo3/cms-backend']))
            ->withQueryParams(['id' => (string)$pageId]);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $html = (string)$this->get(NewContentElementController::class)->handleRequest($request)->getBody();
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $wizards = (new \DOMXPath($document))->query('//*[@categories]');
        $this->assertNotFalse($wizards);
        $wizard = $wizards->item(0);
        $this->assertInstanceOf(\DOMElement::class, $wizard, 'The response renders no wizard.');
        $categories = json_decode($wizard->getAttribute('categories'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($categories);

        $groups = [];
        foreach ($categories as $category) {
            $group = ['label' => $category['label'], 'items' => [], 'descriptions' => []];
            foreach ($category['items'] as $item) {
                $group['items'][$item['identifier']] = $item['label'];
                $group['descriptions'][$item['identifier']] = $item['description'];
            }
            $groups[$category['identifier']] = $group;
        }

        return $groups;
    }
}
