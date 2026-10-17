<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * "Build/Scripts/manualLinkVersion.php", which "bin/set-version" writes the manual version
 * of the links from package files into the manuals of the repository with.
 *
 * The links name the manual of the branch, and PackageManualLinkTest fails until they do.
 * Branch `2` was cut from `main` with links into the manuals of `main` (ACE-941), because
 * nothing wrote them. The script is run rather than included: it ends in
 * `exit(main($argv))`.
 */
final class ManualLinkVersionTest extends TestCase
{
    private const SETTINGS = <<<'YAML'
        # The validations are documented in
        # https://docs.typo3.org/p/fgtclb/example/main/en-us/Configuration/Validations/Index.html
        validations: []

        YAML;

    private const MANUAL_PAGE = <<<'RST'
        See the `route enhancers
        <https://docs.typo3.org/p/fgtclb/example-edit/main/en-us/Configuration/RouteEnhancers/Index.html#anchor>`__
        and the `manual of example <https://docs.typo3.org/p/fgtclb/example/main/en-us/>`__,
        not the one of `news <https://docs.typo3.org/p/georgringer/news/main/en-us/>`__,
        of a package with a longer name
        `<https://docs.typo3.org/p/fgtclb/example-other/main/en-us/>`__
        or of TYPO3 `<https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/>`__.

        RST;

    /**
     * The link table is the one of readmeManualLink.php, the text below it is written here.
     */
    private const README = <<<'MARKDOWN'
        # TYPO3 extension `Example`

        |                  | URL                                                 |
        |------------------|-----------------------------------------------------|
        | **Read online:** | https://docs.typo3.org/p/fgtclb/example/main/en-us/ |

        See the [Configuration](https://docs.typo3.org/p/fgtclb/example/main/en-us/Configuration/Index.html)
        chapter of the manual.

        MARKDOWN;

    /**
     * Files whose links are not written: they keep the link of `main` in every case.
     */
    private const LEFT_OUT = [
        'Documentation/Changelog/2.4/Feature-Example.rst' => "https://docs.typo3.org/p/fgtclb/example/main/en-us/\n",
        'Documentation/Upgrade/From23To24.rst' => "https://docs.typo3.org/p/fgtclb/example/main/en-us/\n",
        'Documentation-GENERATED-temp/Index.html' => "https://docs.typo3.org/p/fgtclb/example/main/en-us/\n",
        'Resources/Public/JavaScript/vendor/library.js' => "// https://docs.typo3.org/p/fgtclb/example/main/en-us/\n",
        'Resources/Public/Images/binary.png' => "\x89PNG\0https://docs.typo3.org/p/fgtclb/example/main/en-us/\n",
    ];

    private string $root;

    private string $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/manual-link-version-' . bin2hex(random_bytes(4));
        foreach (['example' => 'fgtclb/example', 'example-edit' => 'fgtclb/example-edit', 'other' => 'fgtclb/other'] as $directory => $name) {
            mkdir($this->root . '/' . $directory, 0777, true);
            file_put_contents($this->root . '/' . $directory . '/composer.json', sprintf('{"name": "%s"}', $name));
        }
        $this->package = $this->root . '/example';
        $this->writeFile('Configuration/Settings.yaml', self::SETTINGS);
        $this->writeFile('Documentation/Configuration/Index.rst', self::MANUAL_PAGE);
        $this->writeFile('README.md', self::README);
        foreach (self::LEFT_OUT as $path => $content) {
            $this->writeFile($path, $content);
        }
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->root);
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{string, string, string}>
     */
    public static function branchesDataProvider(): \Generator
    {
        yield 'cut of a major version branch' => ['3', '3.0.0', '3.0'];
        yield 'minor bump on a major version branch' => ['2', '2.5.0', '2.5'];
        yield 'release on a minor version branch' => ['2.4', '2.4.1', '2.4'];
    }

    #[DataProvider('branchesDataProvider')]
    #[Test]
    public function linksTheManualsOfTheVersionLine(string $branch, string $version, string $manual): void
    {
        [$status, $output] = $this->execute($branch, $version);

        $this->assertSame(0, $status, $output);
        $this->assertSame(
            str_replace('/example/main/', '/example/' . $manual . '/', self::SETTINGS),
            $this->readFile('Configuration/Settings.yaml'),
        );
        $this->assertSame(
            str_replace(
                ['/example-edit/main/', '/example/main/'],
                ['/example-edit/' . $manual . '/', '/example/' . $manual . '/'],
                self::MANUAL_PAGE,
            ),
            $this->readFile('Documentation/Configuration/Index.rst'),
        );
        $this->assertSame(
            str_replace(
                '/example/main/en-us/Configuration/',
                '/example/' . $manual . '/en-us/Configuration/',
                self::README,
            ),
            $this->readFile('README.md'),
        );
        $this->assertLeftOutFilesAreUnchanged();
    }

    #[Test]
    public function linksWrittenInCapitalsToo(): void
    {
        $this->writeFile('Configuration/Settings.yaml', "# HTTPS://DOCS.TYPO3.ORG/P/FGTCLB/EXAMPLE/MAIN/EN-US/INDEX.HTML\n");

        [$status, $output] = $this->execute('2', '2.5.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame(
            "# HTTPS://DOCS.TYPO3.ORG/P/FGTCLB/EXAMPLE/2.5/EN-US/INDEX.HTML\n",
            $this->readFile('Configuration/Settings.yaml'),
        );
    }

