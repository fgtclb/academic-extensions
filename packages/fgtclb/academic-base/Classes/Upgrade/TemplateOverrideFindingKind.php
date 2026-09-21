<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

/**
 * What the upgrade check found out about one override file.
 *
 * Two of the three are problems and let the command exit with a failure status;
 * `Identical` is a notice, because a deliberate copy that pins the markup of one
 * file must not fail a pipeline.
 */
enum TemplateOverrideFindingKind: string
{
    /**
     * The extension ships no file at that relative path, not even one that
     * differs in case. Fluid never resolves the override.
     */
    case MissingUpstream = 'missing-upstream';

    /**
     * The extension ships the file under a name that differs only in case.
     * Such an override resolves on a case insensitive file system - a macOS
     * development machine - and is dead on the Linux server next to it.
     */
    case CaseMismatch = 'case-mismatch';

    /**
     * The override is a byte identical copy. It renders, and it freezes the
     * upstream markup of that one file: the next release changes nothing about
     * it.
     */
    case Identical = 'identical';

    /**
     * Whether this finding makes the command exit with a failure status.
     */
    public function isProblem(): bool
    {
        return $this !== self::Identical;
    }
}
