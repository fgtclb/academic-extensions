<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use FGTCLB\AcademicProjects\Collection\FilterCollection;
use FGTCLB\AcademicProjects\Domain\Repository\CategoryRepository;
use FGTCLB\AcademicProjects\Domain\Repository\ProjectRepository;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator
        ->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('FGTCLB\\AcademicProjects\\', '../Classes/')
        ->exclude('../Classes/Domain/Model/');

    $services
        ->set(FilterCollection::class)
        ->public();

    $services
        ->set(CategoryRepository::class)
        ->public();

    $services
        ->set(ProjectRepository::class)
        ->public();
};
