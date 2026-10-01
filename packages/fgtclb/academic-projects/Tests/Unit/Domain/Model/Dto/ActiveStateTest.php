<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `ActiveState` decides which projects a list plugin shows: all of them, the running
 * ones, or the finished ones. The value arrives from a FlexForm, so it is a string
 * from outside and `tryFromDefault()` is what stands between that string and the
 * repository.
 */
final class ActiveStateTest extends UnitTestCase
{
    #[Test]
    public function allIsTheDefault(): void
    {
        $this->assertSame(ActiveState::ALL, ActiveState::default());
    }

    #[Test]
    #[DataProvider('knownValues')]
    public function aKnownValueResolvesToItsCase(string $value, ActiveState $expected): void
    {
        $this->assertSame($expected, ActiveState::tryFromDefault($value));
    }

    /**
     * @return \Generator<string, array{0: string, 1: ActiveState}>
     */
    public static function knownValues(): \Generator
    {
        yield 'all' => ['all', ActiveState::ALL];
        yield 'active' => ['active', ActiveState::ACTIVE];
        yield 'completed' => ['completed', ActiveState::COMPLETED];
    }

    /**
     * The point of `tryFromDefault()` over `from()`: an unusable value must not reach
     * the repository as an exception, it has to fall back. A FlexForm that was saved
     * before an option was renamed still holds the old string.
     */
    #[Test]
    #[DataProvider('unknownValues')]
    public function anUnknownValueFallsBackToAll(string $value): void
    {
        $this->assertSame(ActiveState::ALL, ActiveState::tryFromDefault($value));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unknownValues(): \Generator
    {
        yield 'empty string' => [''];
        yield 'unknown option' => ['pending'];
        yield 'wrong case' => ['ACTIVE'];
        yield 'padded' => [' active '];
        yield 'the case name instead of its value' => ['COMPLETED'];
    }

    /**
     * `ProjectDemand::getPossibleActiveStates()` hands this list to the FlexForm item
     * list, so the order is what an editor sees.
     */
    #[Test]
    public function valuesAreTheCaseValuesInDeclarationOrder(): void
    {
        $this->assertSame(['all', 'active', 'completed'], ActiveState::values());
    }

    /**
     * The state of one project, for a template. It is the rule of the list filter in
     * `ProjectRepository::findByDemand()`: no end date or one still ahead is active, one
     * that has passed is completed. A project is never in the state "all", so a template
     * that reads it always gets a label to show.
     */
    #[Test]
    #[DataProvider('endDates')]
    public function theEndDateDecidesBetweenActiveAndCompleted(?\DateTimeInterface $endDate, ActiveState $expected): void
    {
        $now = new \DateTimeImmutable('2026-10-01 12:00:00');

        $this->assertSame($expected, ActiveState::fromEndDate($endDate, $now));
    }

    /**
     * @return \Generator<string, array{0: ?\DateTimeInterface, 1: ActiveState}>
     */
    public static function endDates(): \Generator
    {
        yield 'no end date' => [null, ActiveState::ACTIVE];
        yield 'ending tomorrow' => [new \DateTimeImmutable('2026-10-02 12:00:00'), ActiveState::ACTIVE];
        yield 'ended yesterday' => [new \DateTimeImmutable('2026-09-30 12:00:00'), ActiveState::COMPLETED];
        // The "Active" filter asks for an end date after now, so the very moment
        // itself is not active any more.
        yield 'ending right now' => [new \DateTimeImmutable('2026-10-01 12:00:00'), ActiveState::COMPLETED];
        yield 'a mutable end date' => [new \DateTime('2026-10-02 12:00:00'), ActiveState::ACTIVE];
    }

    /**
     * Guards the pair above against drifting apart: every value the enum offers has to
     * be resolvable again, or `values()` would advertise something `tryFrom()` rejects.
     */
    #[Test]
    public function everyAdvertisedValueResolvesBack(): void
    {
        foreach (ActiveState::values() as $value) {
            $this->assertNotNull(ActiveState::tryFrom($value), sprintf('Value "%s" does not resolve', $value));
        }
    }
}
