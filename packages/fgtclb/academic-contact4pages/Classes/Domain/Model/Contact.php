<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Model;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Contact extends AbstractEntity
{
    protected int $page = 0;
    protected ?Contract $contract = null;
    protected ?Role $role = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getContract(): ?Contract
    {
        return $this->contract;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function getLabel(): string
    {
        $labelParts = [];
        if ($this->role) {
            $labelParts[] = $this->role->getName();
        }
        if ($this->contract) {
            $labelParts[] = $this->contract->getLabel();
        }
        return implode(' - ', $labelParts);
    }
}
