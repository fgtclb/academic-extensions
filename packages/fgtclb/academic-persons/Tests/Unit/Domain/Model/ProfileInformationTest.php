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

    /**
     * The three columns are native SQL `DATE` values, so the model carries
     * `\DateTime` objects rather than the four digit integers it used to hold.
     * A date is kept as it is handed in - the model neither normalizes the
     * time part nor applies a timezone.
     */
    #[Test]
    public function datesRoundTripAsNullableDateTimeObjects(): void
    {
        $date = new \DateTime('2026-03-14');
        $dateStart = new \DateTime('2024-10-01');
        $dateEnd = new \DateTime('2028-09-30');
        $subject = (new ProfileInformation())
            ->setDate($date)
            ->setDateStart($dateStart)
            ->setDateEnd($dateEnd);
        $this->assertSame($date, $subject->getDate());
        $this->assertSame($dateStart, $subject->getDateStart());
        $this->assertSame($dateEnd, $subject->getDateEnd());
        $this->assertSame('2026-03-14', $subject->getDate()->format('Y-m-d'));
        $this->assertSame('2024-10-01', $subject->getDateStart()->format('Y-m-d'));
        $this->assertSame('2028-09-30', $subject->getDateEnd()->format('Y-m-d'));
        $this->assertNull($subject->setDate(null)->getDate());
    }

    #[Test]
    public function getSortingReturnsIntegerZeroForNewModel(): void
    {
        $this->assertSame(0, (new ProfileInformation())->getSorting());
    }
}
