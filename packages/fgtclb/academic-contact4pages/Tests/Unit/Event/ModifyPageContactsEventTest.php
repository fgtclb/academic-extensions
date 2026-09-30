<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Unit\Event;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Model\Role;
use FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent;
use FGTCLB\AcademicContacts4pages\Event\PageContactsOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The list a listener hands back is grouped into roles and contacts without role and
 * rendered as it is, so the setter is the one place that can refuse what the templates
 * cannot render.
 */
final class ModifyPageContactsEventTest extends UnitTestCase
{
    private function subject(): ModifyPageContactsEvent
    {
        return new ModifyPageContactsEvent(
            [new Contact()],
            2,
            PageContactsOutput::DataProcessor,
            new ServerRequest('https://www.acme.com/'),
        );
    }

    #[Test]
    public function setContactsReplacesTheContacts(): void
    {
        $subject = $this->subject();
        $contacts = [new Contact(), new Contact()];

        $subject->setContacts($contacts);

        $this->assertSame($contacts, $subject->getContacts());
    }

    #[Test]
    public function setContactsAcceptsAnEmptyList(): void
    {
        $subject = $this->subject();

        $subject->setContacts([]);

        $this->assertSame([], $subject->getContacts());
    }

    /**
     * @return \Generator<string, array{array<mixed>, int}>
     */
    public static function invalidContactsDataProvider(): \Generator
    {
        yield 'a map instead of a list' => [['first' => new Contact()], 1790767001];
        yield 'a list with a gap' => [[0 => new Contact(), 2 => new Contact()], 1790767001];
        yield 'a role among the contacts' => [[new Contact(), new Role()], 1790767002];
        yield 'a uid among the contacts' => [[new Contact(), 5], 1790767002];
    }

    /**
     * @param array<mixed> $contacts
     */
    #[DataProvider('invalidContactsDataProvider')]
    #[Test]
    public function setContactsRejectsAnythingButAListOfContacts(array $contacts, int $expectedCode): void
    {
        $subject = $this->subject();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode($expectedCode);

        $subject->setContacts($contacts);
    }

    #[Test]
    public function aRejectedListLeavesTheContactsUnchanged(): void
    {
        $subject = $this->subject();
        $contacts = $subject->getContacts();

        try {
            // @phpstan-ignore argument.type
            $subject->setContacts([new Role()]);
        } catch (\InvalidArgumentException) {
        }

        $this->assertSame($contacts, $subject->getContacts());
    }
}
