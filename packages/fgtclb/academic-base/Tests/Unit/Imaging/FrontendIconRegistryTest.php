<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Imaging;

use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The registry defines how a site package replaces a frontend icon: every active
 * package may ship `Configuration/FrontendIcons.php`, a later package replaces an
 * identifier wholesale, and a file entry replaces an icon a listener contributed.
 * Those rules are what the integrator documentation promises, so they are pinned
 * here together with the cache round trip and its key.
 */
final class FrontendIconRegistryTest extends UnitTestCase
{
    /**
     * Nothing of the first package's entry survives, `spinning` included: the merge is
     * `array_merge()` per identifier, as core merges `Icons.php`, not a recursive one.
     */
    #[Test]
    public function aLaterPackageReplacesAnIdentifierWholesale(): void
    {
        $subject = $this->subject(['first', 'second']);

        $this->assertSame(
            [
                'provider' => BitmapIconProvider::class,
                'options' => ['source' => 'EXT:second/Resources/Public/Icons/phone.png'],
            ],
            $subject->getIconConfiguration('example-phone'),
        );
        $this->assertTrue($subject->isRegistered('example-only-first'));
    }

    #[Test]
    public function thePackageOrderDecidesWhichEntryWins(): void
    {
        $subject = $this->subject(['second', 'first']);

        $this->assertSame(
            [
                'provider' => SvgIconProvider::class,
                'options' => ['source' => 'EXT:first/Resources/Public/Icons/phone.svg', 'spinning' => true],
            ],
            $subject->getIconConfiguration('example-phone'),
        );
    }

    /**
     * The event is dispatched before any file is read, so the package of the file may
     * load before or after the package of the listener.
     */
    #[Test]
    public function aFileEntryReplacesAContributedIcon(): void
    {
        $subject = $this->subject(['file-entry'], $this->dispatcherContributing('example-generated'));

        $this->assertSame(
            [
                'provider' => SvgIconProvider::class,
                'options' => ['source' => 'EXT:file_entry/Resources/Public/Icons/generated.svg'],
            ],
            $subject->getIconConfiguration('example-generated'),
        );
    }

    #[Test]
    public function aContributedIconIsRegistered(): void
    {
        $subject = $this->subject(['first'], $this->dispatcherContributing('example-contributed'));

        $this->assertSame(
            [
                'provider' => BitmapIconProvider::class,
                'options' => ['source' => 'EXT:contributing/Resources/Public/Icons/contributed.png'],
            ],
            $subject->getIconConfiguration('example-contributed'),
        );
    }

    /**
     * The suffix rule of `IconRegistry::detectIconProvider()`, case insensitive.
     */
    #[Test]
    public function aMissingProviderIsDetectedFromTheSource(): void
    {
        $subject = $this->subject(['detecting']);

        $this->assertSame(SvgIconProvider::class, $subject->getIconConfiguration('example-svg')['provider'] ?? null);
        $this->assertSame(BitmapIconProvider::class, $subject->getIconConfiguration('example-bitmap')['provider'] ?? null);
    }

    #[Test]
    public function anEntryWithoutProviderAndSourceIsSkipped(): void
    {
        $subject = $this->subject(['skipping']);

        $this->assertFalse($subject->isRegistered('example-neither'));
        $this->assertFalse($subject->isRegistered('example-empty-source'));
        $this->assertTrue($subject->isRegistered('example-kept'));
    }

    #[Test]
    public function anEntryThatIsNotAnArrayIsSkipped(): void
    {
        $subject = $this->subject(['skipping']);

        $this->assertFalse($subject->isRegistered('example-not-an-array'));
        $this->assertTrue($subject->isRegistered('example-kept'));
    }

    /**
     * A file that returns something else than an array is ignored, and so is a package
     * without the file. The packages around them still count.
     */
    #[Test]
    public function aFileThatIsNotAnArrayAndAPackageWithoutTheFileAreSkipped(): void
    {
        $subject = $this->subject(['first', 'not-an-array', 'without-file', 'second']);

        $this->assertSame(BitmapIconProvider::class, $subject->getIconConfiguration('example-phone')['provider'] ?? null);
        $this->assertTrue($subject->isRegistered('example-only-first'));
    }

    #[Test]
    public function aProviderThatIsNotAnIconProviderFailsNamingTheIcon(): void
    {
        $subject = $this->subject(['first', 'invalid-provider']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791061901);
        $this->expectExceptionMessage('"example-invalid"');

        $subject->isRegistered('example-phone');
    }

    /**
     * Contributed icons first, then the files in loading order, a replaced identifier
     * at the place of its first registration, and nothing a normalisation skipped.
     */
    #[Test]
    public function allRegisteredIconIdentifiersAreListedInRegistrationOrder(): void
    {
        $subject = $this->subject(['first', 'skipping', 'second'], $this->dispatcherContributing('example-contributed'));

        $this->assertSame(
            ['example-contributed', 'example-phone', 'example-only-first', 'example-kept'],
            $subject->getAllRegisteredIconIdentifiers(),
        );
    }

    #[Test]
    public function aCachedEntryIsListedWithoutBuilding(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn([
            'example-cached' => ['provider' => SvgIconProvider::class, 'options' => ['source' => 'cached.svg']],
            'example-also-cached' => ['provider' => SvgIconProvider::class, 'options' => ['source' => 'also.svg']],
        ]);
        $cache->expects($this->never())->method('set');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('getActivePackages');

        $subject = new FrontendIconRegistry(
            $cache,
            $packageManager,
            $this->dispatcherContributing(null),
            new PackageDependentCacheIdentifier($this->packageManagerWithCacheIdentifier('packages')),
        );

        $this->assertSame(['example-cached', 'example-also-cached'], $subject->getAllRegisteredIconIdentifiers());
    }

