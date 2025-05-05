<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Model\Dto;

class AbstractFormData
{
    /**
     * -------------------------------------------------------------------------
     * Magic methods
     * -------------------------------------------------------------------------
     */
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
}
