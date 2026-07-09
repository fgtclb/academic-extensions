<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Environment;

use FGTCLB\AcademicBase\Environment\Exception\NoTypo3VersionCompatibleEnvironmentBuilderFound;

use FGTCLB\EnvironmentStateManager\StateBuildContext;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * Default environment builder factory implementation for {@see EnvironmentBuilderFactoryInterface}.
 *
 * @deprecated since academic_base 2.4.0, will be removed in academic_base 3.0.0.
 *             Use the fgtclb/environment-state-manager extension instead
 *             (namespace FGTCLB\EnvironmentStateManager).
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Exclude]
final class EnvironmentBuilderFactory implements EnvironmentBuilderFactoryInterface
{
    public function __construct(
        private readonly EnvironmentBuilderInterface $frontendEnvironmentBuilder,
    ) {}

    /**
     * @throws NoTypo3VersionCompatibleEnvironmentBuilderFound
     */
    public function create(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface
    {
        return match ($stateBuildContext->applicationType) {
            ApplicationType::FRONTEND => $this->frontendEnvironmentBuilder,
            ApplicationType::BACKEND => $this->notImplemented($stateBuildContext),
            // ApplicationType only has 2 cases, so no default branch is required. Omitting it makes PHPStan happy.
        };
    }

    private function notImplemented(StateBuildContext $stateBuildContext): EnvironmentBuilderInterface
    {
        throw new \RuntimeException(
            sprintf(
                'Not implemented yet for applicationType "%s".',
                $stateBuildContext->applicationType->value,
            ),
            1762256802,
        );
    }
}
