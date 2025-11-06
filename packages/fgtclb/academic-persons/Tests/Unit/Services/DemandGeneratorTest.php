<?php declare(strict_types=1);

namespace Fgtclb\AcademicPersons\Tests\Unit\Services;

use Fgtclb\AcademicPersons\Services\DemandGenerator;
use Fgtclb\AcademicPersons\Domain\Model\Dto\ProfileDemandInterface;
use Fgtclb\AcademicPersons\Event\PostModifyDemandEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @covers \Fgtclb\AcademicPersons\Services\DemandGenerator
 */
class DemandGeneratorTest extends UnitTestCase
{
    /**
     * @var EventDispatcherInterface&MockObject
     */
    private $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @test
     */
    public function overrideProfileDemandSkipsEmptyValuesAndSetsOthers(): void
    {
        /** @var ProfileDemandInterface&MockObject $demand */
        $demand = $this->createMock(ProfileDemandInterface::class);

        $demand
            ->expects(self::once())
            ->method('setShowPublicOnly')
            ->with(true);

        $generator = new DemandGenerator($this->eventDispatcher);

        $generator->overrideProfileDemand($demand, [
            'showPublic' => '1',
            'someEmptyProp' => '',
        ]);

        self::assertTrue(true);
    }

    /**
     * @test
     */
    public function createProfileDemandParsesFunctionTypesAndOrganisationalUnitsAndPages(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (PostModifyDemandEvent $event) {
                return $event; // return unchanged
            });

        $generator = new DemandGenerator($this->eventDispatcher);

        $demand = $generator->createProfileDemand([
            'functionTypes' => '1,2,3',
            'organisationalUnits' => '4,5',
            'pages' => '123',
            'showPublic' => '1',
        ]);

        self::assertSame([1, 2, 3], $demand->getFunctionTypes());
        self::assertSame([4, 5], $demand->getOrganisationalUnits());
        self::assertSame('123', $demand->getStoragePages());
        self::assertTrue($demand->getShowPublicOnly());
    }

    /**
     * @test
     */
    public function createProfileDemandParseAllowedShowPublic(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (PostModifyDemandEvent $event) {
                return $event; // return unchanged
            });

        $generator = new DemandGenerator($this->eventDispatcher);

        $demand = $generator->createProfileDemand([
            'showPublic' => '0',
        ]);

        self::assertFalse($demand->getShowPublicOnly());
    }

    /**
     * @test
     */
    public function createProfileDemandIgnoresEmptyListsAndEmptyPages(): void
    {
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (PostModifyDemandEvent $event) {
                return $event;
            });

        $generator = new DemandGenerator($this->eventDispatcher);

        $demand = $generator->createProfileDemand([
            'functionTypes' => '',
            'organisationalUnits' => '',
            'pages' => '',
        ]);

        self::assertSame([], $demand->getFunctionTypes());
        self::assertSame([], $demand->getOrganisationalUnits());
        self::assertSame('', $demand->getStoragePages());
    }

    /**
     * @test
     */
    public function createProfileDemandSetsFallbackForNonTranslatedOnlyWhenOne(): void
    {
        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (PostModifyDemandEvent $event) {
                return $event;
            });

        $generator = new DemandGenerator($this->eventDispatcher);

        $demand0 = $generator->createProfileDemand(['fallbackForNonTranslated' => 0]);
        self::assertSame(0, (int)$demand0->getFallbackForNonTranslated());

        $demand1 = $generator->createProfileDemand(['fallbackForNonTranslated' => 1]);
        self::assertSame(1, (int)$demand1->getFallbackForNonTranslated());
    }
}
