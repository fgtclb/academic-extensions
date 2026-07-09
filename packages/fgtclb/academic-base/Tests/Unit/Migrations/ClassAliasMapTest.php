<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Migrations;

use FGTCLB\EnvironmentStateManager\StateBuildContext;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the backward-compatibility class alias declared in
 * {@see \FGTCLB\AcademicBase\Migrations\Code\ClassAliasMap} (registered by the
 * `typo3/class-alias-loader` composer plugin).
 *
 * Only the {@see StateBuildContext} value object is aliased: it is carried by-type
 * through the public PSR-14 event of EXT:academic_persons, so the removed
 * `\FGTCLB\AcademicBase\Environment\StateBuildContext` must keep resolving to its
 * replacement. The remaining environment / state-manager classes intentionally
 * stay real (deprecated) classes and must NOT be aliased, so their TYPO3
 * core-version aware dependency injection keeps working.
 */
final class ClassAliasMapTest extends TestCase
{
    private const DEPRECATED_STATE_BUILD_CONTEXT = 'FGTCLB\\AcademicBase\\Environment\\StateBuildContext';

    #[Test]
    public function deprecatedStateBuildContextResolvesToEnvironmentStateManager(): void
    {
        $this->assertTrue(
            class_exists(self::DEPRECATED_STATE_BUILD_CONTEXT),
            'Deprecated StateBuildContext alias is not resolvable.',
        );
        $this->assertSame(
            StateBuildContext::class,
            (new \ReflectionClass(self::DEPRECATED_STATE_BUILD_CONTEXT))->getName(),
            'Deprecated StateBuildContext does not resolve to the environment-state-manager class.',
        );
    }

    /**
     * Guards the design decision: the core-version aware / dependency-injected
     * classes must remain distinct, real classes and must not be turned into
     * aliases of the environment-state-manager classes.
     */
    #[Test]
    public function coreVersionAwareClassesAreNotAliased(): void
    {
        $stateManagerInterface = 'FGTCLB\\AcademicBase\\Environment\\StateManagerInterface';
        $this->assertTrue(interface_exists($stateManagerInterface));
        $this->assertSame(
            $stateManagerInterface,
            (new \ReflectionClass($stateManagerInterface))->getName(),
            'StateManagerInterface must stay a real academic_base interface, not a class alias.',
        );
    }
}
