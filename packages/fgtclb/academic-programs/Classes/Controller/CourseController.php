<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\EducationalCourse\Domain\Collection\CourseCollection;
use FGTCLB\EducationalCourse\Domain\Model\Dto\CourseFilter;
use FGTCLB\EducationalCourse\Domain\Repository\CourseCategoryRepository;
use FGTCLB\EducationalCourse\Exception\Domain\CategoryExistException;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CourseController extends ActionController
{
    public function __construct(
        protected readonly CourseCategoryRepository $categoryRepository
    ) {
    }

    /**
     * @throws Exception
     * @throws DBALException
     * @throws CategoryExistException
     * @throws FileDoesNotExistException
     */
    public function listAction(?CourseFilter $filter = null): ResponseInterface
    {
        $sorting = $this->settings['sorting'] ?? 'title asc';
        if ($filter === null) {
            if ((int)$this->settings['categories'] > 0) {
                if ($this->configurationManager->getContentObject() !== null) {
                    $uid = $this->configurationManager->getContentObject()->data['uid'];
                    $filterCategories = $this->categoryRepository->getByDatabaseFields($uid);
                    $filter = CourseFilter::createByCategoryCollection($filterCategories);
                }
            }
        }
        $filter ??= CourseFilter::createEmpty();
        $courses = CourseCollection::getByFilter(
            $filter,
            GeneralUtility::intExplode(
                ',',
                $this->configurationManager->getContentObject()
                    ? $this->configurationManager->getContentObject()->data['pages']
                    : []
            ),
            $sorting
        );
        $categories = $this->categoryRepository->findAll();

        $assignedValues = [
            'courses' => $courses,
            'filter' => $filter,
            'categories' => $categories,
        ];
        $this->view->assignMultiple($assignedValues);

        return $this->htmlResponse();
    }
}
