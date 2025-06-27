<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\PageTitle;

use TYPO3\CMS\Core\PageTitle\AbstractPageTitleProvider;

class ProfileTitleProvider extends AbstractPageTitleProvider
{
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
