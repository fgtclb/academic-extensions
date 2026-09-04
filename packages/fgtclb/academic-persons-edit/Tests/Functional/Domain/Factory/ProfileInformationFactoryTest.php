<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileInformationFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use PHPUnit\Framework\Attributes\Test;

/**
 * Profile information is the only form of the package with nullable date properties
 * (`date`, `dateStart`, `dateEnd`), and `null` there is a meaningful stored value rather than
 * an "empty" one. That makes it the place to pin two things the string based forms cannot show:
 *
 * - an empty date field that *was* submitted has to reach the record as `NULL`, and
 * - the override type check is `instanceof \DateTime`, so `null` - the only value that could
 *   express "clear this date" - is the one value an override cannot carry.
 *
 * The submitted values use the `d.m.Y` format `AbstractActionController::DATETIME_ARGUMENTS`
 * configures for this argument; the stored columns are native SQL DATE columns.
 */
final class ProfileInformationFactoryTest extends AbstractFactoryTestCase
{
    /**
     * @var array<non-empty-string, string>
     */
    private const DATE_FORMATS = ['date' => 'd.m.Y', 'dateStart' => 'd.m.Y', 'dateEnd' => 'd.m.Y'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/profileWithInformation.csv');
    }

    /**
     * The two date properties that were not submitted stay `NULL` on a new record rather than
     * being written as an empty date, which is what the nullable column and property are for.
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
                    'date' => '01.01.2020',
                ],
            ],
            self::DATE_FORMATS,
        );
        $this->assertSame('2020-01-01', $formData->getDate()?->format('Y-m-d'));
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
     * An emptied date input is submitted as an empty string, which `DateTimeConverter` turns
     * into `null` - and that `null` must be written, because clearing a date is a thing an
     * editor is allowed to do.
     */
    #[Test]
    public function updateAppliesSubmittedEmptyDateAsNull(): void
    {
        $formData = $this->mapFormDataForUpdate(['title' => 'New Title', 'date' => '']);
        $this->assertNull($formData->getDate());
        $this->assertTrue($formData->wasPropertySentInRequest('date'));

        $this->applyAndPersist($formData);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndClearedDate.csv');
    }

    #[Test]
    public function updateAppliesDateOverrideForDateThatWasNotSubmitted(): void
    {
        $this->updateProfileInformationWith(['title' => 'New Title'], ['date' => new \DateTime('2021-01-01')]);

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndOverriddenDate.csv');
    }

    /**
     * Documents current behaviour, and it is the shape of a defect: `setDate()` applies the
     * override only when it is a `\DateTime`, so an override of `null` - the only way to say
     * "clear the date" - falls through to the submitted value instead. A listener meaning to
     * clear the date silently keeps whatever the editor sent.
     *
     * @see ProfileInformationFactory::setDate()
     */
    #[Test]
    public function nullOverrideCannotClearADateAndFallsBackToTheSubmittedValue(): void
    {
        $this->updateProfileInformationWith(
            ['title' => 'New Title', 'date' => '01.01.2022'],
            ['date' => null],
        );

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/ProfileInformationFactoryTest/updatedTitleAndSubmittedDate.csv');
    }

    /**
     * The same trap with the stakes raised: the date was not submitted, so the fallback is the
     * form data default `null`, and a `null` override wipes the stored date although the type
     * check was supposed to reject the value.
     *
     * @see ProfileInformationFactory::setDate()
     */
    #[Test]
    public function nullOverrideWipesAStoredDateThatWasNotSubmitted(): void
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
