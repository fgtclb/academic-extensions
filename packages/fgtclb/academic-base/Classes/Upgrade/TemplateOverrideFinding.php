<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

/**
 * One override file and what the upgrade check found out about it.
 *
 * The paths are relative to the folders the check was given, so a finding reads
 * the same whether the folder was named as an `EXT:` path, as an absolute path
 * or through the TypoScript of a site.
 */
final readonly class TemplateOverrideFinding
{
    /**
     * @param string $overridePath The file, relative to the override folder.
     * @param string|null $upstreamPath The file it was compared with, relative to the upstream
     *                                  folder. `null` for {@see TemplateOverrideFindingKind::MissingUpstream},
     *                                  where there is nothing to compare with.
     */
    public function __construct(
        public TemplateOverrideFindingKind $kind,
        public string $overridePath,
        public ?string $upstreamPath = null,
    ) {}
}
