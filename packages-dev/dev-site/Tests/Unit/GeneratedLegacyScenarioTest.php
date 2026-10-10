<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * The committed `ScenarioLegacy.yaml` is what `Scenario.yaml` produces today.
 *
 * The `/legacy/` tree is a mirror, and a mirror maintained by hand is a mirror
 * that drifts. It is generated instead - and a generated file that is committed
 * has the same problem one level up: somebody edits `Scenario.yaml`, does not
 * re-run the script, and the two trees quietly stop being the same tree.
 *
 * `LegacyDeliveryTest` sees part of that, because a page whose title changed in
 * one tree and not in the other renders differently. It does not see a page
 * *added* to `Scenario.yaml`, which simply has no counterpart and is skipped.
 * This does, and it costs a subprocess.
 *
 * The script is run rather than included: it ends in `exit(main($argv))`, which
 * is right for a script and unusable from a test.
 */
final class GeneratedLegacyScenarioTest extends TestCase
{
    #[Test]
    public function theCommittedMirrorIsUpToDate(): void
    {
        $script = dirname(__DIR__, 4) . '/Build/Scripts/generateLegacyScenario.php';
        $this->assertFileExists($script);

        $output = [];
        $status = 0;
        exec(
            sprintf('%s %s --check 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($script)),
            $output,
            $status,
        );

        $this->assertSame(
            0,
            $status,
            sprintf(
                "\"ScenarioLegacy.yaml\" is not what \"Scenario.yaml\" produces:\n  %s\n\n"
                . 'Run "php Build/Scripts/generateLegacyScenario.php" and commit the result. The mirror is'
                . ' generated, so an edit to it is lost on the next run and an edit to "Scenario.yaml" that'
                . ' does not reach it makes the two trees different trees.',
                implode("\n  ", $output),
            ),
        );
    }

    /**
     * An "Insert records" element of the mirror shows a content element of the mirror.
     *
     * Its `records` field names the elements it shows. Were it copied as it is, the
     * `/legacy/` copy would render a content element of the `/` tree, which
     * `LegacyDeliveryTest` does not notice: both trees render the same markup then.
     */
    #[Test]
    public function insertRecordsElementsOfTheMirrorShowTheMirror(): void
    {
        $directory = dirname(__DIR__, 2) . '/Configuration/DataFactory/academics-instance/';
        $this->assertFileExists($directory . 'Scenario.yaml');
        $this->assertFileExists($directory . 'ScenarioLegacy.yaml');

        $source = [];
        $this->collectRecordReferences(Yaml::parseFile($directory . 'Scenario.yaml'), $source);
        $mirror = [];
        $this->collectRecordReferences(Yaml::parseFile($directory . 'ScenarioLegacy.yaml'), $mirror);
        $this->assertNotSame([], $source, 'The seed has no "Insert records" element to check');

        // The mirror keeps the order of the "/" tree, and a content element of the
        // mirror is its original plus 1000.
        $expected = array_map(
            static fn(string $reference): string => 'tt_content_' . ((int)preg_replace('/^tt_content_/', '', $reference) + 1000),
            $source,
        );
        $this->assertSame(
            $expected,
            $mirror,
            'An "Insert records" element of the mirror does not show the mirror of the record its original shows',
        );
    }

    /**
     * @param list<string> $references
     */
    private function collectRecordReferences(mixed $node, array &$references): void
    {
        if (!is_array($node)) {
            return;
        }
        $self = $node['self'] ?? null;
        if (is_array($self) && ($self['CType'] ?? null) === 'shortcut' && is_string($self['records'] ?? null)) {
            foreach (explode(',', $self['records']) as $reference) {
                $references[] = trim($reference);
            }
        }
        foreach ($node as $child) {
            $this->collectRecordReferences($child, $references);
        }
    }
}
