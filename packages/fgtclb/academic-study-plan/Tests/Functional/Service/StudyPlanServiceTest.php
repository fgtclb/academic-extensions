<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Service;

use FGTCLB\AcademicStudyPlan\Service\StudyPlanService;
use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class StudyPlanServiceTest extends AbstractAcademicStudyPlanTestCase
{
    public static function getTableHiddenFieldNameDataSets(): \Generator
    {
        $sets = [
            // Extension
            'tx_academicstudyplan_domain_model_category' => 'hidden',
            'tx_academicstudyplan_domain_model_module' => 'hidden',
            'tx_academicstudyplan_domain_model_semester' => 'hidden',
            // TYPO3 Core Cross checks
            'pages' => 'hidden',
            'tt_content' => 'hidden',
            'sys_log' => null,
        ];
        $c = 0;
        foreach ($sets as $tableName => $expected) {
            $c++;
            $label = sprintf(
                '#%s "%s": %s',
                $c,
                $tableName,
                ($expected !== null ? '"' . $tableName . '"' : 'NULL'),
            );
            yield $label => [
                'tableName' => $tableName,
                'expected' => $expected,
            ];
        }
    }

    #[DataProvider('getTableHiddenFieldNameDataSets')]
    #[Test]
    public function getTableHiddenFieldNameReturnsExpectedColumnName(string $tableName, ?string $expected): void
    {
        $subject = $this->get(StudyPlanService::class);
        $invoker = new \ReflectionMethod($subject, 'getTableHiddenFieldName');
        $this->assertSame($expected, $invoker->invoke($subject, $tableName));
    }

    public static function getTableDeletedFieldNameDataSets(): \Generator
    {
        $sets = [
            // Extension
            'tx_academicstudyplan_domain_model_category' => 'deleted',
            'tx_academicstudyplan_domain_model_module' => 'deleted',
            'tx_academicstudyplan_domain_model_semester' => 'deleted',
            // TYPO3 Core Cross checks
            'pages' => 'deleted',
            'tt_content' => 'deleted',
            'sys_log' => null,
        ];
        $c = 0;
        foreach ($sets as $tableName => $expected) {
            $c++;
            $label = sprintf(
                '#%s "%s": %s',
                $c,
                $tableName,
                ($expected !== null ? '"' . $tableName . '"' : 'NULL'),
            );
            yield $label => [
                'tableName' => $tableName,
                'expected' => $expected,
            ];
        }
    }

    #[DataProvider('getTableDeletedFieldNameDataSets')]
    #[Test]
    public function getTableDeletedFieldNameReturnsExpectedColumnName(string $tableName, ?string $expected): void
    {
        $subject = $this->get(StudyPlanService::class);
        $invoker = new \ReflectionMethod($subject, 'getTableDeletedFieldName');
        $this->assertSame($expected, $invoker->invoke($subject, $tableName));
    }

    public static function getTableLanguageFieldNameDataSets(): \Generator
    {
        $sets = [
            // Extension
            'tx_academicstudyplan_domain_model_category' => 'sys_language_uid',
            'tx_academicstudyplan_domain_model_module' => 'sys_language_uid',
            'tx_academicstudyplan_domain_model_semester' => 'sys_language_uid',
            // TYPO3 Core Cross checks
            'pages' => 'sys_language_uid',
            'tt_content' => 'sys_language_uid',
            'sys_log' => null,
        ];
        $c = 0;
        foreach ($sets as $tableName => $expected) {
            $c++;
            $label = sprintf(
                '#%s "%s": %s',
                $c,
                $tableName,
                ($expected !== null ? '"' . $tableName . '"' : 'NULL'),
            );
            yield $label => [
                'tableName' => $tableName,
                'expected' => $expected,
            ];
        }
    }

    #[DataProvider('getTableLanguageFieldNameDataSets')]
    #[Test]
    public function getTableLanguageFieldNameReturnsExpectedColumnName(string $tableName, ?string $expected): void
    {
        $subject = $this->get(StudyPlanService::class);
        $invoker = new \ReflectionMethod($subject, 'getTableLanguageFieldName');
        $this->assertSame($expected, $invoker->invoke($subject, $tableName));
    }
}
