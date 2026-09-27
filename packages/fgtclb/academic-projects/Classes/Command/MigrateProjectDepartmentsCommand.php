<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Command;

use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Moves the stored department categories of projects from the type `department`, which
 * `academic_programs` declares too, to `project_department` (ACE-64).
 *
 * A console command rather than an upgrade wizard: every new wizard is another call site
 * of `Install\Updates`, which is removed in TYPO3 v15 while its replacement does not exist
 * on v13 (ACE-294).
 *
 * The value is renamed with plain update statements. DataHandler would need a backend user
 * on the command line and would run every hook of every installed extension for a select
 * value that changes nothing else of the record.
 */
#[AsCommand(
    name: 'academic:projects:department:migrate',
    description: 'Move the department categories of projects to the category type "project_department".',
)]
final class MigrateProjectDepartmentsCommand extends Command
{
    private const FORMER_TYPE = 'department';
    private const TYPE = 'project_department';

    /**
     * The doktype of a program page. `academic_programs` is not a dependency of this
     * extension, so its page type class may not exist.
     */
    private const PROGRAM_DOKTYPE = 20;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'A category of the type "department" moves when every program or project page it is'
            . ' assigned to is a project page, or when academic_programs is not installed. A'
            . ' category on program and project pages, or on none of them, is listed and keeps'
            . ' its type: set it in the backend. Running the command again moves only what is left.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $categories = $this->findDepartmentCategories();
        $programsInstalled = ExtensionManagementUtility::isLoaded('academic_programs');
        $doktypes = $programsInstalled ? $this->findProgramAndProjectDoktypes(array_keys($categories)) : [];

        $categoriesToMove = [];
        $ambiguousCategories = [];
        foreach ($categories as $uid => $title) {
            $assignedTo = $doktypes[$uid] ?? [];
            if (!$programsInstalled || $assignedTo === [PageTypes::TYPE_ACEDEMIC_PROJECT]) {
                $categoriesToMove[] = $uid;
            } elseif ($assignedTo === []) {
                $ambiguousCategories[] = [$uid, $title, 'no program or project page'];
            } elseif ($assignedTo !== [self::PROGRAM_DOKTYPE]) {
                $ambiguousCategories[] = [$uid, $title, 'program and project pages'];
            }
        }

        $this->moveCategories($categoriesToMove);

        $moved = count($categoriesToMove);
        $io->success($moved === 0
            ? 'No category left to move.'
            : sprintf('%d %s moved to "%s".', $moved, $moved === 1 ? 'category' : 'categories', self::TYPE));

        if ($ambiguousCategories !== []) {
            $io->warning(sprintf(
                'These categories keep the type "%s", which is the department of study programs now.'
                . ' Set the type of each one in the backend.',
                self::FORMER_TYPE,
            ));
            $io->table(['uid', 'Title', 'Assigned to'], $ambiguousCategories);
        }

        return Command::SUCCESS;
    }

    /**
     * Categories that are neither a translation nor a workspace version of another one. Those
     * follow the category they belong to.
     *
     * @return array<int, string> Titles by uid, in uid order
     */
    private function findDepartmentCategories(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $result = $queryBuilder
            ->select('uid', 'title')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::FORMER_TYPE)),
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery();

        $categories = [];
        while ($row = $result->fetchAssociative()) {
            $categories[(int)$row['uid']] = (string)$row['title'];
        }
        return $categories;
    }

    /**
     * The program and project doktypes of the pages each category is assigned to. Hidden
     * pages count, deleted ones do not. Pages of other doktypes decide nothing: the type
     * only matters to the program and project lists.
     *
     * @param list<int> $categoryUids
     * @return array<int, list<int>> Doktypes in ascending order, by category uid
     */
    private function findProgramAndProjectDoktypes(array $categoryUids): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        $result = $queryBuilder
            ->select('mm.uid_local', 'p.doktype')
            ->from('sys_category_record_mm', 'mm')
            ->join(
                'mm',
                'pages',
                'p',
                $queryBuilder->expr()->eq('p.uid', $queryBuilder->quoteIdentifier('mm.uid_foreign'))
            )
            ->where(
                $queryBuilder->expr()->in('mm.uid_local', $queryBuilder->quoteArrayBasedValueListToIntegerList($categoryUids)),
                $queryBuilder->expr()->eq('mm.tablenames', $queryBuilder->createNamedParameter('pages')),
                $queryBuilder->expr()->eq('mm.fieldname', $queryBuilder->createNamedParameter('categories')),
                $queryBuilder->expr()->in(
                    'p.doktype',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList([self::PROGRAM_DOKTYPE, PageTypes::TYPE_ACEDEMIC_PROJECT])
                ),
            )
            ->groupBy('mm.uid_local', 'p.doktype')
            ->orderBy('mm.uid_local')
            ->addOrderBy('p.doktype')
            ->executeQuery();

        $doktypes = [];
        while ($row = $result->fetchAssociative()) {
            $doktypes[(int)$row['uid_local']][] = (int)$row['doktype'];
        }
        return $doktypes;
    }

    /**
     * @param list<int> $categoryUids
     */
    private function moveCategories(array $categoryUids): void
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category');
        $uidList = $queryBuilder->quoteArrayBasedValueListToIntegerList($categoryUids);
        $queryBuilder
            ->update('sys_category')
            ->set('type', self::TYPE)
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::FORMER_TYPE)),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->in('uid', $uidList),
                    $queryBuilder->expr()->in('l10n_parent', $uidList),
                    $queryBuilder->expr()->in('t3ver_oid', $uidList),
                ),
            )
            ->executeStatement();
    }
}
