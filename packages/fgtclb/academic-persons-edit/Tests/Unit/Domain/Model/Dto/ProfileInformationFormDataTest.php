<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `Controller\ProfileInformationController` instantiates this class through the
 * configurable `$profileInformationFormDataClassName`, not through a hard coded name, so
 * both static factories have to honour late static binding. It is the only form data
 * object with a second factory for the "new record" case and with nullable properties.
 */
final class ProfileInformationFormDataTest extends UnitTestCase
{
    /**
     * The `newAction()` case. The type is what decides which fields the template renders
     * and under which type the record is stored, so it is the one value that must survive;
     * everything else has to arrive as its default so the form comes up empty.
     */
    #[Test]
    public function anEmptyFormDataCarriesOnlyTheRequestedType(): void
    {
        $formData = ProfileInformationFormData::createEmptyForType('publications');

        $this->assertSame('publications', $formData->getType());
        $this->assertSame(['', '', ''], [$formData->getTitle(), $formData->getBodytext(), $formData->getLink()]);
        $this->assertNull($formData->getDate());
        $this->assertNull($formData->getDateStart());
        $this->assertNull($formData->getDateEnd());
        $this->assertFalse($formData->isYearOnly());
    }

    /**
     * `ProfileInformationController::newAction()` resolves an unknown type to an empty
     * string rather than raising. The factory must not turn that into something else -
     * the validator downstream is what rejects it.
     */
    #[Test]
    public function anUnresolvedTypeIsKeptAsAnEmptyString(): void
    {
        $this->assertSame('', ProfileInformationFormData::createEmptyForType('')->getType());
    }

    /**
     * Four strings, three nullable dates and a flag, with `date`, `dateStart` and `dateEnd`
     * being interchangeable at the type level: a swapped assignment is only visible when
     * all eight are asserted at once against distinct values.
     */
    #[Test]
    public function everyPersistedPropertyOfAProfileInformationReachesTheFormData(): void
    {
        $profileInformation = new ProfileInformation();
        $profileInformation->setType('vita');
        $profileInformation->setTitle('Research assistant');
        $profileInformation->setBodytext('Worked on distributed systems.');
        $profileInformation->setLink('https://example.org/vita');
        $date = new \DateTime('2021-06-15');
        $dateStart = new \DateTime('2018-10-01');
        $dateEnd = new \DateTime('2024-03-31');
        $profileInformation->setDate($date);
        $profileInformation->setDateStart($dateStart);
        $profileInformation->setDateEnd($dateEnd);
        $profileInformation->setYearOnly(true);

        $formData = ProfileInformationFormData::createFromProfileInformation($profileInformation);

        $this->assertSame(
            [
                'type' => 'vita',
                'title' => 'Research assistant',
                'bodytext' => 'Worked on distributed systems.',
                'link' => 'https://example.org/vita',
                'date' => $date,
                'dateStart' => $dateStart,
                'dateEnd' => $dateEnd,
                'yearOnly' => true,
            ],
            [
                'type' => $formData->getType(),
                'title' => $formData->getTitle(),
                'bodytext' => $formData->getBodytext(),
                'link' => $formData->getLink(),
                'date' => $formData->getDate(),
                'dateStart' => $formData->getDateStart(),
                'dateEnd' => $formData->getDateEnd(),
                'yearOnly' => $formData->isYearOnly(),
            ],
        );
    }

    /**
     * A vita entry without an end date is an ongoing one. `null` is a stored value of its
     * own for the template and for the database, so the mapping may not substitute one.
     */
    #[Test]
    public function unsetDatesStayNull(): void
    {
        $profileInformation = new ProfileInformation();
        $dateStart = new \DateTime('2018-01-01');
        $profileInformation->setDateStart($dateStart);

        $formData = ProfileInformationFormData::createFromProfileInformation($profileInformation);

        $this->assertSame($dateStart, $formData->getDateStart());
        $this->assertNull($formData->getDate());
        $this->assertNull($formData->getDateEnd());
    }

    /**
     * Both factories use `new static()`. A project replacing
     * `$profileInformationFormDataClassName` with its own subclass would otherwise get the
     * base class back and lose every property its own validator relies on.
     */
    #[Test]
    public function bothFactoriesRespectTheLateStaticBoundClass(): void
    {
        $subclass = new class () extends ProfileInformationFormData {};

        $this->assertInstanceOf($subclass::class, $subclass::createEmptyForType('vita'));
        $this->assertInstanceOf($subclass::class, $subclass::createFromProfileInformation(new ProfileInformation()));
    }

    /**
     * Both factories produce display objects: nothing is bound to a request and nothing is
     * overridden, so `ProfileInformationFactory` may not write any of it back.
     */
    #[Test]
    public function neitherFactoryProducesAnApplicableProperty(): void
    {
        foreach ([
            ProfileInformationFormData::createEmptyForType('vita'),
            ProfileInformationFormData::createFromProfileInformation(new ProfileInformation()),
        ] as $formData) {
            $this->assertNull($formData->getArgumentName());
            $this->assertFalse($formData->shouldApplyProperty('type'));
            $this->assertFalse($formData->shouldApplyProperty('title'));
            $this->assertFalse($formData->shouldApplyProperty('date'));
        }
    }
}
