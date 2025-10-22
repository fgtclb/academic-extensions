<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Service to map a (nested) array dataset to object properties, either passing as constructor arguments,
 * setter-methods or directly setting public, protected and private properties if no setter/constructor
 * is given as a simplified and side effect free and **working** alternative to `Extbase PropertyMapper`.
 *
 * Not supporting X`Classing or dependency injection of services into the objects is a design decision,
 * because the usage is to populate configuration classes from data sets and are build on well-known
 * `Data Transfer Object` principles **not** containing services. This decision simplifies the required
 * implementation **and** ensures that the system build on this service do not violate these principles.
 *
 * @internal to be used only in `EXT:academic_base` and not part of public API or internal academic API.
 */
#[Autoconfigure(public: true)]
final class ArrayObjectMapper
{
    public function __construct(
        #[Autowire(service: 'academic-base.serializer')]
        private readonly SerializerInterface&DenormalizerInterface $serializer,
    ) {}

    /**
     * @template T of object
     * @param array<string, mixed> $data
     * @param class-string<T> $className name of the class to instantiate, must not be empty and not start with a backslash
     * @return T the created instance
     */
    public function map(array $data, string $className): object
    {
        if (! class_exists($className)) {
            throw new \RuntimeException(
                message: sprintf(
                    'Provided $className "%s" does not exists.',
                    $className,
                ),
                code: 1761108598,
            );
        }
        /** @var T $object Required to keep PHPStan happy */
        $object = $this->serializer->denormalize(
            data: $data,
            type: $className,
        );
        return $object;
    }
}
