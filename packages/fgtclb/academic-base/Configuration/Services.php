<?php

use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactory;
use FGTCLB\AcademicBase\Environment\EnvironmentBuilderFactoryInterface;
use FGTCLB\AcademicBase\Environment\StateManagerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use TYPO3\CMS\Core\Information\Typo3Version;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (
    ContainerConfigurator $configurator,
    ContainerBuilder $builder,
) {
    $typo3Version = new Typo3Version();
    $majorVersion = $typo3Version->getMajorVersion();

    //==================================================================================================================
    // We retrieve the services class.
    //==================================================================================================================
    $services = $configurator->services();
    //==================================================================================================================

    //==================================================================================================================
    // The default configuration: allow autowire and autoconfigure,
    // no need to make every class public.
    //==================================================================================================================
    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->private(); // "private" is the default and can safely be omitted
    //==================================================================================================================

    //==================================================================================================================
    // Define the location of the PHP sources of our extension.
    // In addition, exclude Extbase models that should never be used via DI.
    //==================================================================================================================
    $services
        ->load('FGTCLB\\AcademicBase\\', __DIR__ . '/../Classes/*')
        ->exclude([
            '../Classes/Domain/Model/*',
        ]);
    //==================================================================================================================

    //==================================================================================================================
    // We need different implementation for some services and interfaces based on the current TYPO3 version,
    // and we use here string concatenation intentionally to mitigate PHPStan complaining here at this point.
    //==================================================================================================================
    // Define core version based services and enable `autoconfigure` and `autowire` again while making them
    // public in the same step. This is required due to used `#[Exclude]` attribute on the service classes
    // to avoid dependency injection issues for services of the other core version during DI build time.
    $coreVersionRelatedBaseNamespace = 'FGTCLB\\AcademicBase\\Core' . $majorVersion . '\\';
    $services
        ->set($coreVersionRelatedBaseNamespace . 'Environment\\StateManager')
        ->autoconfigure()
        ->autowire()
        ->public();
    $services
        ->set($coreVersionRelatedBaseNamespace . 'Environment\\FrontendEnvironmentBuilder')
        ->autoconfigure()
        ->autowire()
        ->public();
    // Set concrete classes for meta factory
    $services
        ->set(EnvironmentBuilderFactory::class)
        ->arg('$frontendEnvironmentBuilder', service($coreVersionRelatedBaseNamespace . 'Environment\\FrontendEnvironmentBuilder'))
        ->autowire()
        ->autoconfigure()
        ->public();
    // Set default services for interfaces
    $services
        ->alias(StateManagerInterface::class, $coreVersionRelatedBaseNamespace . 'Environment\\StateManager')
        ->public();
    $services
        ->alias(EnvironmentBuilderFactoryInterface::class, EnvironmentBuilderFactory::class)
        ->public();
    //------------------------------------------------------------------------------------------------------------------
};
