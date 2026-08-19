<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Service;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileFormDataFactoryInterface;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileUpdatePayload;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\ProfileFormDataValidator;
use FGTCLB\AcademicPersonsEdit\Service\ProfileGenderOptionsService;
use FGTCLB\AcademicPersonsEdit\Service\ProfileUpdateValidationService;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\RecordingValidator;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\ValidationSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use UnexpectedValueException;

final class ProfileUpdateValidationServiceTest extends UnitTestCase
{
    private bool $tcaWasDefined = false;

    /** @var mixed */
    private $tcaBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tcaWasDefined = array_key_exists('TCA', $GLOBALS);
        $this->tcaBackup = $GLOBALS['TCA'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->tcaWasDefined) {
            $GLOBALS['TCA'] = $this->tcaBackup;
        } else {
            unset($GLOBALS['TCA']);
        }
        parent::tearDown();
    }

    #[Test]
    public function payloadPropertiesAreRegisteredAsOverridesOnFactoryResult(): void
    {
        $context = $this->createPluginControllerActionContext();
        $profile = new Profile();
        $formData = new ProfileFormData(
            firstName: 'Persisted',
            lastName: 'Unchanged',
        );
        $factory = $this->createMock(ProfileFormDataFactoryInterface::class);
        $factory
            ->expects(self::once())
            ->method('createFromProfile')
            ->with($context, $profile)
            ->willReturn($formData);
        $subject = $this->createSubject($factory);

        $result = $subject->createFormData(
            $context,
            $profile,
            new ProfileUpdatePayload(
                profileUid: 123,
                data: [
                    'firstName' => 'Submitted',
                    'website' => '',
                    'skipSync' => true,
                ],
            ),
        );

        self::assertSame($formData, $result);
        self::assertSame('Submitted', $result->getPropertyOverride('firstName'));
        self::assertSame('', $result->getPropertyOverride('website'));
        self::assertTrue($result->getPropertyOverride('skipSync'));
        self::assertTrue($result->shouldApplyProperty('firstName'));
        self::assertFalse($result->hasPropertyOverride('lastName'));
        self::assertFalse($result->shouldApplyProperty('lastName'));
    }

    #[Test]
    public function unknownProfilePropertyIsRejected(): void
    {
        $subject = $this->createSubjectForFormData(new ProfileFormData());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Unknown profile property "unknown".');

        $subject->createFormData(
            $this->createPluginControllerActionContext(),
            new Profile(),
            new ProfileUpdatePayload(
                profileUid: 123,
                data: ['unknown' => 'value'],
            ),
        );
    }

    #[Test]
    #[DataProvider('invalidGenderValues')]
    public function invalidGenderIsRejected(mixed $gender): void
    {
        $this->setGenderItems([
            ['label' => 'Female', 'value' => 'female'],
        ]);
        $subject = $this->createSubjectForFormData(new ProfileFormData());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid gender value.');

        $subject->createFormData(
            $this->createPluginControllerActionContext(),
            new Profile(),
            new ProfileUpdatePayload(
                profileUid: 123,
                data: ['gender' => $gender],
            ),
        );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidGenderValues(): array
    {
        return [
            'unknown string' => ['unknown'],
            'integer' => [1],
            'null' => [null],
            'array' => [['female']],
        ];
    }

    #[Test]
    #[DataProvider('validGenderValues')]
    public function configuredOrEmptyGenderIsAccepted(string $gender): void
    {
        $this->setGenderItems([
            ['label' => 'Female', 'value' => 'female'],
        ]);
        $subject = $this->createSubjectForFormData(new ProfileFormData());

        $result = $subject->createFormData(
            $this->createPluginControllerActionContext(),
            new Profile(),
            new ProfileUpdatePayload(
                profileUid: 123,
                data: ['gender' => $gender],
            ),
        );

        self::assertTrue($result->hasPropertyOverride('gender'));
        self::assertSame($gender, $result->getPropertyOverride('gender'));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function validGenderValues(): array
    {
        return [
            'configured value' => ['female'],
            'empty value for clearing' => [''],
        ];
    }

    #[Test]
    public function validateReturnsErrorsFromProfileFormDataValidator(): void
    {
        $validator = new ProfileFormDataValidator();
        $validator->injectAcademicPersonsSettings(
            ValidationSettings::forIdentifier(
                'profile',
                ['firstName' => [RecordingValidator::class]],
            ),
        );
        $subject = new ProfileUpdateValidationService(
            $this->createStub(ProfileFormDataFactoryInterface::class),
            $validator,
            new ProfileGenderOptionsService(),
        );

        $result = $subject->validate(
            new ProfileFormData(firstName: 'Jane'),
        );

        self::assertSame(
            ['string(Jane)'],
            array_map(
                static fn($error): string => $error->getMessage(),
                $result->forProperty('firstName')->getErrors(),
            ),
        );
    }

    private function createSubjectForFormData(
        ProfileFormData $formData,
    ): ProfileUpdateValidationService {
        $factory = $this->createStub(ProfileFormDataFactoryInterface::class);
        $factory->method('createFromProfile')->willReturn($formData);
        return $this->createSubject($factory);
    }

    private function createSubject(
        ProfileFormDataFactoryInterface $factory,
    ): ProfileUpdateValidationService {
        return new ProfileUpdateValidationService(
            $factory,
            new ProfileFormDataValidator(),
            new ProfileGenderOptionsService(),
        );
    }

    private function createPluginControllerActionContext(): PluginControllerActionContext
    {
        $request = (new ServerRequest())->withAttribute(
            'extbase',
            new ExtbaseRequestParameters(),
        );

        return new PluginControllerActionContext(
            new Request($request),
            [],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function setGenderItems(array $items): void
    {
        $GLOBALS['TCA'] = [
            'tx_academicpersons_domain_model_profile' => [
                'columns' => [
                    'gender' => [
                        'config' => [
                            'items' => $items,
                        ],
                    ],
                ],
            ],
        ];
    }
}
