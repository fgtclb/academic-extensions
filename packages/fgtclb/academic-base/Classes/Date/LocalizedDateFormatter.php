<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Date;

use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\Locale;

/**
 * Formats a date for a locale, showing the parts a field publishes.
 *
 * A complete date is formatted with the core keyword `MEDIUMDATE` rather than
 * from the `yMMMd` skeleton: that keyword is what the contract rows and the
 * editor already render, and the skeleton would silently move `14.03.2019` to
 * `14. März 2019` on every existing installation. Anything narrower has no
 * such precedent and is built from the skeleton, so the order and the
 * separators stay the locale's own.
 *
 * `ext-intl` is an unconditional requirement of `typo3/cms-core` on both
 * supported versions, so nothing here guards for it.
 *
 * @internal not part of public API.
 */
final readonly class LocalizedDateFormatter
{
    public function format(\DateTimeInterface $date, DateDisplay $display, Locale|string $locale): string
    {
        if ($display->isEmpty()) {
            return '';
        }
        if ($display->isComplete()) {
            return (new DateFormatter())->format($date, 'MEDIUMDATE', $locale);
        }
        // ICU answers `false` for a skeleton it cannot serve. That is not a
        // reason to render nothing: the whole date is more useful than an empty
        // cell, and it is what an unconfigured field shows anyway.
        $pattern = (new \IntlDatePatternGenerator((string)$locale))->getBestPattern($display->skeleton());
        return (new DateFormatter())->format($date, $pattern === false ? 'MEDIUMDATE' : $pattern, $locale);
    }
}
