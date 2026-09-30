<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\DataProcessing;

use FGTCLB\AcademicContacts4pages\Event\PageContactsOutput;
use FGTCLB\AcademicContacts4pages\Service\PageContactsProvider;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Assigns the contacts of a page, the roles they carry and the contacts without a role to a
 * page template.
 *
 * Which contacts those are is decided by `PageContactsProvider`, the very service the
 * contacts content element asks - so a contact without a visible person is left out here
 * exactly as it is there, and a listener of `ModifyPageContactsEvent` reaches both.
 *
 * Options, each read through stdWrap:
 *
 * - `as`: the variable that holds `contacts`, `roles` and `contactsWithoutRole`. Without it
 *   the three are written at the top level.
 * - `showHiddenRecords`: shows hidden contacts and hidden address records, as the option of
 *   the same name of the content element does. Off by default.
 * - `pageUid`: the page whose contacts are read. By default the page that is rendered, and
 *   then the processor does nothing when it is attached to an object whose current record
 *   is not a page, a content element for example.
 *
 * The shipped TypoScript attaches the processor by its identifier `academic-page-contacts`,
 * which `Configuration/Services.yaml` tags this service with. The service is published
 * (`public: true`) as well, because installations name it by class name in their own
 * TypoScript: TYPO3 takes such an entry from the service container when it knows the name
 * and instantiates the class itself otherwise, and only the container way passes the
 * provider to the constructor.
 *
 * The class stays open, and `process()` keeps its signature without a return type, because
 * the 3.0 changelog describes how a project subclasses it. `ModifyPageContactsEvent` is the
 * way to change the contacts without a subclass.
 *
 * @api
 */
#[Autoconfigure(public: true)]
class ContactsProcessor implements DataProcessorInterface
{
    public function __construct(
        private readonly PageContactsProvider $pageContactsProvider,
    ) {}

    /**
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $pageUid = (int)$cObj->stdWrapValue('pageUid', $processorConfiguration, 0);
        if ($pageUid <= 0) {
            [$currentRecordTable, $currentRecordUid] = explode(':', $cObj->currentRecord) + [1 => ''];
            if ($currentRecordTable !== 'pages') {
                return $processedData;
            }
            $pageUid = (int)$currentRecordUid;
        }

        $pageContacts = $this->pageContactsProvider->get(
            $pageUid,
            (bool)$cObj->stdWrapValue('showHiddenRecords', $processorConfiguration, false),
            PageContactsOutput::DataProcessor,
            $cObj->getRequest(),
        );

        $variables = [
            'contacts' => $pageContacts->contacts,
            'roles' => $pageContacts->roles,
            'contactsWithoutRole' => $pageContacts->contactsWithoutRole,
        ];
        $targetVariableName = (string)$cObj->stdWrapValue('as', $processorConfiguration, '');
        if ($targetVariableName !== '') {
            $processedData[$targetVariableName] = $variables;
            return $processedData;
        }

        return array_merge($processedData, $variables);
    }
}
