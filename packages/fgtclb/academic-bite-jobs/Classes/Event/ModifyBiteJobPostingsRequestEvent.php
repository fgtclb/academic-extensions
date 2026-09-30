<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicBiteJobs\Services\BiteJobsService;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatched in {@see BiteJobsService::fetchBiteJobs()}, which the job list asks for its
 * postings, after the request payload is built from the plugin settings and before it is
 * sent to the B-ITE API. The payload a listener hands back is sent as it is, encoded as
 * JSON.
 *
 * The payload the extension builds carries `key`, `channel`, `locale`, `page` (with
 * `offset`), `filter` and `sort` (with `order` and `by`). A listener may change each of them
 * and add any other key the B-ITE API accepts.
 *
 * @api
 */
final class ModifyBiteJobPostingsRequestEvent
{
    /**
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private array $payload,
        private readonly array $settings,
        private readonly ServerRequestInterface $request,
        private readonly ?PluginControllerActionContextInterface $pluginControllerActionContext = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * The values stored below `settings.jobs` in the FlexForm of the content element, which
     * the payload is built from: not merged with TypoScript, and not normalised, so a
     * content element saved with 2.0 still carries its old view value here. The settings the
     * plugin works with, TypoScript included, are those of the plugin action context.
     *
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * The request of the page being rendered.
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * The context of the plugin action that asked for the postings, with the settings of the
     * plugin, or `null` when the service was called outside of a plugin action.
     */
    public function getPluginControllerActionContext(): ?PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
