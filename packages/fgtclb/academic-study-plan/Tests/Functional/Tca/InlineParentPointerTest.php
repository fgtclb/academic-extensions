<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Tca;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\InlineParentPointerAssertionTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * The inline children of this extension keep their parent when it is edited in a
 * workspace and published (ACE-907), see {@see InlineParentPointerAssertionTrait}.
 *
 * Both pointers are `passthrough` and no relation to TYPO3, so they keep their parent
 * already. The test fails as soon as one of them becomes a relation without naming its
 * parent table in `dontRemapTablesOnCopy`.
 */
final class InlineParentPointerTest extends AbstractAcademicStudyPlanTestCase
{
    use InlineParentPointerAssertionTrait;

    #[Test]
    public function inlineChildrenKeepTheLiveParent(): void
    {
        $this->assertInlineParentPointersKeepTheLiveParent(['tx_academicstudyplan_'], [
            'tt_content.tx_academicstudyplan_semesters -> tx_academicstudyplan_domain_model_semester.content_element',
            'tx_academicstudyplan_domain_model_semester.modules -> tx_academicstudyplan_domain_model_module.semester',
        ]);
    }
}
