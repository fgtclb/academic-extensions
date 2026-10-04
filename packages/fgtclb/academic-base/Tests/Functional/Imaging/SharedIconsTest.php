<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\IconFilesAssertionTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * The shared icon set the academic extensions render: the actions of a control, the
 * states a control shows and the glyphs in front of a piece of information. They are
 * frontend icons, registered in `Configuration/FrontendIcons.php` and rendered with
 * `ab:icon`, and nothing in the backend shows them. Each one follows the text colour,
 * which the colour scheme aware provider and `currentColor` give it, and sizes itself
 * by the font size, because a frontend page has no stylesheet that would.
 *
 * The identifiers are spelled out here rather than read back out of the registration,
 * so a rename has to be made twice instead of silently agreeing with itself.
 */
final class SharedIconsTest extends AbstractAcademicBaseTestCase
{
    use FrontendIconsAssertionTrait;
    use IconFilesAssertionTrait;

    private const IDENTIFIERS = [
        'tx-academicbase-action-add',
        'tx-academicbase-action-back',
        'tx-academicbase-action-clear',
        'tx-academicbase-action-close',
        'tx-academicbase-action-collapse',
        'tx-academicbase-action-delete',
        'tx-academicbase-action-drag',
        'tx-academicbase-action-edit',
        'tx-academicbase-action-expand',
        'tx-academicbase-action-help',
        'tx-academicbase-action-move-down',
        'tx-academicbase-action-move-up',
        'tx-academicbase-action-save',
        'tx-academicbase-action-undo',
        'tx-academicbase-action-upload-image',
        'tx-academicbase-action-view',
        'tx-academicbase-action-view-close',
        'tx-academicbase-state-hidden',
        'tx-academicbase-state-visible',
        'tx-academicbase-info-calendar',
        'tx-academicbase-info-company',
        'tx-academicbase-info-contract',
        'tx-academicbase-info-degree',
        'tx-academicbase-info-department',
        'tx-academicbase-info-email',
        'tx-academicbase-info-employment',
        'tx-academicbase-info-information',
        'tx-academicbase-info-international',
        'tx-academicbase-info-link',
        'tx-academicbase-info-location',
        'tx-academicbase-info-partnership',
        'tx-academicbase-info-person',
        'tx-academicbase-info-phone',
        'tx-academicbase-info-recommendation',
        'tx-academicbase-info-role',
        'tx-academicbase-info-room',
        'tx-academicbase-info-sector',
        'tx-academicbase-info-time',
    ];

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function sharedIconIdentifiers(): \Generator
    {
        foreach (self::IDENTIFIERS as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconIsAFrontendIconWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider($identifier, CurrentColorSvgIconProvider::class);
        $this->assertFrontendIconIsNotABackendIcon($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertFrontendIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function sharedIconIsInTheHouseFormat(string $identifier): void
    {
        $this->assertFrontendIconIsInTheHouseFormat($identifier);
    }

    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function renderedSharedIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedFrontendIconCarriesItsIdentifier($identifier);
    }

    /**
     * What a template gets from `ab:icon`: the wrapper of core's markup around the
     * inlined file, with or without `alternativeMarkupIdentifier="inline"`, so a
     * template that forgets the argument still gets an icon in the text colour. The
     * attribution comment of the file does not reach the page.
     */
    #[Test]
    #[DataProvider('sharedIconIdentifiers')]
    public function iconViewHelperRendersTheSharedIconInline(string $identifier): void
    {
        $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $view = $this->get(ViewFactoryInterface::class)->create(new ViewFactoryData(
            templatePathAndFilename: __DIR__ . '/Fixtures/SharedIcon.html',
            request: $request,
        ));
        $view->assign('identifier', $identifier);
        [$default, $inline] = array_map(trim(...), explode("\n===\n", trim($view->render())));

        $this->assertSame($inline, $default, 'The default markup differs from the inline markup.');
        $this->assertStringStartsWith(
            '<span class="t3js-icon icon icon-size-small icon-state-default icon-' . $identifier . '"'
            . ' data-identifier="' . $identifier . '" aria-hidden="true">' . "\n"
            . "\t" . '<span class="icon-markup">' . "\n"
            . '<svg',
            $inline,
        );
        $this->assertStringContainsString('width="1em" height="1em" fill="currentColor"', $inline);
        $this->assertStringNotContainsString('<img', $inline);
        $this->assertStringNotContainsString('<!--', $inline);
        $this->assertStringNotContainsString('default-not-found', $inline);
    }

    /**
     * The list above cannot see an identifier that is registered and missing from it, so
     * the registered `tx-academicbase-*` set is compared against it as a whole.
     */
    #[Test]
    public function everyRegisteredSharedIconIsListed(): void
    {
        $registered = array_values(array_filter(
            $this->get(FrontendIconRegistry::class)->getAllRegisteredIconIdentifiers(),
            static fn(string $identifier): bool => str_starts_with($identifier, 'tx-academicbase-'),
        ));
        $listed = self::IDENTIFIERS;
        sort($registered);
        sort($listed);

        $this->assertSame($listed, $registered);
    }

    /**
     * Every identifier follows the scheme in a frontend group and draws the file named
     * after it. The placeholder `default-not-found` is exempt.
     */
    #[Test]
    public function identifiersFollowTheNamingScheme(): void
    {
        $this->assertFrontendIconIdentifiersFollowTheNamingScheme('academic_base');
    }

    #[Test]
    public function everyFrontendIconDrawnFromTheSetIsInTheHouseFormat(): void
    {
        $this->assertEveryFrontendIconOfTheExtensionIsInTheHouseFormat('academic_base');
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_base');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_base');
    }
}
