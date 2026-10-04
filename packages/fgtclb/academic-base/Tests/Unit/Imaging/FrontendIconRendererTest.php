<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Imaging;

use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The size and identifier rules, and the version token. The token is built from the
 * frontend icon registry only, so these tests hand the renderer a registry whose cache
 * entry they write themselves - the cached registry cannot be changed at runtime - and
 * source files below `typo3temp/var/tests/` whose modification time they set.
 */
final class FrontendIconRendererTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{string, IconSize|null}>
     */
    public static function sizes(): \Generator
    {
        yield 'default' => ['default', IconSize::DEFAULT];
        yield 'small' => ['small', IconSize::SMALL];
        yield 'medium' => ['medium', IconSize::MEDIUM];
        yield 'large' => ['large', IconSize::LARGE];
        yield 'mega' => ['mega', IconSize::MEGA];
        yield 'overlay, the size of an overlay icon' => ['overlay', null];
        yield 'upper case' => ['SMALL', null];
        yield 'empty' => ['', null];
        yield 'a number' => ['16', null];
    }

    #[DataProvider('sizes')]
    #[Test]
    public function sizeFromAcceptsTheSizesOfAStandaloneIcon(string $value, ?IconSize $expected): void
    {
        $this->assertSame($expected, FrontendIconRenderer::sizeFrom($value));
    }

    /**
     * @return \Generator<string, array{string, bool}>
     */
    public static function identifiers(): \Generator
    {
        yield 'a shared icon' => ['tx-academicbase-action-add', true];
        yield 'a category type icon' => ['category_types.group.type', true];
        yield 'a category group icon' => ['category_types_group.group', true];
        yield 'one character' => ['a', true];
        yield '100 characters' => [str_repeat('a', 100), true];
        yield '101 characters' => [str_repeat('a', 101), false];
        yield 'empty' => ['', false];
        yield 'upper case' => ['Tx-academicbase-action-add', false];
        yield 'a leading dot' => ['.tx-academicbase-action-add', false];
        yield 'a comma' => ['tx-academicbase-action-add,tx-academicbase-action-edit', false];
        yield 'a space' => ['tx-academicbase action-add', false];
        yield 'a slash' => ['tx-academicbase/action-add', false];
        yield 'a trailing line feed' => ["tx-academicbase-action-add\n", false];
        yield '100 characters and a trailing line feed' => [str_repeat('a', 100) . "\n", false];
        yield 'a leading line feed' => ["\ntx-academicbase-action-add", false];
    }

    /**
     * `$` of a PCRE pattern also matches before a final line feed. The pattern has to
     * refuse one all the same.
     */
    #[DataProvider('identifiers')]
    #[Test]
    public function identifierPatternAcceptsExactlyAnIdentifier(string $identifier, bool $expected): void
    {
        $this->assertSame($expected, preg_match(FrontendIconRenderer::IDENTIFIER_PATTERN, $identifier) === 1);
    }

    #[Test]
    public function theVersionIsAStableHash(): void
    {
        $icons = ['tx-test-a' => $this->icon($this->file('a.svg'))];

        $version = $this->renderer($icons)->getVersion();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $version);
        $this->assertSame($version, $this->renderer($icons)->getVersion());
    }

    #[Test]
    public function theVersionChangesWhenARegistrationIsReplaced(): void
    {
        $before = $this->renderer(['tx-test-a' => $this->icon($this->file('a.svg'))])->getVersion();

        $after = $this->renderer(['tx-test-a' => $this->icon($this->file('replacement.svg'))])->getVersion();

        $this->assertNotSame($before, $after);
    }

    #[Test]
    public function theVersionChangesWhenAnIconIsAdded(): void
    {
        $icons = ['tx-test-a' => $this->icon($this->file('a.svg'))];
        $before = $this->renderer($icons)->getVersion();

        $after = $this->renderer($icons + ['tx-test-b' => $this->icon($this->file('b.svg'))])->getVersion();

        $this->assertNotSame($before, $after);
    }

    #[Test]
    public function theVersionChangesWhenASourceFileChangesOnDisk(): void
    {
        $path = $this->file('on-disk.svg');
        $icons = ['tx-test-a' => $this->icon($path)];
        $before = $this->renderer($icons)->getVersion();

        touch($path, 1700000100);
        clearstatcache(true, $path);

        $this->assertNotSame($before, $this->renderer($icons)->getVersion());
    }

    /**
     * The registrations and the files stay the same, and only the code that turns a file
     * into markup changes, in place, without a new `composer.lock`. Both the provider
     * class and a class it extends count. The two classes are written for this test, so
     * their files can be touched.
     */
    #[Test]
    public function theVersionChangesWhenTheFileOfAProviderOrItsParentChanges(): void
    {
        [$provider, $parentFile, $providerFile] = $this->writeProviderClasses();
        $icons = ['tx-test-a' => $this->icon($this->file('a.svg'), $provider)];
        $before = $this->renderer($icons)->getVersion();

        touch($parentFile, 1700000100);
        clearstatcache(true, $parentFile);
        $afterParent = $this->renderer($icons)->getVersion();
        touch($providerFile, 1700000100);
        clearstatcache(true, $providerFile);

        $this->assertNotSame($before, $afterParent);
        $this->assertNotSame($afterParent, $this->renderer($icons)->getVersion());
    }

    /**
     * The package dependent cache identifier changes with every update of TYPO3 and, in
     * composer mode, with `composer.lock`, and so with an update that changes a provider
     * or the sanitiser but no registration and no source file.
     */
    #[Test]
    public function theVersionChangesWithThePackageDependentCacheIdentifier(): void
    {
        $icons = ['tx-test-a' => $this->icon($this->file('a.svg'))];

        $this->assertNotSame(
            $this->renderer($icons, 'one deployment')->getVersion(),
            $this->renderer($icons, 'another deployment')->getVersion(),
        );
    }

    /**
     * @return \Generator<string, array{string, array{provider: class-string, options: array<string, mixed>}}>
     */
    public static function iconsThatAreNotServed(): \Generator
    {
        yield 'a bitmap icon' => ['tx-test-bitmap', ['provider' => BitmapIconProvider::class, 'options' => ['source' => 'EXT:test/bitmap.png']]];
        yield 'the placeholder' => ['default-not-found', ['provider' => SvgIconProvider::class, 'options' => ['source' => 'EXT:test/other.svg']]];
        yield 'an identifier that is not one' => ['Tx-Test-Upper', ['provider' => SvgIconProvider::class, 'options' => ['source' => 'EXT:test/other.svg']]];
    }

    /**
     * @param array{provider: class-string, options: array<string, mixed>} $configuration
     */
    #[DataProvider('iconsThatAreNotServed')]
    #[Test]
    public function theVersionIgnoresAnIconThatIsNotServed(string $identifier, array $configuration): void
    {
        $icons = ['tx-test-a' => $this->icon($this->file('a.svg'))];
        $before = $this->renderer($icons)->getVersion();

        $after = $this->renderer($icons + [$identifier => $configuration])->getVersion();

        $this->assertSame($before, $after);
    }

    /**
     * The order of the packages decides the order of the registry, and that changes no
     * markup.
     */
    #[Test]
    public function theVersionIgnoresTheOrderOfTheRegistry(): void
    {
        $a = $this->icon($this->file('a.svg'));
        $b = $this->icon($this->file('b.svg'));

        $this->assertSame(
            $this->renderer(['tx-test-a' => $a, 'tx-test-b' => $b])->getVersion(),
            $this->renderer(['tx-test-b' => $b, 'tx-test-a' => $a])->getVersion(),
        );
    }

    /**
     * @param array<string, array{provider: class-string, options: array<string, mixed>}> $icons The cache entry of the registry
     */
    private function renderer(array $icons, string $packageCacheIdentifier = 'packages'): FrontendIconRenderer
    {
        $packageManager = $this->createMock(PackageManager::class);
        $packageManager->method('getCacheIdentifier')->willReturn($packageCacheIdentifier);
        $packageDependentCacheIdentifier = new PackageDependentCacheIdentifier($packageManager);
        $cache = $this->createMock(PhpFrontend::class);
        $cache->method('require')->willReturn($icons);
        $registry = new FrontendIconRegistry(
            $cache,
            $this->createMock(PackageManager::class),
            $this->createMock(EventDispatcherInterface::class),
            $packageDependentCacheIdentifier,
        );

        return new FrontendIconRenderer(
            new FrontendIconFactory($registry, $this->createMock(ContainerInterface::class), $this->createMock(FrontendInterface::class)),
            $registry,
            $packageDependentCacheIdentifier,
        );
    }

    /**
     * @param class-string $provider
     * @return array{provider: class-string, options: array<string, mixed>}
     */
    private function icon(string $source, string $provider = CurrentColorSvgIconProvider::class): array
    {
        return ['provider' => $provider, 'options' => ['source' => $source]];
    }

    /**
     * A file below `typo3temp/var/tests/` with a fixed modification time, deleted after
     * the test.
     */
    private function file(string $name): string
    {
        return $this->writeFile($name, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M0 0h1v1H0z"/></svg>');
    }

    /**
     * Two classes in files of their own, a provider and the class it extends, with a
     * name unique to the test, since a class can be declared once per process.
     *
     * @return array{class-string, string, string}
     */
    private function writeProviderClasses(): array
    {
        $suffix = bin2hex(random_bytes(6));
        $namespace = 'FGTCLB\\AcademicBase\\Tests\\Unit\\Imaging\\Generated';
        $parentFile = $this->writeFile(
            'TokenParentProvider' . $suffix . '.php',
            '<?php namespace ' . $namespace . '; abstract class TokenParentProvider' . $suffix
                . ' extends \\' . SvgIconProvider::class . ' {}',
        );
        $providerFile = $this->writeFile(
            'TokenProvider' . $suffix . '.php',
            '<?php namespace ' . $namespace . '; final class TokenProvider' . $suffix
                . ' extends TokenParentProvider' . $suffix . ' {}',
        );
        require $parentFile;
        require $providerFile;
        /** @var class-string $provider */
        $provider = $namespace . '\\TokenProvider' . $suffix;

        return [$provider, $parentFile, $providerFile];
    }

    private function writeFile(string $name, string $content): string
    {
        $path = Environment::getPublicPath() . '/typo3temp/var/tests/frontend-icon-renderer-' . $name;
        file_put_contents($path, $content);
        touch($path, 1700000000);
        clearstatcache(true, $path);
        $this->testFilesToDelete[] = $path;

        return $path;
    }
}
