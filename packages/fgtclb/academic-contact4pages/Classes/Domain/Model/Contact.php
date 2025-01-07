<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Model;

use Fgtclb\AcademicPersons\Domain\Model\Contract;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Contact extends AbstractEntity
{
    protected int $page = 0;

    protected ?Contract $contract = null;

    protected ?Role $role = null;

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
        $label = '';
        if ($this->contract) {
            $label .= $this->contract->getLabel();
        }
        if ($this->role) {
            $label .= ' / ' . $this->role->getName();
        }
        return $label;
    }
}
