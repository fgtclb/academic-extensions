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
 * Profile editing instantiates this class through the configurable
 * `$profileInformationFormDataClassName`, not through a hard coded name, so both
 * static factories have to honour late static binding. It is the only form data
 * object with a second factory for the "new record" case and nullable properties.
 */
final class ProfileInformationFormDataTest extends UnitTestCase
{
    /**
     * The new-record case. The type is what decides which fields the template renders
     * and under which type the record is stored, so it is the one value that must survive;
     * everything else has to arrive as its default so the form comes up empty.
     */
    #[Test]
    public function anEmptyFormDataCarriesOnlyTheRequestedType(): void
    {
        $formData = ProfileInformationFormData::createEmptyForType('publications');

        $this->assertSame('publications', $formData->getType());
        $this->assertSame(['', '', ''], [$formData->getTitle(), $formData->getBodytext(), $formData->getLink()]);
        $this->assertNull($formData->getYear());
        $this->assertNull($formData->getYearStart());
        $this->assertNull($formData->getYearEnd());
        $this->assertFalse($formData->isYearOnly());
    }

    /**
     * The request handling resolves an unknown type to an empty string rather than
     * raising. The factory must not turn that into something else - the validator
     * downstream is what rejects it.
     */
    #[Test]
    public function anUnresolvedTypeIsKeptAsAnEmptyString(): void
    {
        $this->assertSame('', ProfileInformationFormData::createEmptyForType('')->getType());
    }

    /**
     * Four strings, three nullable dates and one presentation flag, with
     * `year`, `yearStart` and `yearEnd`
     * being interchangeable at the type level: a swapped assignment is only visible when
     * all eight values are asserted at once against distinct values.
     */
    #[Test]
    public function everyPersistedPropertyOfAProfileInformationReachesTheFormData(): void
    {
        $profileInformation = new ProfileInformation();
        $profileInformation->setType('vita');
        $profileInformation->setTitle('Research assistant');
        $profileInformation->setBodytext('Worked on distributed systems.');
        $profileInformation->setLink('https://example.org/vita');
        $profileInformation->setYear(new \DateTime('2021-05-12'));
        $profileInformation->setYearStart(new \DateTime('2018-02-03'));
        $profileInformation->setYearEnd(new \DateTime('2024-11-30'));
        $profileInformation->setYearOnly(true);

        $formData = ProfileInformationFormData::createFromProfileInformation($profileInformation);

        $this->assertSame(
            [
                'type' => 'vita',
                'title' => 'Research assistant',
                'bodytext' => 'Worked on distributed systems.',
                'link' => 'https://example.org/vita',
                'year' => '2021-05-12',
                'yearStart' => '2018-02-03',
                'yearEnd' => '2024-11-30',
                'yearOnly' => true,
            ],
            [
                'type' => $formData->getType(),
                'title' => $formData->getTitle(),
                'bodytext' => $formData->getBodytext(),
                'link' => $formData->getLink(),
                'year' => $formData->getYear()?->format('Y-m-d'),
                'yearStart' => $formData->getYearStart()?->format('Y-m-d'),
                'yearEnd' => $formData->getYearEnd()?->format('Y-m-d'),
                'yearOnly' => $formData->isYearOnly(),
            ],
        );
    }

    /**
     * A vita entry without an end date is an ongoing one. ``null`` and a real
     * calendar date mean different things, so the mapping may not synthesize a value.
     */
    #[Test]
    public function unsetYearsStayNullInsteadOfBecomingZero(): void
    {
        $profileInformation = new ProfileInformation();
        $profileInformation->setYearStart(new \DateTime('2018-06-15'));

        $formData = ProfileInformationFormData::createFromProfileInformation($profileInformation);

        $this->assertSame('2018-06-15', $formData->getYearStart()?->format('Y-m-d'));
        $this->assertNull($formData->getYear());
        $this->assertNull($formData->getYearEnd());
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
     * Both factories produce display objects without explicit property overrides, so
     * `ProfileInformationFactory` may not write any of it back.
     */
    #[Test]
    public function neitherFactoryProducesAnApplicableProperty(): void
    {
        foreach ([
            ProfileInformationFormData::createEmptyForType('vita'),
            ProfileInformationFormData::createFromProfileInformation(new ProfileInformation()),
        ] as $formData) {
            $this->assertFalse($formData->shouldApplyProperty('type'));
            $this->assertFalse($formData->shouldApplyProperty('title'));
            $this->assertFalse($formData->shouldApplyProperty('year'));
        }
    }
}
