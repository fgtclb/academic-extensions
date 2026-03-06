<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;

/**
 * @internal to be used only in `EXT:academic_person_edit` and not part of public API. May change at any time.
 */
abstract class AbstractFormData
{
    private ?ServerRequestInterface $request = null;
    private ?string $argumentName = null;

    // =================================================================================================================
    // Magic methods
    // =================================================================================================================

    public function _getProperty(string $propertyName): mixed
    {
        return $this->_hasProperty($propertyName) && isset($this->{$propertyName})
            ? $this->{$propertyName}
            : null;
    }

    public function _hasProperty(string $propertyName): bool
    {
        return property_exists($this, $propertyName);
    }

    final public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    final protected function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    final public function setArgumentName(string $argumentName): void
    {
        $this->argumentName = $argumentName;
    }

    final public function getArgumentName(): ?string
    {
        return $this->argumentName;
    }

    protected function getExtbaseRequestParameters(): ?ExtbaseRequestParameters
    {
        return $this->request?->getAttribute('extbase') ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function getActionRequestArguments(): ?array
    {
        return $this->getExtbaseRequestParameters()?->getArguments() ?? null;
    }

    public function wasPropertySendInRequest(string $propertyName): bool
    {
        $arguments = $this->getActionRequestArguments();
        $argumentName = $this->getArgumentName();
        if ($arguments === null || $argumentName === null || $argumentName === '') {
            return false;
        }
        $namespacedArguments = $arguments[$argumentName] ?? null;
        if (!is_array($namespacedArguments)) {
            return false;
        }
        return array_key_exists($propertyName, $namespacedArguments);
    }
}
