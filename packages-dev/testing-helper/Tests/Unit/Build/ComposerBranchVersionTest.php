<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * "Build/Scripts/composerBranchVersion.sh" prints the version name composer gives a git
 * branch, the only key of "extra.branch-alias" composer applies on that branch.
 *
 * "bin/set-version" wrote "dev-<branch>" for every branch until ACE-784. That is the name
 * of "main", but composer names a branch "2" "2.x-dev", ignored the key "dev-2", and no
 * split package requiring a sibling with "~2.4.0@dev" could be installed from branch "2".
 * The expected names below are what composer 2.10.1 derives for these branches.
 */
final class ComposerBranchVersionTest extends TestCase
{
    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function branchNamesDataProvider(): \Generator
    {
        yield 'default branch' => ['main', 'dev-main'];
        yield 'feature branch' => ['feature/foo', 'dev-feature/foo'];
        yield 'name starting with a number' => ['2-legacy', 'dev-2-legacy'];
        yield 'release branch of bin/release' => ['release-2.4.0', 'dev-release-2.4.0'];
        yield 'hash in the name' => ['a#b', 'dev-a+b'];
        yield 'major' => ['2', '2.x-dev'];
        yield 'two digit major' => ['10', '10.x-dev'];
        yield 'major with x' => ['2.x', '2.x-dev'];
        yield 'major with upper case x' => ['2.X', '2.x-dev'];
        yield 'major with star' => ['2.*', '2.x-dev'];
        yield 'minor' => ['2.2', '2.2.x-dev'];
        yield 'minor with x' => ['2.2.x', '2.2.x-dev'];
        yield 'patch' => ['2.2.3', '2.2.3.x-dev'];
    }

    #[DataProvider('branchNamesDataProvider')]
    #[Test]
    public function printsTheVersionNameComposerGivesTheBranch(string $branch, string $expected): void
    {
        [$status, $output] = $this->execute($branch);
        $this->assertSame(0, $status, $output);
        $this->assertSame($expected, $output);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function refusedBranchNamesDataProvider(): \Generator
    {
        // Composer names these "v3.x-dev" in a repository and "3.x-dev" as the root package.
        yield 'v prefix' => ['v3'];
        yield 'upper case v prefix' => ['V3'];
        yield 'v prefix with minor' => ['v3.1'];
        // Numeric to composer, no version branch shape.
        yield 'four parts' => ['2.2.3.4'];
        yield 'x before a number' => ['2.x.3'];
        yield 'two x' => ['2.x.x'];
    }

    #[DataProvider('refusedBranchNamesDataProvider')]
    #[Test]
    public function refusesABranchNameWithoutOneVersionName(string $branch): void
    {
        [$status, $output] = $this->execute($branch);
        $this->assertSame(1, $status, $output);
        $this->assertSame("Branch '{$branch}' is numeric to composer, but not of the form N, N.M, N.M.P, N.x or N.M.x.", $output);
    }

    #[Test]
    public function refusesAnEmptyBranchName(): void
    {
        [$status, $output] = $this->execute('');
        $this->assertSame(1, $status, $output);
        $this->assertStringStartsWith('Usage: ', $output);
    }

    /**
     * @return array{int, string}
     */
    private function execute(string $branch): array
    {
        $script = dirname(__DIR__, 5) . '/Build/Scripts/composerBranchVersion.sh';
        $output = [];
        $status = 0;
        exec(escapeshellarg($script) . ' ' . escapeshellarg($branch) . ' 2>&1', $output, $status);
        return [$status, implode("\n", $output)];
    }
}
