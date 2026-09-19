<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Unit\Build\Fixtures;

/**
 * A class name for the phpunit 10 test lists of SplitFunctionalTestsTest: the
 * splitter looks the file of a listed class up with the composer class loader,
 * so the name has to resolve to a file. Deliberately not named "*Test", or the
 * unit suite would collect it.
 */
final class Kilo {}
