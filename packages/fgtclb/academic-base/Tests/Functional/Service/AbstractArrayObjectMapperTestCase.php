<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service;

use FGTCLB\AcademicBase\Service\ArrayObjectMapper;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractArrayObjectMapperTestCase extends AbstractAcademicBaseTestCase
{
    final protected function createSubject(): ArrayObjectMapper
    {
        return $this->getContainer()->get(ArrayObjectMapper::class);
    }

    final protected function createSubjectWithGeneralUtility(): ArrayObjectMapper
    {
        return GeneralUtility::makeInstance(ArrayObjectMapper::class);
    }
}
