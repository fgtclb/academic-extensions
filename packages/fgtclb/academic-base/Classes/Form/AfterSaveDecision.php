<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Form;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * What a frontend form plugin does once it saved a record: the page it redirects to,
 * null to stay with the plugin, and whether it queues its confirmation as a flash
 * message. {@see AfterSaveResolver::decide()} builds it.
 *
 * @internal for the frontend form plugins of the academic extensions, not part of the public API.
 */
#[Exclude]
final readonly class AfterSaveDecision
{
    public function __construct(
        public ?int $redirectPageId,
        public bool $createFlashMessage,
    ) {}
}
