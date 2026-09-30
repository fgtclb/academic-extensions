<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The branch alias of the root `composer.json` and of every split package is keyed to the
 * version name composer gives the branch the files live on.
 *
 * Composer applies an entry of `extra.branch-alias` only when its key is exactly that name,
 * `dev-main` for `main` and `2.x-dev` for `2`, and skips every other key without a word;
 * `composer validate --strict` accepts it as well. Branch `2` carried `dev-2` from its cut
 * until ACE-784, so `2.4.x-dev` never existed and no split package requiring a sibling with
 * `~2.4.0@dev` could be installed from the branch.
 *
 * - The root and the split packages carry the same alias object, with one entry: they are
 *   one version line, and `bin/set-version` writes all of them at once.
 * - Its key is what `Build/Scripts/composerBranchVersion.sh` prints for the branch
 *   {@see DocumentationGuidesTest::branch()} reads from the root.
 * - A numeric key aliases a version inside it (`2.x-dev` to `2.4.x-dev`). A reader that
 *   validates the package drops the whole branch for any other target.
 */
final class BranchAliasKeyTest extends TestCase
{
    /**
     * @return \Generator<string, array{string}>
     */
    public static function packageDirectoriesDataProvider(): \Generator
    {
        $directories = array_map('dirname', glob(self::repositoryPath() . '/packages/fgtclb/*/composer.json') ?: []);
        sort($directories);
        foreach ($directories as $directory) {
            yield basename($directory) => [$directory];
        }
    }

    #[DataProvider('packageDirectoriesDataProvider')]
    #[Test]
    public function packageCarriesTheBranchAliasOfTheRoot(string $directory): void
    {
        $this->assertSame(self::branchAlias(self::repositoryPath()), self::branchAlias($directory));
    }

    #[Test]
    public function rootBranchAliasHasOneEntry(): void
    {
        $this->assertCount(1, self::branchAlias(self::repositoryPath()));
    }

    #[Test]
    public function keyIsTheVersionNameComposerGivesTheBranch(): void
    {
        $key = (string)array_key_first(self::branchAlias(self::repositoryPath()));
        $output = [];
        $status = 0;
        exec(
            escapeshellarg(self::repositoryPath() . '/Build/Scripts/composerBranchVersion.sh') . ' '
            . escapeshellarg(DocumentationGuidesTest::branch()) . ' 2>&1',
            $output,
            $status,
        );
        $this->assertSame(0, $status, implode("\n", $output));
        $this->assertSame(implode("\n", $output), $key);
    }

    #[Test]
    public function targetIsAMinorVersionInsideANumericKey(): void
    {
        $alias = self::branchAlias(self::repositoryPath());
        $key = (string)array_key_first($alias);
        $target = (string)($alias[$key] ?? '');
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.x-dev$/', $target);
        if (!str_starts_with($key, 'dev-')) {
            $this->assertStringStartsWith(substr($key, 0, -strlen('x-dev')), $target);
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function branchAlias(string $directory): array
    {
        $manifest = json_decode((string)file_get_contents($directory . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $alias = is_array($manifest) ? ($manifest['extra']['branch-alias'] ?? []) : [];
        return is_array($alias) ? $alias : [];
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
