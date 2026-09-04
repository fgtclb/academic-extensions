<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\SettingsFileLoader;
use FGTCLB\AcademicBase\Tests\Unit\Settings\Fixtures\TestSettings;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The loader defines how an installation overrides a settings file: every active
 * package may ship one, and they are folded together top-level key by top-level key.
 * That merge depth is what the integrator documentation promises - "restate the whole
 * block" - so it is pinned here together with the cache round trip that makes the
 * object graph's `__set_state()` implementations load-bearing.
 */
final class SettingsFileLoaderTest extends UnitTestCase
{
    /**
     * `array_merge()` on the top level only: the second package replaces the whole
     * `validations` map of the first, it does not add `lastName` next to `firstName`.
     * A key the second package does not mention survives.
     */
    #[Test]
    public function aLaterPackageReplacesTheWholeTopLevelKey(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['first', 'second']),
        );

        $this->assertSame(
            [
                'validations' => ['profile' => ['lastName' => ['readonly']]],
                'types' => ['first' => 1],
            ],
            $subject->loadMergedArray('Configuration/Test/Settings.yaml'),
        );
    }

    /**
     * Package order is what decides who wins, so the same two packages in the other
     * order give the other result - there is no ordering of its own in the loader.
     */
    #[Test]
    public function thePackageOrderDecidesWhichPackageWins(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['second', 'first']),
        );

        $this->assertSame(
            [
                'validations' => ['profile' => ['firstName' => ['required']]],
                'types' => ['first' => 1],
            ],
            $subject->loadMergedArray('Configuration/Test/Settings.yaml'),
        );
    }

    /**
     * A package without the file and a package whose file is empty both contribute
     * nothing - an empty YAML file parses to `null`, which must not end the walk or
     * wipe what was collected before it.
     */
    #[Test]
    public function packagesWithoutTheFileOrWithAnEmptyFileAreSkipped(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['first', 'without', 'empty']),
        );

        $this->assertSame(
            [
                'validations' => ['profile' => ['firstName' => ['required']]],
                'types' => ['first' => 1],
            ],
            $subject->loadMergedArray('Configuration/Test/Settings.yaml'),
        );
    }

    #[Test]
    public function noPackageShippingTheFileProducesAnEmptyArray(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['without']),
        );

        $this->assertSame([], $subject->loadMergedArray('Configuration/Test/Settings.yaml'));
    }

    /**
     * On a cache miss the merged array goes through the normaliser, and what the
     * normaliser returns is what the caller gets and what is written to the cache -
     * as a `return <var_export>;` statement `PhpFrontend::require()` can evaluate.
     */
    #[Test]
    public function theNormalizedObjectIsReturnedAndCached(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->with('Test_Settings')->willReturn(false);
        $written = null;
        $cache->expects($this->once())->method('set')
            ->with('Test_Settings', $this->callback(static function (string $code) use (&$written): bool {
                $written = $code;
                return true;
            }));
        $subject = new SettingsFileLoader($cache, $this->packageManager(['first']));

        $settings = $subject->load(
            'Configuration/Test/Settings.yaml',
            'Test_Settings',
            TestSettings::class,
            static fn(array $merged): TestSettings => new TestSettings(raw: $merged),
        );

        $this->assertSame(['first' => 1], $settings->raw['types']);
        $this->assertIsString($written);
        $this->assertStringStartsWith('return ', $written);
        $this->assertEquals($settings, eval($written));
    }

    /**
     * A cache hit short-circuits everything: no package is asked for its path, the
     * normaliser is not called, and the cached instance is returned as is.
     */
    #[Test]
    public function aCachedObjectOfTheExpectedClassIsReturnedWithoutLoading(): void
    {
        $cached = new TestSettings(raw: ['cached' => true]);
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->with('Test_Settings')->willReturn($cached);
        $cache->expects($this->never())->method('set');
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->expects($this->never())->method('getActivePackages');
        $subject = new SettingsFileLoader($cache, $packageManager);

        $settings = $subject->load(
            'Configuration/Test/Settings.yaml',
            'Test_Settings',
            TestSettings::class,
            fn(array $merged): TestSettings => $this->fail('The normaliser must not run on a cache hit.'),
        );

        $this->assertSame($cached, $settings);
    }

    /**
     * A cache entry of another class - a stale entry written by an older version of
     * the settings object, or another extension's identifier - is not trusted; the
     * settings are loaded and the entry is rewritten.
     */
    #[Test]
    public function aCachedObjectOfAnotherClassIsReplaced(): void
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->with('Test_Settings')->willReturn(new \stdClass());
        $cache->expects($this->once())->method('set');
        $subject = new SettingsFileLoader($cache, $this->packageManager(['first']));

        $settings = $subject->load(
            'Configuration/Test/Settings.yaml',
            'Test_Settings',
            TestSettings::class,
            static fn(array $merged): TestSettings => new TestSettings(raw: $merged),
        );

        $this->assertSame(['first' => 1], $settings->raw['types']);
    }

    private function cacheWithoutEntry(): PhpFrontend
    {
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn(false);
        return $cache;
    }

    /**
     * @param list<string> $fixturePackages Directory names below `Fixtures/Packages/`, in loading order
     */
    private function packageManager(array $fixturePackages): PackageManager
    {
        $packages = [];
        foreach ($fixturePackages as $fixturePackage) {
            $package = $this->createMock(PackageInterface::class);
            $package->method('getPackagePath')->willReturn(__DIR__ . '/Fixtures/Packages/' . $fixturePackage . '/');
            $packages[] = $package;
        }
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);
        return $packageManager;
    }
}
