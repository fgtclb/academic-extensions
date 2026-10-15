<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Form;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What a frontend form plugin does once it saved a record: whether it returns the visitor
 * to the page of the form, the page it redirects to otherwise, and whether it queues its
 * confirmation as a flash message. {@see AfterSaveResolver::decide()} builds it.
 *
 * `returnsToForm` is set without a redirect page and for a redirect page that is the page
 * of the form. `redirectPageId` keeps the page all the same, so a plugin reads it only
 * when it does not return to the form.
 *
 * @internal for the frontend form plugins of the academic extensions, not part of the public API.
 */
#[Exclude]
final readonly class AfterSaveDecision
{
    public function __construct(
        public ?int $redirectPageId,
        public bool $createFlashMessage,
        public bool $returnsToForm,
    ) {}
}
