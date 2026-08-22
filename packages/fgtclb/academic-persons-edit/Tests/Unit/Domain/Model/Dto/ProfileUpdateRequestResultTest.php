<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileUpdatePayload;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileUpdateRequestResult;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileUpdateRequestResultTest extends UnitTestCase
{
    #[Test]
    public function successContainsPayloadAndProfile(): void
    {
        $payload = new ProfileUpdatePayload(
            profileUid: 123,
            data: ['firstName' => 'Jane'],
        );
        $profile = new Profile();

        $subject = ProfileUpdateRequestResult::success(
            $payload,
            $profile,
        );

        self::assertTrue($subject->isValid());
        self::assertSame($payload, $subject->getPayload());
        self::assertSame($profile, $subject->getProfile());
        self::assertNull($subject->getError());
        self::assertSame(200, $subject->getStatusCode());
    }

    #[Test]
    public function failureContainsOnlyErrorAndStatusCode(): void
    {
        $subject = ProfileUpdateRequestResult::failure(
            'invalid_payload',
            400,
        );

        self::assertFalse($subject->isValid());
        self::assertNull($subject->getPayload());
        self::assertNull($subject->getProfile());
        self::assertSame('invalid_payload', $subject->getError());
        self::assertSame(400, $subject->getStatusCode());
    }
}
