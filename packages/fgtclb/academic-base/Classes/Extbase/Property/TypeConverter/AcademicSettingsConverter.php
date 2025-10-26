<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Extbase\Property\TypeConverter;

use TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException;
use TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter;

final class AcademicSettingsConverter extends ObjectConverter
{
    /**
     * Builds a new instance of $objectType with the given $possibleConstructorArgumentValues. If
     * constructor argument values are missing from the given array the method looks for a default
     * value in the constructor signature. Furthermore, the constructor arguments are removed from
     * $possibleConstructorArgumentValues: They are considered "handled" by __construct and will
     * not be mapped calling setters later.
     *
     * @param array<string, mixed> $possibleConstructorArgumentValues
     * @param class-string $objectType
     * @return object The created instance
     * @throws InvalidTargetException if a required constructor argument is missing
     */
    protected function buildObject(array &$possibleConstructorArgumentValues, string $objectType): object
    {
        if (empty($possibleConstructorArgumentValues) || !method_exists($objectType, '__construct')) {
            return new $objectType();
        }
        $classSchema = $this->reflectionService->getClassSchema($objectType);
        $constructor = $classSchema->getMethod('__construct');
        $constructorArguments = [];
        foreach ($constructor->getParameters() as $parameterName => $parameter) {
            if (array_key_exists($parameterName, $possibleConstructorArgumentValues)) {
                $constructorArguments[] = $possibleConstructorArgumentValues[$parameterName];
                unset($possibleConstructorArgumentValues[$parameterName]);
            } elseif ($parameter->isOptional()) {
                $constructorArguments[] = $parameter->getDefaultValue();
            } else {
                throw new InvalidTargetException('Missing constructor argument "' . $parameterName . '" for object of type "' . $objectType . '".', 1268734872);
            }
        }
        if ($constructorArguments === []) {
            return new $objectType();
        }
        return new $objectType(...array_values($constructorArguments));
    }
}
