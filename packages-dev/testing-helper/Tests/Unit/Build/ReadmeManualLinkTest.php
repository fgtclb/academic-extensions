<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * "Build/Scripts/readmeManualLink.php", which "bin/set-version" writes the "Read online"
 * link of every package README with.
 *
 * The link names the manual of the branch, and ReadmeLinkTableTest fails until it does.
 * Before ACE-939 nothing wrote it, so cutting a version branch or bumping the minor
 * version of one failed the checks of the pull request "bin/cut-branch" or "bin/release"
 * waits for. The script is run rather than included: it ends in `exit(main($argv))`.
 */
final class ReadmeManualLinkTest extends TestCase
{
    private const README = <<<'MARKDOWN'
        [![Badge](https://example.org/badge.svg)](https://example.org/)

        # TYPO3 extension `Example` (READ-ONLY)

        |                  | URL                                                         |
        |------------------|-------------------------------------------------------------|
        | **Repository:**  | https://github.com/fgtclb/typo3-example                     |
        | **Read online:** | https://docs.typo3.org/p/fgtclb/example-package/main/en-us/ |
        | **TER:**         | https://extensions.typo3.org/extension/example/             |

        ## Description

        | Not | the link table |
        |-----|----------------|

        MARKDOWN;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/readme-manual-link-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
        file_put_contents($this->directory . '/composer.json', '{"name": "fgtclb/example-package"}');
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
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
    public function linksTheManualOfTheVersionLineAndPadsTheTable(string $branch, string $version, string $manual): void
    {
        $this->writeReadme(self::README);

        [$status, $output] = $this->execute($branch, $version);

        $this->assertSame(0, $status, $output);
        $this->assertSame(
            str_replace(
                [
                    '|                  | URL                                                         |',
                    '|------------------|-------------------------------------------------------------|',
                    '| **Repository:**  | https://github.com/fgtclb/typo3-example                     |',
                    '| **Read online:** | https://docs.typo3.org/p/fgtclb/example-package/main/en-us/ |',
                    '| **TER:**         | https://extensions.typo3.org/extension/example/             |',
                ],
                [
                    '|                  | URL                                                        |',
                    '|------------------|------------------------------------------------------------|',
                    '| **Repository:**  | https://github.com/fgtclb/typo3-example                    |',
                    '| **Read online:** | https://docs.typo3.org/p/fgtclb/example-package/' . $manual . '/en-us/ |',
                    '| **TER:**         | https://extensions.typo3.org/extension/example/            |',
                ],
                self::README,
            ),
            $this->readme(),
        );
    }

    #[Test]
    public function linksTheManualOfMainOnMain(): void
    {
        $this->writeReadme(str_replace('/main/en-us/ |', '/2.4/en-us/  |', self::README));

        [$status, $output] = $this->execute('main', '3.0.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame(self::README, $this->readme());
    }

    #[Test]
    public function leavesAReadmeThatLinksTheManualUntouched(): void
    {
        $this->writeReadme(self::README);
        touch($this->directory . '/README.md', 1000000000);

        [$status, $output] = $this->execute('main', '3.0.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame(self::README, $this->readme());
        clearstatcache();
        $this->assertSame(1000000000, filemtime($this->directory . '/README.md'));
    }

    #[Test]
    public function leavesAManualThatIsNotRenderedAlone(): void
    {
        $readme = str_replace(
            '| **Read online:** | https://docs.typo3.org/p/fgtclb/example-package/main/en-us/ |',
            '| **Read online:** | -                                                           |',
            self::README,
        );
        $this->writeReadme($readme);

        [$status, $output] = $this->execute('2', '2.5.0');

        $this->assertSame(0, $status, $output);
        $this->assertSame($readme, $this->readme());
    }

    #[Test]
    public function keepsWindowsLineEndings(): void
    {
        $this->writeReadme(str_replace("\n", "\r\n", self::README));

        [$status, $output] = $this->execute('2', '2.5.0');

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString("| **Read online:** | https://docs.typo3.org/p/fgtclb/example-package/2.5/en-us/ |\r\n", $this->readme());
        $this->assertStringNotContainsString("\n", str_replace("\r\n", '', $this->readme()));
    }

    #[Test]
    public function checkReportsAReadmeItWouldChangeAndChangesNothing(): void
    {
        $this->writeReadme(self::README);

        [$status, $output] = $this->execute('2', '2.5.0', true);

        $this->assertSame(1, $status, $output);
        $this->assertStringContainsString('Read online is not the manual of 2.5', $output);
        $this->assertSame(self::README, $this->readme());
    }

    #[Test]
    public function checkPassesAReadmeThatLinksTheManual(): void
    {
        $this->writeReadme(self::README);

        [$status, $output] = $this->execute('main', '3.0.0', true);

        $this->assertSame(0, $status, $output);
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function brokenReadmesDataProvider(): \Generator
    {
        yield 'no table' => [
            "# TYPO3 extension `Example`\n\n## Description\n",
            'no link table one blank line below the title',
        ];
        yield 'no blank line below the title' => [
            str_replace("(READ-ONLY)\n\n", "(READ-ONLY)\n", self::README),
            'no link table one blank line below the title',
        ];
        yield 'no Read online row' => [
            (string)preg_replace('/^\| \*\*Read online:\*\*.*\n/m', '', self::README),
            'no "**Read online:**" row in the link table',
        ];
        yield 'Read online row with three cells' => [
            str_replace('/main/en-us/ |', '/main/en-us/ | extra |', self::README),
            'the "**Read online:**" row has 3 cells, not 2',
        ];
    }

    #[DataProvider('brokenReadmesDataProvider')]
    #[Test]
    public function failsForAReadmeWithoutTheLinkTable(string $readme, string $message): void
    {
        $this->writeReadme($readme);

        [$status, $output] = $this->execute('2', '2.5.0');

        $this->assertSame(1, $status, $output);
        $this->assertSame($this->directory . '/README.md: ' . $message, $output);
        $this->assertSame($readme, $this->readme());
    }

    #[Test]
    public function failsForAVersionBranchWithoutAVersion(): void
    {
        $this->writeReadme(self::README);

        [$status, $output] = $this->execute('2', '2.5');

        $this->assertSame(1, $status, $output);
        $this->assertSame('No manual version for branch "2" and version "2.5".', $output);
    }

    private function writeReadme(string $content): void
    {
        file_put_contents($this->directory . '/README.md', $content);
    }

    private function readme(): string
    {
        return (string)file_get_contents($this->directory . '/README.md');
    }

    /**
     * @return array{int, string}
     */
    private function execute(string $branch, string $version, bool $check = false): array
    {
        $arguments = [PHP_BINARY, dirname(__DIR__, 5) . '/Build/Scripts/readmeManualLink.php'];
        if ($check) {
            $arguments[] = '--check';
        }
        array_push($arguments, $branch, $version, $this->directory);
        $output = [];
        $status = 0;
        exec(implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1', $output, $status);
        return [$status, implode("\n", $output)];
    }
}
