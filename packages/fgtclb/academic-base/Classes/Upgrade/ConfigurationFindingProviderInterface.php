<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Upgrade;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Hands findings of another academic extension to {@see ConfigurationChecker}.
 *
 * `academic_base` cannot read what another extension configures, the persons
 * settings for one. An extension that can tell configuration of its own is lost
 * reports it through a provider instead: every service implementing this
 * interface is tagged by autoconfiguration, and the checker adds the findings of
 * every provider to its own. The command and the status report entry of the
 * upgrade check show them like any other finding.
 *
 * A provider only reads and keeps nothing between two calls.
 *
 * @internal not part of public API. It exists for the academic extensions and is
 *           no extension point.
 */
#[AutoconfigureTag(self::TAG_NAME)]
interface ConfigurationFindingProviderInterface
{
    public const TAG_NAME = 'academic.upgrade.configuration-finding-provider';

    /**
     * @return list<ConfigurationFinding>
     */
    public function findings(): array;
}
