<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Domain\Model\Dto;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Interface describing an extbase plugin controller action context container for the bare minimum,
 * using the interface in controller action events instead of a concrete implementation open it up
 * for customization in the future.
 */
interface PluginControllerActionContextInterface
{
    public function getContentObjectRenderer(): ?ContentObjectRenderer;
    public function getRequest(): ServerRequestInterface;
    public function getApplicationType(): ApplicationType;
    public function getSite(): ?Site;
    public function getLanguage(): ?SiteLanguage;
    public function getPluginName(): ?string;
    public function getControllerName(): ?string;
    public function getControllerObjectName(): ?string;
    public function getActionName(): ?string;
    public function getControllerExtensionKey(): ?string;
    public function getControllerExtensionName(): ?string;
    public function getExtbaseRequestParameters(): ?ExtbaseRequestParameters;
    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array;
}
