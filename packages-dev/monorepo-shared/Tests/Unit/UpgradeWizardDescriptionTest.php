<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsMonorepoShared\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Every upgrade wizard of this repository has a title and a description.
 *
 * The upgrade module lists a wizard with both, and the description is the only place an
 * administrator learns what a wizard is going to write before running it. Eight of the
 * seventeen wizards of this branch returned an empty one until ACE-871.
 *
 * Title and description are constant texts in every wizard, so the class is created
 * without its constructor and its dependencies.
 */
final class UpgradeWizardDescriptionTest extends TestCase
{
    /**
     * @return \Generator<string, array{class-string<UpgradeWizardInterface>}>
     */
    public static function upgradeWizardDataProvider(): \Generator
    {
        $files = glob(self::repositoryPath() . '/packages/fgtclb/*/Classes/Upgrades/*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $content = (string)file_get_contents($file);
            if (!str_contains($content, '#[UpgradeWizard(')
                || preg_match('/^namespace\s+([^;]+);/m', $content, $namespace) !== 1
            ) {
                continue;
            }
            $className = $namespace[1] . '\\' . basename($file, '.php');
            yield $className => [$className];
        }
    }

    #[Test]
    public function upgradeWizardsAreFound(): void
    {
        $this->assertGreaterThanOrEqual(17, iterator_count(self::upgradeWizardDataProvider()));
    }

    /**
     * @param class-string<UpgradeWizardInterface> $className
     */
    #[DataProvider('upgradeWizardDataProvider')]
    #[Test]
    public function upgradeWizardHasATitleAndADescription(string $className): void
    {
        $wizard = (new \ReflectionClass($className))->newInstanceWithoutConstructor();
        $this->assertInstanceOf(UpgradeWizardInterface::class, $wizard);

        $this->assertNotSame('', trim($wizard->getTitle()), 'title');
        $this->assertNotSame('', trim($wizard->getDescription()), 'description');
    }

    private static function repositoryPath(): string
    {
        return dirname(__DIR__, 4);
    }
}
