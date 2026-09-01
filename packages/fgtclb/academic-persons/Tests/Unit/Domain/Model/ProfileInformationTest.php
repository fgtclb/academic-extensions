<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileInformationTest extends UnitTestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        new ProfileInformation();
    }

    #[Test]
    public function getProfileReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getProfile());
    }

    #[Test]
    public function getTypeReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new ProfileInformation())->getType());
    }

    #[Test]
    public function getTitleReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new ProfileInformation())->getTitle());
    }

    #[Test]
    public function getBodytextReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new ProfileInformation())->getBodytext());
    }

    #[Test]
    public function getLinkReturnsEmptyStringForNewModel(): void
    {
        $this->assertSame('', (new ProfileInformation())->getLink());
    }

    #[Test]
    public function getYearReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getYear());
    }

    #[Test]
    public function getYearStartReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getYearStart());
    }

    #[Test]
    public function getYearEndReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getYearEnd());
    }

    #[Test]
    public function completeDatesAndYearOnlyFlagRoundTripWithoutTimeConversion(): void
    {
        $year = new \DateTime('2026-04-17');
        $yearStart = new \DateTime('2024-02-29');
        $yearEnd = new \DateTime('2028-12-31');
        $subject = (new ProfileInformation())
            ->setYear($year)
            ->setYearStart($yearStart)
            ->setYearEnd($yearEnd)
            ->setYearOnly(true);
        $this->assertSame($year, $subject->getYear());
        $this->assertSame($yearStart, $subject->getYearStart());
        $this->assertSame($yearEnd, $subject->getYearEnd());
        $this->assertTrue($subject->isYearOnly());
    }

    #[Test]
    public function getSortingReturnsIntegerZeroForNewModel(): void
    {
        $this->assertSame(0, (new ProfileInformation())->getSorting());
    }
}
