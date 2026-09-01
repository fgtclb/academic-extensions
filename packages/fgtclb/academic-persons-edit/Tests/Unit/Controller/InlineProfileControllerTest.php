<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Controller;

use FGTCLB\AcademicPersons\Domain\Repository\ContractRepository;
use FGTCLB\AcademicPersons\Domain\Repository\FunctionTypeRepository;
use FGTCLB\AcademicPersons\Domain\Repository\LocationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\OrganisationalUnitRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileInformationRepository;
use FGTCLB\AcademicPersons\Domain\Repository\ProfileRepository;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersonsEdit\Controller\InlineProfileController;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ContractFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileFormDataFactoryInterface;
use FGTCLB\AcademicPersonsEdit\Domain\Factory\ProfileInformationFactory;
use FGTCLB\AcademicPersonsEdit\Domain\Parser\ProfileUpdatePayloadParser;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\ProfileFormDataValidator;
use FGTCLB\AcademicPersonsEdit\Service\ProfileDocumentSectionProvider;
use FGTCLB\AcademicPersonsEdit\Service\ProfileFieldOptionsService;
use FGTCLB\AcademicPersonsEdit\Service\ProfileRichTextSanitizerInterface;
use FGTCLB\AcademicPersonsEdit\Service\ProfileSectionProvider;
use FGTCLB\AcademicPersonsEdit\Service\ProfileUpdateRequestService;
use FGTCLB\AcademicPersonsEdit\Service\ProfileUpdateValidationService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class InlineProfileControllerTest extends UnitTestCase
{
    #[Test]
    public function updateRejectsNonPostRequest(): void
    {
        $subject = $this->createSubject(
            $this->createRequest('GET')
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateAction(),
        );

        $this->assertJsonResponse(
            $response,
            405,
            [
                'success' => false,
                'error' => 'method_not_allowed',
            ],
        );
    }

    #[Test]
    public function updateRejectsInvalidJson(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                '{"profile": 123,'
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateAction(),
        );

        $this->assertJsonResponse(
            $response,
            400,
            [
                'success' => false,
                'error' => 'invalid_json',
            ],
        );
    }

    #[Test]
    public function updateRejectsInvalidPayload(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                json_encode(
                    [
                        'profile' => 123,
                    ],
                    JSON_THROW_ON_ERROR,
                )
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateAction(),
        );

        $this->assertJsonResponse(
            $response,
            400,
            [
                'success' => false,
                'error' => 'invalid_payload',
            ],
        );
    }

    #[Test]
    public function updateReturnsJsonWhenAuthenticationIsRequired(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                json_encode(
                    [
                        'profile' => 123,
                        'data' => [],
                    ],
                    JSON_THROW_ON_ERROR,
                )
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateAction(),
        );

        $this->assertJsonResponse(
            $response,
            401,
            [
                'success' => false,
                'error' => 'authentication_required',
            ],
        );
    }

    #[Test]
    public function updateSkipSyncRejectsNonPostRequest(): void
    {
        $subject = $this->createSubject(
            $this->createRequest('GET')
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateSkipSyncAction(),
        );

        $this->assertJsonResponse(
            $response,
            405,
            [
                'success' => false,
                'error' => 'method_not_allowed',
            ],
        );
    }

    #[Test]
    public function updateSkipSyncReturnsJsonWhenAuthenticationIsRequired(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                json_encode(
                    [
                        'profile' => 123,
                        'data' => ['skipSync' => true],
                    ],
                    JSON_THROW_ON_ERROR,
                )
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateSkipSyncAction(),
        );

        $this->assertJsonResponse(
            $response,
            401,
            [
                'success' => false,
                'error' => 'authentication_required',
            ],
        );
    }

    #[Test]
    public function updateSkipSyncRejectsInvalidJson(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                '{"profile": 123,'
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->updateSkipSyncAction(),
        );

        $this->assertJsonResponse(
            $response,
            400,
            [
                'success' => false,
                'error' => 'invalid_json',
            ],
        );
    }

    #[Test]
    public function deleteImageRejectsNonPostRequest(): void
    {
        $subject = $this->createSubject(
            $this->createRequest('GET')
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->deleteImageAction(),
        );

        $this->assertJsonResponse(
            $response,
            405,
            [
                'success' => false,
                'error' => 'method_not_allowed',
            ],
        );
    }

    #[Test]
    public function deleteImageRejectsInvalidJson(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                '{"profile": 123,'
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->deleteImageAction(),
        );

        $this->assertJsonResponse(
            $response,
            400,
            [
                'success' => false,
                'error' => 'invalid_json',
            ],
        );
    }

    #[Test]
    public function deleteImageReturnsJsonWhenAuthenticationIsRequired(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                json_encode(
                    [
                        'profile' => 123,
                        'data' => [],
                    ],
                    JSON_THROW_ON_ERROR,
                )
            )
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->deleteImageAction(),
        );

        $this->assertJsonResponse(
            $response,
            401,
            [
                'success' => false,
                'error' => 'authentication_required',
            ],
        );
    }

    #[Test]
    public function documentFormReturnsJsonWhenAuthenticationIsRequired(): void
    {
        $subject = $this->createSubject(
            $this->createRequest(
                'POST',
                json_encode(
                    [
                        'profile' => 123,
                        'data' => ['section' => 'cooperation', 'record' => 0],
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ),
        );

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->documentFormAction(),
        );

        $this->assertJsonResponse(
            $response,
            401,
            [
                'success' => false,
                'error' => 'authentication_required',
            ],
        );
    }

    #[Test]
    public function sortDocumentRejectsNonPostRequest(): void
    {
        $subject = $this->createSubject($this->createRequest('GET'));

        $response = $this->getPropagatedResponse(
            static fn(): ResponseInterface => $subject->sortDocumentAction(),
        );

        $this->assertJsonResponse(
            $response,
            405,
            [
                'success' => false,
                'error' => 'method_not_allowed',
            ],
        );
    }

    private function createSubject(Request $request): InlineProfileController
    {
        $profileRepository = $this->createStub(ProfileRepository::class);
        $profileFormDataFactory = $this->createStub(ProfileFormDataFactoryInterface::class);
        $academicPersonsSettings = new AcademicPersonsSettings(
            profileSections: [],
            documentSections: [],
            raw: [],
        );
        $profileFieldOptionsService = new ProfileFieldOptionsService($academicPersonsSettings);

        $subject = new InlineProfileController(
            $this->createStub(ProfileFactory::class),
            $profileRepository,
            $this->createStub(ResourceFactory::class),
            new ProfileUpdateRequestService(
                new Context(),
                $profileRepository,
                new ProfileUpdatePayloadParser(),
            ),
            new ProfileUpdateValidationService(
                $profileFormDataFactory,
                new ProfileFormDataValidator(),
                $profileFieldOptionsService,
                $this->createStub(ProfileRichTextSanitizerInterface::class),
                $academicPersonsSettings,
            ),
            $profileFieldOptionsService,
            new ProfileSectionProvider($academicPersonsSettings),
            new ProfileDocumentSectionProvider(
                $academicPersonsSettings,
            ),
            $this->createStub(ContractFactory::class),
            $this->createStub(ContractRepository::class),
            $this->createStub(ProfileInformationFactory::class),
            $this->createStub(ProfileInformationRepository::class),
            $this->createStub(FunctionTypeRepository::class),
            $this->createStub(OrganisationalUnitRepository::class),
            $this->createStub(LocationRepository::class),
            $this->createStub(ProfileRichTextSanitizerInterface::class),
        );

        $requestProperty = new \ReflectionProperty(
            ActionController::class,
            'request',
        );
        $requestProperty->setValue($subject, $request);

        return $subject;
    }

    private function createRequest(
        string $method,
        string $body = '',
    ): Request {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($body);
        $stream->rewind();

        $serverRequest = (new ServerRequest())
            ->withMethod($method)
            ->withBody($stream)
            ->withHeader('Content-Type', 'application/json')
            ->withAttribute(
                'extbase',
                new ExtbaseRequestParameters(),
            );

        return new Request($serverRequest);
    }

    /**
     * @param callable(): ResponseInterface $action
     */
    private function getPropagatedResponse(callable $action): ResponseInterface
    {
        try {
            $action();
        } catch (PropagateResponseException $exception) {
            return $exception->getResponse();
        }
        $this->fail('The JSON error response was not propagated to the middleware stack.');
    }

    /**
     * @param array<string, mixed> $expectedBody
     */
    private function assertJsonResponse(
        ResponseInterface $response,
        int $expectedStatusCode,
        array $expectedBody,
    ): void {
        $this->assertSame(
            $expectedStatusCode,
            $response->getStatusCode(),
        );

        $this->assertSame(
            $expectedBody,
            json_decode(
                (string)$response->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            ),
        );
    }
}
