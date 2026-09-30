<?php

declare(strict_types=1);

namespace TESTS\TestPageContactsListener\EventListener;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;

/**
 * Removes one contact of a page, driven by TypoScript so that one fixture extension serves
 * every scenario: a test includes the TypoScript file of the behaviour it wants, and the
 * listener stays inert for every other test of the same class.
 *
 * Two sources name the contact. The setting `testRemovePageContact` of a content element,
 * stored in its FlexForm and read from the plugin context, acts for that content element
 * alone. The setup below `plugin.tx_testpagecontactslistener` is read from the request,
 * which both outputs carry, and acts for both unless `onlyForOutput` names one of them.
 */
final class RemovePageContact
{
    #[AsEventListener(identifier: 'test-page-contacts-listener/remove-contact')]
    public function __invoke(ModifyPageContactsEvent $event): void
    {
        $uid = (int)($event->getPluginControllerActionContext()?->getSettings()['testRemovePageContact'] ?? 0);
        if ($uid === 0) {
            $frontendTypoScript = $event->getRequest()->getAttribute('frontend.typoscript');
            $setup = $frontendTypoScript instanceof FrontendTypoScript
                ? ($frontendTypoScript->getSetupArray()['plugin.']['tx_testpagecontactslistener.'] ?? [])
                : [];
            $onlyForOutput = (string)($setup['onlyForOutput'] ?? '');
            if ($onlyForOutput !== '' && $onlyForOutput !== $event->getOutput()->value) {
                return;
            }
            $uid = (int)($setup['removeContact'] ?? 0);
        }
        if ($uid === 0) {
            return;
        }

        $event->setContacts(array_values(array_filter(
            $event->getContacts(),
            static fn(Contact $contact): bool => $contact->getUid() !== $uid,
        )));
    }
}
