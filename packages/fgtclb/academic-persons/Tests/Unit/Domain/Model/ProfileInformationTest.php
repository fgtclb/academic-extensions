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
    public function getDateReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getDate());
    }

    #[Test]
    public function getDateStartReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getDateStart());
    }

    #[Test]
    public function getDateEndReturnsNullForNewModel(): void
    {
        $this->assertNull((new ProfileInformation())->getDateEnd());
    }

    #[Test]
    public function completeDatesAndYearOnlyFlagRoundTripWithoutTimeConversion(): void
    {
        $date = new \DateTime('2026-04-17');
        $dateStart = new \DateTime('2024-02-29');
        $dateEnd = new \DateTime('2028-12-31');
        $subject = (new ProfileInformation())
            ->setDate($date)
            ->setDateStart($dateStart)
            ->setDateEnd($dateEnd)
            ->setYearOnly(true);
        $this->assertSame($date, $subject->getDate());
        $this->assertSame($dateStart, $subject->getDateStart());
        $this->assertSame($dateEnd, $subject->getDateEnd());
        $this->assertTrue($subject->isYearOnly());
    }

    #[Test]
    public function getSortingReturnsIntegerZeroForNewModel(): void
    {
        $this->assertSame(0, (new ProfileInformation())->getSorting());
    }
}
