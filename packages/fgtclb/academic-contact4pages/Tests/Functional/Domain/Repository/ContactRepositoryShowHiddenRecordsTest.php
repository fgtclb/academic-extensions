<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ContactRepositoryShowHiddenRecordsTest extends AbstractAcademicContacts4PagesTestCase
{
    private function getContactRepository(): ContactRepository
    {
        return $this->get(ContactRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Contact> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $contact) {
            $uids[] = (int)$contact->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findByPidExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryShowHidden/contacts.csv');
        $result = $this->getContactRepository()->findByPid(100);
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByPidIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryShowHidden/contacts.csv');
        $result = $this->getContactRepository()->findByPid(100, true);
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    /**
     * A frontend preview of hidden records includes hidden content in the visibility of
     * the context, and the Extbase query then returns hidden records without the plugin
     * option. The language pre-query of `findByPid()` (ACE-430) follows that visibility,
     * so the preview keeps showing them.
     */
    #[Test]
    public function findByPidIncludesHiddenRecordsInAPreviewOfHiddenRecords(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryShowHidden/contacts.csv');
        // The constructor of TYPO3 v12 takes two arguments, the one of v13 four, and the
        // configuration manager reads the setup only.
        $frontendTypoScript = (new \ReflectionClass(FrontendTypoScript::class))->newInstanceWithoutConstructor();
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray([]);
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.acme.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript);
        GeneralUtility::makeInstance(Context::class)->setAspect('visibility', new VisibilityAspect(false, true));

        $result = $this->getContactRepository()->findByPid(100);

        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }
}
