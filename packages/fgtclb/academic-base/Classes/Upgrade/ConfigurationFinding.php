<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * One piece of stored configuration that no longer reaches the academic
 * extensions, or that reaches them twice.
 *
 * `subject` names where the configuration is stored - a record, a page or a
 * site - and is what the status report shows as the value of its entry;
 * `message` says what is wrong with it and what to do about it.
 */
final readonly class ConfigurationFinding
{
    public function __construct(
        public ConfigurationFindingKind $kind,
        public ContextualFeedbackSeverity $severity,
        public string $subject,
        public string $message,
    ) {}

    /**
     * Whether the finding makes the upgrade check command fail. A notice
     * describes configuration that still works.
     */
    public function isProblem(): bool
    {
        return $this->severity->value >= ContextualFeedbackSeverity::WARNING->value;
    }
}
