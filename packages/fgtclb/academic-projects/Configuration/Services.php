<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('FGTCLB\\AcademicProjects\\', '../Classes/')
        ->exclude('../Classes/Domain/Model/');
};
