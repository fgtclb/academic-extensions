<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Every development instance requires the packages of this repository as `@dev`.
 *
 * The instances track their `composer.lock`, and `ddev start` runs `composer install` on it.
 * Composer refuses that install when a locked package no longer satisfies a requirement of
 * the root, and nothing refreshes the locks on a version bump: they need composer, the
 * network and the path repository plugin of the instance, while `bin/set-version` only
 * edits files. A pinned requirement such as `~3.1.0@dev` therefore broke the first start
 * after every release. `@dev` accepts whatever version a path package carries, and the
 * path packages are symlinked, so an instance runs the code of its checkout either way.
 */
final class DevelopmentInstanceRequirementTest extends TestCase
{
    /**
     * @return \Generator<string, array{string, string, string}>
     */
    public static function instanceRequirementsDataProvider(): \Generator
    {
        $repositoryPackages = self::repositoryPackageNames();
        $manifests = glob(self::repositoryPath() . '/core-*/composer.json') ?: [];
        sort($manifests);
        foreach ($manifests as $manifest) {
            $data = json_decode((string)file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
            foreach (['require', 'require-dev'] as $section) {
                $requirements = is_array($data) && is_array($data[$section] ?? null) ? $data[$section] : [];
                foreach ($requirements as $name => $constraint) {
                    if (in_array((string)$name, $repositoryPackages, true)) {
                        $label = basename(dirname($manifest)) . ' ' . $section . ' ' . $name;
                        yield $label => [basename(dirname($manifest)), (string)$name, (string)$constraint];
                    }
                }
            }
        }
    }

    #[DataProvider('instanceRequirementsDataProvider')]
    #[Test]
    public function instanceRequiresRepositoryPackageAsDev(string $instance, string $name, string $constraint): void
    {
        $this->assertSame(
            '@dev',
            $constraint,
            sprintf('%s requires %s as "%s". A version in it breaks "composer install" after the next version bump.', $instance, $name, $constraint),
        );
    }

    #[Test]
    public function everyInstanceRequiresARepositoryPackage(): void
    {
        $instances = [];
        foreach (self::instanceRequirementsDataProvider() as [$instance]) {
            $instances[$instance] = true;
        }
        $manifests = glob(self::repositoryPath() . '/core-*/composer.json') ?: [];
        $this->assertNotSame([], $manifests);
        $this->assertCount(count($manifests), $instances, 'An instance requires no package of this repository, so the check above covers nothing for it.');
    }

    /**
     * @return list<string>
     */
    private static function repositoryPackageNames(): array
    {
        $names = [];
        $manifests = array_merge(
            glob(self::repositoryPath() . '/packages/fgtclb/*/composer.json') ?: [],
            glob(self::repositoryPath() . '/packages-dev/*/composer.json') ?: [],
        );
        foreach ($manifests as $manifest) {
            $data = json_decode((string)file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data) && is_string($data['name'] ?? null)) {
                $names[] = $data['name'];
            }
        }
        return $names;
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
