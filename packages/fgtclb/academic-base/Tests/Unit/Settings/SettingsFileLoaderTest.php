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
 * package may ship one, and they are folded together recursively - a later package
 * changes only the keys it names, a list replaces a list, and `null` removes a key.
 * Those rules are what the integrator documentation promises, so they are pinned here
 * together with the cache round trip that makes the object graph's `__set_state()`
 * implementations load-bearing.
 */
final class SettingsFileLoaderTest extends UnitTestCase
{
    /**
     * The merge is recursive: the second package adds `lastName` next to the
     * `firstName` of the first, it does not replace the whole `validations` map, and
     * the `types` key it does not mention survives.
     */
    #[Test]
    public function aLaterPackageChangesOnlyTheKeysItNames(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['first', 'second']),
        );

        $this->assertSame(
            [
                'validations' => ['profile' => [
                    'firstName' => ['required'],
                    'lastName' => ['readonly'],
                ]],
                'types' => ['first' => 1],
            ],
            $subject->loadMergedArray('Configuration/Test/Settings.yaml'),
        );
    }

    /**
     * Package order is what decides the key order of a partial override, so the same
     * two packages in the other order give the same values in the other order - there
     * is no ordering of its own in the loader.
     */
    #[Test]
    public function thePackageOrderDecidesTheKeyOrder(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['second', 'first']),
        );

        $this->assertSame(
            [
                'validations' => ['profile' => [
                    'lastName' => ['readonly'],
                    'firstName' => ['required'],
                ]],
                'types' => ['first' => 1],
            ],
            $subject->loadMergedArray('Configuration/Test/Settings.yaml'),
        );
    }

    /**
     * A list is a value, not a map: the later list replaces the earlier one instead of
     * being merged into it, because there is no identity to merge flags by - a project
     * could otherwise never drop `required`. The sibling key of the replaced list, and
     * the sibling section, keep what the earlier package gave them.
     */
    #[Test]
    public function aListIsReplacedAsAWhole(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'replacing']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(['disabled'], $merged['sections']['publications']['validators']);
        $this->assertSame('Publications', $merged['sections']['publications']['label']);
        $this->assertSame('Lectures', $merged['sections']['lectures']['label']);
    }

    /**
     * An empty sequence is a list as well, and replacing with it is how a project
     * clears the flags of a field. An empty YAML map parses to the same empty
     * array, so `{}` clears a map the same way.
     */
    #[Test]
    public function anEmptyListOrMapClearsTheEarlierValue(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'clearing']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame([], $merged['sections']['publications']['validators']);
        $this->assertSame('Publications', $merged['sections']['publications']['label']);
        $this->assertSame([], $merged['sections']['lectures']);
    }

    /**
     * `null` (`~` in YAML) removes the key, at the top level and at any depth. It is
     * the only way to drop an upstream entry once the merge is recursive: leaving the
     * entry out means "do not change it".
     */
    #[Test]
    public function aNullValueRemovesATopLevelAndANestedKey(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'removing']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(['sections'], array_keys($merged));
        $this->assertSame(['publications'], array_keys($merged['sections']));
    }

    /**
     * `null` means "not configured" whether or not an earlier package named the
     * key, so a file that is the only one to carry an entry drops it just as well.
     * Anything else would make the meaning of `~` depend on what is installed.
     */
    #[Test]
    public function aNullRemovesAKeyNoEarlierPackageConfigured(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['removing']),
        );

        $this->assertSame(['sections' => []], $subject->loadMergedArray('Configuration/Test/Settings.yaml'));
    }

    /**
     * Two values only merge when both are maps. A list against a map, a scalar
     * against a map - and any other pair of types - is a replacement, because
     * there is nothing to merge key by key.
     */
    #[Test]
    public function aValueOfAnotherTypeReplacesTheEarlierOne(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'retyping']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(['one', 'two'], $merged['sections']['publications']);
        $this->assertSame('a scalar', $merged['legacy']);
    }

    /**
     * And the same the other way round: a map from the later package replaces an
     * earlier list or scalar rather than being folded into it.
     */
    #[Test]
    public function aMapReplacesAnEarlierListOrScalar(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['retyping', 'upstream']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(
            ['label' => 'Publications', 'validators' => ['required', 'readonly']],
            $merged['sections']['publications'],
        );
        $this->assertSame(['flag' => true], $merged['legacy']);
    }

    /**
     * A YAML map whose keys happen to be `0 ... n-1` is a PHP list, so it is replaced
     * rather than merged. Nothing in the shipped settings files has that shape; the
     * documentation says so.
     */
    #[Test]
    public function aMapWithConsecutiveIntegerKeysCountsAsAList(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'replacing']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(['only'], $merged['sections']['lectures']['columns']);
    }

    /**
     * When the later map names every key of the earlier one, the later order is the
     * order of the result. That is what keeps a reordered full copy - the shape every
     * override had before the merge became recursive - rendering in its own order.
     */
    #[Test]
    public function aCompleteRestatementDecidesTheKeyOrder(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'reordering']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(['lectures', 'publications'], array_keys($merged['sections']));
        $this->assertSame('Lectures reordered', $merged['sections']['lectures']['label']);
    }

    /**
     * A partial map must not move the key it names to the front, so the earlier order
     * is kept and the keys only the later map names follow it.
     */
    #[Test]
    public function aPartialMapKeepsTheEarlierOrderAndAppendsItsNewKeys(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['upstream', 'extending']),
        );

        $merged = $subject->loadMergedArray('Configuration/Test/Settings.yaml');

        $this->assertSame(['publications', 'lectures', 'teaching'], array_keys($merged['sections']));
        $this->assertSame('Lectures changed', $merged['sections']['lectures']['label']);
        $this->assertSame(['first', 'second'], $merged['sections']['lectures']['columns']);
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

    /**
     * The per-package view is what the merge folds: one array per package that ships
     * the file, keyed by package key, in loading order, packages without the file or
     * with an empty one left out. A migration report needs the attribution the merged
     * array has lost.
     */
    #[Test]
    public function thePackageArraysAreKeyedByPackageInLoadingOrder(): void
    {
        $subject = new SettingsFileLoader(
            $this->cacheWithoutEntry(),
            $this->packageManager(['second', 'without', 'empty', 'first']),
        );

        $this->assertSame(
            [
                'second' => ['validations' => ['profile' => ['lastName' => ['readonly']]]],
                'first' => [
                    'validations' => ['profile' => ['firstName' => ['required']]],
                    'types' => ['first' => 1],
                ],
            ],
            $subject->loadPackageArrays('Configuration/Test/Settings.yaml'),
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
            $package->method('getPackageKey')->willReturn($fixturePackage);
            $packages[] = $package;
        }
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getActivePackages')->willReturn($packages);
        return $packageManager;
    }
}
