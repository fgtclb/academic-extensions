<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// The one class this package ships, the data processor of the icon overview
// page, is registered through its own attribute. This file only loads it.
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->load('FGTCLB\\AcademicsDevSite\\', __DIR__ . '/../Classes/*');
};
