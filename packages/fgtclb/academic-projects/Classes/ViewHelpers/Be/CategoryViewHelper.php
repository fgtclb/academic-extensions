<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\ViewHelpers\Be;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use FGTCLB\AcademicProjects\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicProjects\Enumeration\CategoryTypes;
use TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CategoryViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument(
            'page',
            'int',
            'Page ID',
            true
        );

        $this->registerArgument(
            'type',
            'string',
            'The type, none given returns all in associative array'
        );

        $this->registerArgument(
            'as',
            'string',
            'The variable name',
            false,
            'projectCategory'
        );
    }

    /**
     * @param array{
     *     page: int,
     *     type: string,
     *     as: string
     * } $arguments
     * @throws DBALException
     * @throws Exception
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $templateVariableContainer = $renderingContext->getVariableProvider();

        $repository = GeneralUtility::makeInstance(CategoryRepository::class);
        try {
            $categoryType = CategoryTypes::cast($arguments['type']);
            $attributes = $repository->findByType($arguments['page'], $categoryType);
        } catch (InvalidEnumerationValueException) {
            $attributes = $repository->findAllByPageId($arguments['page']);
        }

        $templateVariableContainer->add($arguments['as'], $attributes);

        $output = $renderChildrenClosure();

        $templateVariableContainer->remove($arguments['as']);

        return $output;
    }
}
