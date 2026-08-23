<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

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
}
