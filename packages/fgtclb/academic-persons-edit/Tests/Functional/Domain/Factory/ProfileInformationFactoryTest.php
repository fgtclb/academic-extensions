<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileInformationFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use PHPUnit\Framework\Attributes\Test;

/**
 * Profile information carries three nullable date properties (`date`, `dateStart`, `dateEnd`),
 * and `null` there is a meaningful stored value rather than an "empty" one. That makes it the
 * place to pin two things the string based forms cannot show:
 *
 * - an empty date field that *was* submitted has to reach the record as `NULL`, and
 * - a `null` override - the only way to say "clear this date" - is applied rather than falling
 *   back to the submitted value.
 *
 * The columns are native SQL `DATE` columns (`dbType => 'date'`), so a stored value is the day
 * alone and the CSV fixtures compare `YYYY-MM-DD` strings. The month and day the fixtures pick -
 * the first of January for a starting date, the last of December for an ending one - are the
 * fixtures' own choice; the extension applies no such rule and stores whatever day was written.
 */
final class ProfileInformationFactoryTest extends AbstractFactoryTestCase
{
    /**
     * The editor submits a date in the ISO notation its native control uses, so the property
     * mapper is configured for exactly that format - the same way the contract form data is
     * mapped with its own `d.m.Y`.
     */
    private const DATE_FORMATS = [
        'date' => 'Y-m-d',
        'dateStart' => 'Y-m-d',
        'dateEnd' => 'Y-m-d',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/profileWithInformation.csv');
    }

    /**
     * The two date properties that were not submitted stay `NULL` on a new record rather than
     * being written as a zero date, which is what the nullable column and the nullable property
     * are for.
     */
    #[Test]
    public function createFromFormDataBuildsRecordFromSubmittedValuesAndParentProfile(): void
    {
        $formData = $this->mapFormData(
            ProfileInformationFormData::class,
            'profileInformationFormData',
            [
                'profile' => '1',
                'profileInformationFormData' => [
                    'type' => 'vita',
                    'title' => 'New Title',
                    'bodytext' => 'New bodytext',
                    'link' => 'https://new.example.com',
                    'date' => '2020-03-14',
                ],
            ],
            self::DATE_FORMATS,
        );
        $this->assertSame('2020-03-14', $formData->getDate()?->format('Y-m-d'));
        $this->assertNull($formData->getDateStart());
        $profile = $this->persistenceManager()->getObjectByIdentifier(1, Profile::class);
        $this->assertInstanceOf(Profile::class, $profile);

        $profileInformation = (new ProfileInformationFactory())->createFromFormData(
            $this->createValidationSet('profileInformation'),
            $profile,
            $formData,
        );

        $this->assertSame($profile, $profileInformation->getProfile());
        $profileInformation->setPid(2);
        $profileInformation->setSorting(2);
        $this->persistenceManager()->add($profileInformation);
        $this->persistenceManager()->persistAll();

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/createdProfileInformation.csv');
    }

    /**
     * All three date properties are absent from the request. Their form data default is `null`,
     * so writing them unconditionally would silently drop three stored dates at once.
     */
    #[Test]
    public function updateKeepsStoredDatesThatWereNotSubmitted(): void
    {
        $this->updateProfileInformationWith(['title' => 'New Title']);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleOnly.csv');
    }

    /**
     * An emptied date input is submitted as an empty string, which the property mapper turns
     * into `null` - and that `null` must be written, because clearing a date is a thing an
     * editor is allowed to do.
     */
    #[Test]
    public function updateAppliesSubmittedEmptyDateAsNull(): void
    {
        $formData = $this->mapFormDataForUpdate(['title' => 'New Title', 'date' => '']);
        $this->assertNull($formData->getDate());
        $this->assertTrue($formData->shouldApplyProperty('date'));

        $this->applyAndPersist($formData);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndClearedDate.csv');
    }

    /**
     * The override carries a `\DateTime` rather than the four digit integer the column used to
     * hold, and it reaches a record whose date the request never mentioned.
     */
    #[Test]
    public function updateAppliesDateOverrideForDateThatWasNotSubmitted(): void
    {
        $this->updateProfileInformationWith(
            ['title' => 'New Title'],
            ['date' => new \DateTime('2021-05-09')],
        );

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndOverriddenDate.csv');
    }

    /**
     * A `null` override is the only way an event listener can say "clear this date", and it is
     * applied: the registered override *is* the value, so it wins over the value the editor
     * submitted rather than falling through to it.
     *
     * @see ProfileInformationFactory::setDate()
     */
    #[Test]
    public function nullOverrideClearsADateThatWasSubmitted(): void
    {
        $this->updateProfileInformationWith(
            ['title' => 'New Title', 'date' => '2022-08-01'],
            ['date' => null],
        );

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndClearedDate.csv');
    }

    /**
     * The same for a date that was not submitted at all: the override is registered, so the
     * stored date is cleared.
     *
     * @see ProfileInformationFactory::setDate()
     */
    #[Test]
    public function nullOverrideClearsAStoredDateThatWasNotSubmitted(): void
    {
        $this->updateProfileInformationWith(['title' => 'New Title'], ['date' => null]);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndClearedDate.csv');
    }

    #[Test]
    public function updateSkipsPropertyTheValidationSetMarksReadOnly(): void
    {
        $this->updateProfileInformationWith(
            ['title' => 'New Title', 'bodytext' => 'Submitted bodytext'],
            [],
            'bodytext',
        );

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleOnly.csv');
    }

    /**
     * @param array<string, string> $submitted
     * @param array<string, mixed> $overrides
     */
    private function updateProfileInformationWith(
        array $submitted,
        array $overrides = [],
        string $readOnlyProperty = '',
    ): void {
        $formData = $this->mapFormDataForUpdate($submitted);
        foreach ($overrides as $propertyName => $value) {
            $formData->setPropertyOverride($propertyName, $value);
        }
        $this->applyAndPersist($formData, $readOnlyProperty);
    }

    /**
     * @param array<string, string> $submitted
     */
    private function mapFormDataForUpdate(array $submitted): ProfileInformationFormData
    {
        return $this->mapFormData(
            ProfileInformationFormData::class,
            'profileInformationFormData',
            [
                'profileInformation' => '1',
                'profileInformationFormData' => $submitted,
            ],
            self::DATE_FORMATS,
        );
    }

    private function applyAndPersist(ProfileInformationFormData $formData, string $readOnlyProperty = ''): void
    {
        $profileInformation = $this->persistenceManager()->getObjectByIdentifier(1, ProfileInformation::class);
        $this->assertInstanceOf(ProfileInformation::class, $profileInformation);

        $profileInformation = (new ProfileInformationFactory())->updateFromFormData(
            $readOnlyProperty !== ''
                ? $this->createValidationSet('profileInformation', $readOnlyProperty, readOnly: true)
                : $this->createValidationSet('profileInformation'),
            $profileInformation,
            $formData,
        );
        $this->persistenceManager()->update($profileInformation);
        $this->persistenceManager()->persistAll();
    }
}
