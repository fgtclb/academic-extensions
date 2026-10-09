<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional\Support;

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * The site configurations of the development instance of the running core
 * version, written into the test instance.
 *
 * The `/` site of an instance is themed by EXT:bootstrap_package, which the
 * test instances do not load. It is written with the theme swapped for the page
 * object set of this package and with everything else as committed: what the
 * site set driven tree is configured with has to be what the instance is
 * configured with, or a test would prove something about a configuration nobody
 * runs.
 */
trait CommittedSiteTrait
{
    /**
     * The `/` site, `academics`, with its committed settings.
     *
     * @return array<string, mixed> The configuration as written.
     */
    protected function writeAcademicsSite(string $base): array
    {
        $academics = $this->committedSite($this->instanceSitesDirectory() . '/academics/config.yaml');
        $academics['base'] = $base;
        $academics['dependencies'] = array_map(
            static fn(string $set): string => $set === 'bootstrap-package/full'
                ? 'fgtclb/academics-dev-site-page-object'
                : $set,
            $academics['dependencies'] ?? [],
        );
        $this->writeSite('academics', $academics, $this->instanceSitesDirectory() . '/academics/settings.yaml');

        return $academics;
    }

    protected function instanceSitesDirectory(): string
    {
        return sprintf('%s/core-%d/config/sites', dirname(__DIR__, 5), (new Typo3Version())->getMajorVersion());
    }

    /**
     * @return array<string, mixed>
     */
    protected function committedSite(string $file): array
    {
        $this->assertFileExists($file);
        /** @var array<string, mixed> $configuration */
        $configuration = Yaml::parseFile($file);

        // Taken as it stands, including its "imports" of the route enhancers and
        // its "fallbackType: strict". The language bases are relative in the
        // committed file already, so only the site base has to be rewritten, and
        // the caller does that.
        return $configuration;
    }

    /**
     * Written through `SiteWriter` rather than into a directory of the test's
     * choosing: the writer knows where the installation reads site
     * configurations from and flushes the caches that would otherwise answer
     * with the site list of a moment ago.
     *
     * @param array<string, mixed> $configuration
     */
    protected function writeSite(string $identifier, array $configuration, ?string $settingsFile): void
    {
        $writer = $this->get(SiteWriter::class);
        $writer->write($identifier, $configuration);

        if ($settingsFile !== null && is_file($settingsFile)) {
            /** @var array<string, mixed> $settings */
            $settings = Yaml::parseFile($settingsFile);
            $writer->writeSettings($identifier, $settings);
        }
    }
}
