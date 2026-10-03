<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendIconsAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * The registry as the container builds it, against the fixture extension
 * `tests/frontend-icons`: its `Configuration/FrontendIcons.php`, its
 * `Configuration/Icons.php` and its listener of the collect event. What the two
 * registries know of each other is only observable here.
 */
final class FrontendIconRegistryTest extends AbstractAcademicBaseTestCase
{
    use FrontendIconsAssertionTrait;

    private const FIXTURE_FILE = __DIR__ . '/../Fixtures/Extensions/test_frontend_icons/Configuration/FrontendIcons.php';

    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    #[Test]
    public function anIconContributedByAListenerIsRegistered(): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider('test-frontend-contributed', CurrentColorSvgIconProvider::class);
        $this->assertRenderedFrontendIconCarriesItsIdentifier('test-frontend-contributed');
    }

    #[Test]
    public function aFileEntryReplacesAContributedIcon(): void
    {
        $this->assertSame(
            'EXT:test_frontend_icons/Resources/Public/Icons/file-wins.svg',
            $this->get(FrontendIconRegistry::class)->getIconConfiguration('test-frontend-contributed-overridden')['options']['source'] ?? null,
        );
    }

    #[Test]
    public function academicBaseRegistersThePlaceholder(): void
    {
        $this->assertSame(
            [
                'provider' => SvgIconProvider::class,
                'options' => ['source' => 'EXT:core/Resources/Public/Icons/T3Icons/svgs/default/default-not-found.svg'],
            ],
            $this->get(FrontendIconRegistry::class)->getIconConfiguration('default-not-found'),
        );
    }

    #[Test]
    public function anEntryWithoutProviderGetsTheDetectedOneAndAnEntryWithoutSourceIsSkipped(): void
    {
        $this->assertFrontendIconIsRegisteredWithProvider('test-frontend-detected', SvgIconProvider::class);
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered('test-frontend-without-source'));
    }

    #[Test]
    public function anIconOfTheBackendRegistryIsUnknownToTheFrontendRegistry(): void
    {
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered('test-backend-only'));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered('test-backend-only'));
        $this->assertFalse($this->get(FrontendIconRegistry::class)->isRegistered('actions-add'));
    }

    /**
     * Every identifier of the fixture's `FrontendIcons.php` and of its listener, except
     * the one its `Icons.php` registers as well, is unknown to the backend.
     */
    #[Test]
    public function anIconOfTheFrontendRegistryIsUnknownToTheBackendRegistry(): void
    {
        $identifiers = array_map(strval(...), array_keys(require self::FIXTURE_FILE));
        $identifiers[] = 'test-frontend-contributed';
        $frontendIconRegistry = $this->get(FrontendIconRegistry::class);

        foreach (array_diff($identifiers, ['test-frontend-both', 'test-frontend-without-source']) as $identifier) {
            $this->assertTrue($frontendIconRegistry->isRegistered($identifier), $identifier);
            $this->assertFrontendIconIsNotABackendIcon($identifier);
        }
        $this->assertIconIsRegisteredInBothRegistries('test-frontend-both');
    }

    #[Test]
    public function aCurrentColorIconFollowsTheTextColour(): void
    {
        $this->assertFrontendIconMarkupFollowsTheTextColour('test-frontend-current-color');
    }
}
