<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\ViewHelpers\Format;

use FGTCLB\AcademicBase\Date\DateDisplay;
use FGTCLB\AcademicBase\Date\LocalizedDateFormatter;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Renders a date for the locale of the site language the page is requested in,
 * showing the parts the caller asks for.
 *
 * `<f:format.date format="d.m.Y">` writes one notation for every visitor of every
 * language, which is wrong for all but the German ones. This ViewHelper hands the date
 * to the shared {@see LocalizedDateFormatter} instead, so the order, the separators and
 * the month name are the locale's own: `01.10.2026` for `de-DE`, `Oct 1, 2026` for
 * `en-US`.
 *
 * `year`, `month` and `day` switch the parts of the date on and off, each on its own,
 * and the remaining ones are formatted the way that locale writes exactly them - a
 * German year and month is `März 2019`, a US English one `Mar 2019`.
 *
 * Example:
 *
 * ```
 *   <b:format.localizedDate date="{job.employmentStartDate}"/>
 *   <b:format.localizedDate date="{item.date}" month="0" day="0"/>
 * ```
 *
 * @internal not part of public API.
 */
class LocalizedDateViewHelper extends AbstractViewHelper
{
    /**
     * The child node evaluates to a `\DateTimeInterface`, which must not be cast to a
     * string before this ViewHelper sees it.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function __construct(
        protected readonly LocalizedDateFormatter $localizedDateFormatter,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument(
            'date',
            'mixed',
            'An object implementing \DateTimeInterface. Anything else renders nothing.',
        );
        $this->registerArgument('year', 'bool', 'Whether the year is shown.', false, true);
        $this->registerArgument('month', 'bool', 'Whether the month is shown.', false, true);
        $this->registerArgument('day', 'bool', 'Whether the day is shown.', false, true);
    }

    public function render(): string
    {
        $date = $this->renderChildren();
        if (!$date instanceof \DateTimeInterface) {
            return '';
        }

        return $this->localizedDateFormatter->format($date, $this->resolveDisplay(), $this->resolveLocale());
    }

    /**
     * Which parts of the date are shown. A subclass that knows a field's own
     * configuration answers from it instead.
     */
    protected function resolveDisplay(): DateDisplay
    {
        return new DateDisplay(
            year: (bool)$this->arguments['year'],
            month: (bool)$this->arguments['month'],
            day: (bool)$this->arguments['day'],
        );
    }

    public function getContentArgumentName(): string
    {
        return 'date';
    }

    /**
     * The locale of the matched site language.
     *
     * This is the first half of what TYPO3 core's own
     * {@see \TYPO3\CMS\Fluid\ViewHelpers\Format\DateViewHelper} does, and
     * deliberately not the second: core additionally falls back to
     * `$GLOBALS['TYPO3_REQUEST']`, gates on the application type and, in the
     * backend, follows the backend user's language. None of that applies here -
     * every consumer of this ViewHelper renders a frontend request, and a
     * rendering without one is a test or a scheduler run, where the system
     * default locale is the honest answer rather than a borrowed one.
     *
     * The rendering context is `?RenderingContextInterface` in Fluid 5, which
     * ships with TYPO3 v14, and untyped in Fluid 4 on v13. It is therefore
     * narrowed with an `instanceof` rather than with a nullsafe call: static
     * analysis reads the property as non-nullable on v13 and rejects a `?->`
     * there, while v14 rejects a bare `->`. The `instanceof` satisfies both and
     * needs no version switch.
     */
    protected function resolveLocale(): Locale
    {
        $renderingContext = $this->renderingContext;
        if (!$renderingContext instanceof RenderingContextInterface
            || !$renderingContext->hasAttribute(ServerRequestInterface::class)
        ) {
            return new Locale();
        }
        $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        if (!$request instanceof ServerRequestInterface) {
            return new Locale();
        }
        $siteLanguage = $request->getAttribute('language');

        return $siteLanguage instanceof SiteLanguage ? $siteLanguage->getLocale() : new Locale();
    }
}
