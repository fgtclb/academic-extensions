<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Factory;

use FGTCLB\AcademicBase\Service\ArrayObjectMapper;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\PropertyInfo\Extractor\ConstructorExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Factory to create default serializer service used only in {@see ArrayObjectMapper}.
 *
 * This factory **must** not used directly, and it's sole purpose is to be used in
 * `EXT:academic_base/Configuration/Services.yaml` to create the required specific
 *  `Serializer` instance for {@see ArrayObjectMapper}.
 *
 * @internal to be used only in `EXT:academic_base` and not part of public API or internal academic API.
 */
#[Autoconfigure(public: true)]
final class SerializerFactory
{
    public function __invoke(): Serializer
    {
        $defaultContext = [
            // @todo Investigate and check if we need to configure default context for normalizer/encoders not matching
            //       `symfony/serializer` defaults to make it work for the `EXT:academic_base` internal use-case.
        ];
        $classMetadataFactory = $this->classMetadataFactory();
        $metadataAwareNameConverter = new MetadataAwareNameConverter(
            metadataFactory: $classMetadataFactory,
        );
        /** @var iterable<PropertyInfoExtractor> Required to keep PHPStan happy */
        $typeExtractors = [
            new ConstructorExtractor(),
            new PhpDocExtractor(),
            new ReflectionExtractor(),
            new SerializerExtractor(
                classMetadataFactory: $classMetadataFactory,
            ),
        ];
        $propertyTypeExtractor = new PropertyInfoExtractor(
            typeExtractors: $typeExtractors,
        );
        return new Serializer(
            normalizers: $this->normalizers(
                classMetadataFactory: $classMetadataFactory,
                propertyTypeExtractor: $propertyTypeExtractor,
                nameConverter: $metadataAwareNameConverter,
                defaultContext: $defaultContext,
            ),
            encoders: $this->encoders(
                defaultContext: $defaultContext,
            ),
            defaultContext: $defaultContext,
        );
    }

    private function classMetadataFactory(): ClassMetadataFactory
    {
        return new ClassMetadataFactory(
            loader: new LoaderChain(
                loaders: $this->classMetadataLoaders(),
            ),
        );
    }

    /**
     * @param array<string, mixed> $defaultContext
     * @return array<int, NormalizerInterface>
     */
    private function normalizers(
        ClassMetadataFactoryInterface $classMetadataFactory,
        PropertyTypeExtractorInterface $propertyTypeExtractor,
        NameConverterInterface $nameConverter,
        array $defaultContext,
    ): array {
        $normalizers = [
            new BackedEnumNormalizer(),
            // @todo Added as general handling, needs investigation and decision if we really need this normalizer here.
            new ArrayDenormalizer(),
            // We need to add the `PropertyNormalizer` here before `ObjectNormalizer`, which should literally
            // do the same but would not allow simple classes with private properties and missing setter and
            // constructor arguments to set these properties.
            new DateTimeNormalizer(
                defaultContext: $defaultContext,
            ),
            new PropertyNormalizer(
                classMetadataFactory: $classMetadataFactory,
                nameConverter: $nameConverter,
                propertyTypeExtractor: $propertyTypeExtractor,
                defaultContext: $defaultContext,
            ),
            // @todo Consider to implement a custom normalizer to for `Collection` like classes to shift a direct
            //       array to a (php-attribute) configured constructor argument to make readonly collection classes
            //       possible.
            new ObjectNormalizer(
                classMetadataFactory: $classMetadataFactory,
                nameConverter: $nameConverter,
                propertyTypeExtractor: $propertyTypeExtractor,
                defaultContext: $defaultContext,
            ),
        ];
        /** @var array<int, NormalizerInterface> $normalizers Required to keep PHPStan happy */
        return $normalizers;
    }

    /**
     * @param array<string, mixed> $defaultContext
     * @return EncoderInterface[]
     * @todo Currently only the {@see DenormalizableInterface::denormalize()} interface and method is used in
     *       {@see ArrayObjectMapper::map()} and encoders are not required, but we keep them meanwhile until
     *       further investigation has been processed. **Could** be inlined as empty array at the calling place.
     */
    private function encoders(
        array $defaultContext,
    ): array {
        return [
            new XmlEncoder(
                defaultContext: $defaultContext,
            ),
            new JsonEncoder(
                defaultContext: $defaultContext,
            ),
            new YamlEncoder(
                defaultContext: $defaultContext,
            ),
        ];
    }

    /**
     * @return LoaderInterface[]
     */
    private function classMetadataLoaders(): array
    {
        return [
            // `symfony/serializer:^6.4` still provides the deprecated `AnnotationLoader()`, which would not
            // be available with `symfony/serializer:^7` and due to dual version support because of lower
            // PHP version needed to be supported we leave it out here. We can safely rely only on native PHP
            // attributes for the internal `EXT:academic_base` / `EXT:academic_*`-extensions and use strictly
            // typed properties and methods.
            new AttributeLoader(),
            // @todo Investigate and consider to add `YamlFileLoader` and/or `XmlFileLoader` as additional
            //       loader to allow using cached / warmup files or non-php code definitions. Needs to be
            //       investigated if this is a) needed and b) if we want to support this within the internal
            //       usage.
        ];
    }
}