    #[Test]
    public function aCachedEntryIsReturnedWithoutBuilding(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn([
            'example-cached' => ['provider' => SvgIconProvider::class, 'options' => ['source' => 'cached.svg']],
        ]);
        $cache->expects($this->never())->method('set');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('getActivePackages');
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->expects($this->never())->method('dispatch');

        $subject = new FrontendIconRegistry(
            $cache,
            $packageManager,
            $eventDispatcher,
            new PackageDependentCacheIdentifier($this->packageManagerWithCacheIdentifier('packages')),
        );

        $this->assertTrue($subject->isRegistered('example-cached'));
        $this->assertFalse($subject->isRegistered('example-phone'));
    }

    /**
     * Written in the shape core writes its own `Icons_` entry, normalised, under the
     * key core's `PackageDependentCacheIdentifier` gives the prefix.
     */
    #[Test]
    public function aMissingEntryIsWrittenUnderThePackageDependentKey(): void
    {
        $packageDependentCacheIdentifier = new PackageDependentCacheIdentifier($this->packageManagerWithCacheIdentifier('packages'));
        $expectedIdentifier = $packageDependentCacheIdentifier->withPrefix('AcademicFrontendIcons')->toString();
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        $cache->expects($this->once())->method('set')->with(
            $expectedIdentifier,
            'return ' . var_export([
                'example-phone' => ['provider' => BitmapIconProvider::class, 'options' => ['source' => 'EXT:second/Resources/Public/Icons/phone.png']],
            ], true) . ';',
        );

        $subject = new FrontendIconRegistry(
            $cache,
            $this->packageManager(['second']),
            $this->dispatcherContributing(null),
            $packageDependentCacheIdentifier,
        );

        $this->assertTrue($subject->isRegistered('example-phone'));
    }

    /**
     * A package activated or removed gives the package manager another cache
     * identifier, and the registry reads another entry without a flush. Core's class
     * is `@internal`, so a change of its behaviour has to show here.
     */
    #[Test]
    public function theKeyFollowsThePackageSet(): void
    {
        $writtenIdentifiers = [];
        foreach (['packages-before', 'packages-after'] as $packageCacheIdentifier) {
            $cache = $this->createMock(PhpFrontend::class);
            $cache->method('require')->willReturn(false);
            $cache->method('set')->willReturnCallback(
                static function (string $entryIdentifier) use (&$writtenIdentifiers): void {
                    $writtenIdentifiers[] = $entryIdentifier;
                },
            );
            $subject = new FrontendIconRegistry(
                $cache,
                $this->packageManager(['first']),
                $this->dispatcherContributing(null),
                new PackageDependentCacheIdentifier($this->packageManagerWithCacheIdentifier($packageCacheIdentifier)),
            );
            $subject->warmup();
        }

        $this->assertCount(2, $writtenIdentifiers);
        $this->assertNotSame($writtenIdentifiers[0], $writtenIdentifiers[1]);
    }

    #[Test]
    public function warmupBuildsAndWritesEvenWithAnEntry(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(['example-stale' => ['provider' => SvgIconProvider::class, 'options' => []]]);
        $cache->expects($this->once())->method('set')->with(
            $this->anything(),
            $this->stringContains("'example-only-first'"),
        );

        $subject = new FrontendIconRegistry(
            $cache,
            $this->packageManager(['first']),
            $this->dispatcherContributing(null),
            new PackageDependentCacheIdentifier($this->packageManagerWithCacheIdentifier('packages')),
        );

        $subject->warmup();
    }

    /**
     * @param list<string> $fixturePackages Directory names below `Fixtures/Packages/`, in loading order
     */
    private function subject(array $fixturePackages, ?EventDispatcherInterface $eventDispatcher = null): FrontendIconRegistry
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        return new FrontendIconRegistry(
            $cache,
            $this->packageManager($fixturePackages),
            $eventDispatcher ?? $this->dispatcherContributing(null),
            new PackageDependentCacheIdentifier($this->packageManagerWithCacheIdentifier('packages')),
        );
    }

    /**
     * A dispatcher whose one listener contributes `$identifier` as a bitmap, or nothing.
     */
    private function dispatcherContributing(?string $identifier): EventDispatcherInterface
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher->method('dispatch')->willReturnCallback(
            static function (object $event) use ($identifier): object {
                if ($identifier !== null && $event instanceof CollectFrontendIconsEvent) {
                    $event->addIcon(
                        $identifier,
                        BitmapIconProvider::class,
                        ['source' => 'EXT:contributing/Resources/Public/Icons/contributed.png'],
                    );
                }
                return $event;
            },
        );
        return $eventDispatcher;
    }

    /**
     * @param list<string> $fixturePackages
     */
    private function packageManager(array $fixturePackages): PackageManager
    {
        $packages = [];
        foreach ($fixturePackages as $fixturePackage) {
            $package = $this->createMock(PackageInterface::class);
            $package->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/Packages/' . $fixturePackage . '/');
            $package->method('getPackageKey')->willReturn($fixturePackage);
            $packages[] = $package;
        }
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);
        return $packageManager;
    }

    private function packageManagerWithCacheIdentifier(string $cacheIdentifier): PackageManager
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getCacheIdentifier')->willReturn($cacheIdentifier);
        return $packageManager;
    }
}