    #[Test]
    public function linksTheManualsOfMainOnMain(): void
    {
        $search = ['/example/main/', '/example-edit/main/'];
        $replace = ['/example/2.4/', '/example-edit/2.4/'];
        $this->writeFile('Configuration/Settings.yaml', str_replace($search, $replace, self::SETTINGS));
        $this->writeFile('Documentation/Configuration/Index.rst', str_replace($search, $replace, self::MANUAL_PAGE));
        $this->writeFile('README.md', str_replace($search, $replace, self::README));

        [$status, $output] = $this->execute('main', '3.0.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame(self::SETTINGS, $this->readFile('Configuration/Settings.yaml'));
        $this->assertSame(self::MANUAL_PAGE, $this->readFile('Documentation/Configuration/Index.rst'));
        $this->assertSame(
            str_replace(
                '| https://docs.typo3.org/p/fgtclb/example/main/en-us/ |',
                '| https://docs.typo3.org/p/fgtclb/example/2.4/en-us/ |',
                self::README,
            ),
            $this->readFile('README.md'),
            'The link table is left to readmeManualLink.php.',
        );
    }

    #[Test]
    public function leavesFilesThatLinkTheManualsUntouched(): void
    {
        touch($this->package . '/Configuration/Settings.yaml', 1000000000);
        touch($this->package . '/Documentation/Configuration/Index.rst', 1000000000);
        touch($this->package . '/README.md', 1000000000);

        [$status, $output] = $this->execute('main', '3.0.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame($this->package . ': manual links unchanged', $output);
        clearstatcache();
        $this->assertSame(1000000000, filemtime($this->package . '/Configuration/Settings.yaml'));
        $this->assertSame(1000000000, filemtime($this->package . '/Documentation/Configuration/Index.rst'));
        $this->assertSame(1000000000, filemtime($this->package . '/README.md'));
    }

    #[Test]
    public function keepsWindowsLineEndings(): void
    {
        $this->writeFile('Configuration/Settings.yaml', str_replace("\n", "\r\n", self::SETTINGS));

        [$status, $output] = $this->execute('2', '2.5.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame(
            str_replace(["\n", '/main/'], ["\r\n", '/2.5/'], self::SETTINGS),
            $this->readFile('Configuration/Settings.yaml'),
        );
    }

    #[Test]
    public function checkReportsEveryFileItWouldChangeAndChangesNothing(): void
    {
        [$status, $output] = $this->execute('2', '2.5.0', true);

        $this->assertSame(1, $status, $output);
        $this->assertSame(
            implode("\n", [
                $this->package . '/Configuration/Settings.yaml: links a manual other than 2.5',
                $this->package . '/Documentation/Configuration/Index.rst: links a manual other than 2.5',
                $this->package . '/README.md: links a manual other than 2.5',
            ]),
            $output,
        );
        $this->assertSame(self::SETTINGS, $this->readFile('Configuration/Settings.yaml'));
        $this->assertSame(self::MANUAL_PAGE, $this->readFile('Documentation/Configuration/Index.rst'));
        $this->assertSame(self::README, $this->readFile('README.md'));
    }

    #[Test]
    public function checkPassesFilesThatLinkTheManuals(): void
    {
        [$status, $output] = $this->execute('main', '3.0.0', true);

        $this->assertSame(0, $status, $output);
    }

    #[Test]
    public function failsForALinkWithoutAManualVersionBeforeWritingAnything(): void
    {
        $this->writeFile('Documentation/Index.rst', "Intro\n\nhttps://docs.typo3.org/p/fgtclb/other/en-us/Index.html\n");

        [$status, $output] = $this->execute('2', '2.5.0');

        $this->assertSame(1, $status, $output);
        $this->assertSame(
            $this->package . '/Documentation/Index.rst: line 3: "https://docs.typo3.org/p/fgtclb/other/" names no manual version and locale',
            $output,
        );
        $this->assertSame(self::SETTINGS, $this->readFile('Configuration/Settings.yaml'));
        $this->assertSame(self::MANUAL_PAGE, $this->readFile('Documentation/Configuration/Index.rst'));
    }

    #[Test]
    public function failsForAPackageDirectoryThatCannotBeReadBeforeWritingAnything(): void
    {
        $arguments = [
            PHP_BINARY,
            dirname(__DIR__, 5) . '/Build/Scripts/manualLinkVersion.php',
            '2',
            '2.5.0',
            $this->package,
            $this->root . '/missing',
        ];
        $output = [];
        $status = 0;
        exec(implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1', $output, $status);

        $this->assertSame(1, $status, implode("\n", $output));
        $this->assertSame($this->root . '/missing: cannot be read', implode("\n", $output));
        $this->assertSame(self::SETTINGS, $this->readFile('Configuration/Settings.yaml'));
    }

    #[Test]
    public function failsForAVersionBranchWithoutAVersion(): void
    {
        [$status, $output] = $this->execute('2', '2.5');

        $this->assertSame(1, $status, $output);
        $this->assertSame('No manual version for branch "2" and version "2.5".', $output);
    }

    private function assertLeftOutFilesAreUnchanged(): void
    {
        foreach (self::LEFT_OUT as $path => $content) {
            $this->assertSame($content, $this->readFile($path), $path);
        }
    }

    private function writeFile(string $path, string $content): void
    {
        $file = $this->package . '/' . $path;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $content);
    }

    private function readFile(string $path): string
    {
        return (string)file_get_contents($this->package . '/' . $path);
    }

    /**
     * @return array{int, string}
     */
    private function execute(string $branch, string $version, bool $check = false): array
    {
        $arguments = [PHP_BINARY, dirname(__DIR__, 5) . '/Build/Scripts/manualLinkVersion.php'];
        if ($check) {
            $arguments[] = '--check';
        }
        array_push($arguments, $branch, $version, $this->package);
        $output = [];
        $status = 0;
        exec(implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1', $output, $status);
        return [$status, implode("\n", $output)];
    }
}
