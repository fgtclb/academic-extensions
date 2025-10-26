<?php

declare(strict_types=1);

namespace TYPO3\CMS\Install;

use FGTCLB\AcademicBase\DependencyInjection\AcademicSettingsCompilerPass;
use FGTCLB\AcademicBase\Settings\AsAcademicExtensionSetting;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        AsAcademicExtensionSetting::class,
        static function (ChildDefinition $definition, AsAcademicExtensionSetting $attribute): void {
            $definition->addTag(AsAcademicExtensionSetting::TAG_NAME, [
                'settingsFileName' => $attribute->settingsFileName,
                'packageKey' => $attribute->packageKey,
            ]);
        }
    );
    $containerBuilder->addCompilerPass(new AcademicSettingsCompilerPass(AsAcademicExtensionSetting::TAG_NAME));
};
