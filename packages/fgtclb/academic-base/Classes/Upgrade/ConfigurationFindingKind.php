<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

/**
 * The kinds of stale configuration {@see ConfigurationChecker} reports.
 *
 * The value is what the command prints and what a pipeline greps for, so it is
 * part of the public behaviour of the command and does not change silently.
 */
enum ConfigurationFindingKind: string
{
    /**
     * A `sys_template` record includes a static template of an academic
     * extension that delivers no TypoScript.
     */
    case StaticTemplate = 'static-template';

    /**
     * A page, or a site, references an academic page TSconfig file that does
     * not exist.
     */
    case TsConfigImport = 'tsconfig-import';

    /**
     * A page, or a site, references an academic page TSconfig file with the
     * `<INCLUDE_TYPOSCRIPT:` syntax, which TYPO3 v14 no longer reads.
     */
    case TsConfigSyntax = 'tsconfig-syntax';

    /**
     * A site depends on an alias set that only forwards to another one.
     */
    case AliasSet = 'alias-set';

    /**
     * A site delivers one extension through a set and through a static
     * template at the same time.
     */
    case SetAndStaticTemplate = 'set-and-static-template';

    /**
     * An academic class is replaced through the XCLASS registry.
     */
    case Xclass = 'xclass';

    /**
     * The title the status report shows for a finding of this kind. The report
     * groups by it, so one title per kind is what makes the entries readable.
     */
    public function title(): string
    {
        return match ($this) {
            self::StaticTemplate => 'Static template',
            self::TsConfigImport => 'Page TSconfig import',
            self::TsConfigSyntax => 'Page TSconfig syntax',
            self::AliasSet => 'Alias set',
            self::SetAndStaticTemplate => 'Set and static template',
            self::Xclass => 'XCLASS',
        };
    }
}
