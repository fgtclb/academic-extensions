<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\DependencyInjection;

use FGTCLB\AcademicBase\Settings\AcademicSettingsFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal and not part of public API.
 */
final class AcademicSettingsCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private readonly string $tagName,
    ) {}

    public function process(ContainerBuilder $container): void
    {
        $factoryDefinition = $container->findDefinition(AcademicSettingsFactory::class);
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceId => $tags) {
            $definition = $container->findDefinition($serviceId);
            foreach ($tags as $tag) {
                $settingsFile = $tag['settingsFileName'] ?? null;
                $packageKey = $tag['packageKey'] ?? null;
                if (!$definition->isAutoconfigured()
                    || $definition->isAbstract()
                    || $definition->getClass() === null
                    || empty($settingsFile)
                    || empty($packageKey)
                ) {
                    continue;
                }
                $factoryDefinition->addMethodCall('addSettingDefinition', [
                    $serviceId,
                    $settingsFile,
                    $packageKey,
                ]);
                $definition
                    ->setFactory([
                        new Reference(AcademicSettingsFactory::class),
                        'get',
                    ])
                    ->setArguments([$serviceId])
                    ->setPublic(true);
            }
        }
    }
}
