<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileUpdatePayload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileUpdatePayloadTest extends UnitTestCase
{
    #[Test]
    public function constructorDataCanBeReadWithoutModification(): void
    {
        $data = [
            'firstName' => 'Jane',
            'website' => '',
            'skipSync' => false,
            'customValue' => null,
        ];

        $subject = new ProfileUpdatePayload(
            profileUid: 123,
            data: $data,
        );

        self::assertSame(123, $subject->getProfileUid());
        self::assertSame($data, $subject->getData());
    }

    #[Test]
    #[DataProvider('propertyValues')]
    public function presentPropertyCanBeDistinguishedFromMissingProperty(
        mixed $value,
    ): void {
        $subject = new ProfileUpdatePayload(
            profileUid: 123,
            data: ['property' => $value],
        );

        self::assertTrue($subject->hasProperty('property'));
        self::assertSame($value, $subject->getProperty('property'));
        self::assertFalse($subject->hasProperty('missing'));
        self::assertNull($subject->getProperty('missing'));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function propertyValues(): array
    {
        return [
            'non-empty string' => ['value'],
            'empty string' => [''],
            'false' => [false],
            'zero' => [0],
            'null' => [null],
            'array' => [['nested' => 'value']],
        ];
    }
}
