<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\ViewHelpers;

use FGTCLB\AcademicBase\Imaging\FrontendIconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders an icon of the frontend icon registry of academic_base, registered in a
 * `Configuration/FrontendIcons.php`. It takes the arguments of `core:icon`, with the
 * same defaults, and renders the same markup for an icon registered with the same
 * provider and options. An identifier the frontend registry does not know renders the
 * `default-not-found` placeholder. The icons of `Configuration/Icons.php` and of TYPO3
 * itself are not read.
 *
 * Usage, with the namespace declared as
 * `xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"`:
 *
 * ::
 *
 *      <ab:icon identifier="my-extension-download" alternativeMarkupIdentifier="inline" />
 *
 * @internal not part of public API. The tag name and its arguments are.
 */
final class IconViewHelper extends AbstractViewHelper
{
    /**
     * The markup comes from the icon providers.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function __construct(
        private readonly FrontendIconFactory $frontendIconFactory,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('identifier', 'string', 'Identifier of the icon as registered in the frontend icon registry of academic_base.', true);
        $this->registerArgument('size', 'string', 'Desired size of the icon. All values of the IconSize enum are allowed, these are: "small", "default", "medium", "large" and "mega".', false, IconSize::SMALL->value);
        $this->registerArgument('overlay', 'string', 'Identifier of an overlay icon as registered in the frontend icon registry of academic_base.');
        $this->registerArgument('state', 'string', 'Sets the state of the icon. All values of the Icons.states enum are allowed, these are: "default" and "disabled".', false, IconState::STATE_DEFAULT->value);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'Alternative icon identifier. Takes precedence over the identifier if supported by the IconProvider.');
        $this->registerArgument('title', 'string', 'Title for the icon');
    }

    public function render(): string
    {
        $icon = $this->frontendIconFactory->getIcon(
            (string)$this->arguments['identifier'],
            IconSize::from((string)$this->arguments['size']),
            $this->arguments['overlay'],
            IconState::tryFrom((string)$this->arguments['state']),
        );
        if ($this->arguments['title'] ?? false) {
            $icon->setTitle($this->arguments['title']);
        }
        return $icon->render($this->arguments['alternativeMarkupIdentifier']);
    }
}
