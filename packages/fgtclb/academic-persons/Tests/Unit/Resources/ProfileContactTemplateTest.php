<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Resources;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProfileContactTemplateTest extends TestCase
{
    private const EXTENSION_ROOT = __DIR__ . '/../../..';

    #[Test]
    public function detailRendersDirectProfileContactsSeparatelyFromContractContacts(): void
    {
        $detail = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Templates/Profile/Detail.html',
        );
        $contact = file_get_contents(
            self::EXTENSION_ROOT . '/Resources/Private/Partials/Profile/Contact.html',
        );
        $this->assertIsString($detail);
        $this->assertIsString($contact);

        $this->assertStringContainsString('partial="Profile/Contact"', $detail);
        $this->assertLessThan(
            strpos($detail, '<f:if condition="{profile.contracts}">'),
            strpos($detail, 'partial="Profile/Contact"'),
        );
        $this->assertStringContainsString(
            '{profile.publishEmailAddress} && {profile.emailAddress}',
            $contact,
        );
        $this->assertStringContainsString(
            '{profile.publishPhoneNumber} && {profile.phoneNumber}',
            $contact,
        );
        $this->assertStringContainsString('<f:link.email email="{profile.emailAddress}">', $contact);
        $this->assertStringContainsString('href="tel:{profile.phoneNumber}"', $contact);
        $this->assertStringNotContainsString('contract.', $contact);
    }
}
