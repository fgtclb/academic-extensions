<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper;

use FGTCLB\AcademicBase\Tests\Functional\Service\AbstractArrayObjectMapperTestCase;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\NonPublicPropertiesWithoutGetterAndSetterAndConstructor;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\PrivatePropertiesOnly;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\PrivatePropertiesOnlyWithSerializedNameAttribute;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\ProtectedConstructorProperties;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\ProtectedPropertiesOnly;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\ProtectedPropertiesOnlyWithSerializedNameAttribute;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\ProtectedReadOnlyConstructorProperties;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\PublicProperties;
use FGTCLB\AcademicBase\Tests\Functional\Service\ArrayObjectMapper\Fixtures\Simple\PublicPropertiesWithSerializedNameAttribute;
use PHPUnit\Framework\Attributes\Test;

final class SimpleObjectTest extends AbstractArrayObjectMapperTestCase
{
    #[Test]
    public function dataWithMatchingPropertyCasingMapsToNonPublicPropertiesWithoutGetterAndSetterAndConstructor(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protectedTypedString' => $expectedProtectedTypedString,
            'protectedTypedBool' => $expectedProtectedTypedBool,
            'protectedTypedInt' => $expectedProtectedTypedInt,
            'protectedTypedFloat' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, NonPublicPropertiesWithoutGetterAndSetterAndConstructor::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(NonPublicPropertiesWithoutGetterAndSetterAndConstructor::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('protectedTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('protectedTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('protectedTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('protectedTypedFloat')->getValue($object));
    }

    #[Test]
    public function dataWithMatchingPropertyCasingMapsToPublicProperties(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protectedTypedString' => $expectedProtectedTypedString,
            'protectedTypedBool' => $expectedProtectedTypedBool,
            'protectedTypedInt' => $expectedProtectedTypedInt,
            'protectedTypedFloat' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, PublicProperties::class);
        $this->assertInstanceOf(PublicProperties::class, $object);
        $this->assertSame($expectedProtectedTypedString, $object->protectedTypedString);
        $this->assertSame($expectedProtectedTypedBool, $object->protectedTypedBool);
        $this->assertSame($expectedProtectedTypedInt, $object->protectedTypedInt);
        $this->assertSame($expectedProtectedTypedFloat, $object->protectedTypedFloat);
    }

    #[Test]
    public function dataWithMatchingSerializedNameAttributeMapsToPublicPropertiesWithSerializedNameAttribute(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protected_typed_string' => $expectedProtectedTypedString,
            'protected_typed_bool' => $expectedProtectedTypedBool,
            'protected_typed_int' => $expectedProtectedTypedInt,
            'protected_typed_float' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, PublicPropertiesWithSerializedNameAttribute::class);
        $this->assertInstanceOf(PublicPropertiesWithSerializedNameAttribute::class, $object);
        $this->assertSame($expectedProtectedTypedString, $object->protectedTypedString);
        $this->assertSame($expectedProtectedTypedBool, $object->protectedTypedBool);
        $this->assertSame($expectedProtectedTypedInt, $object->protectedTypedInt);
        $this->assertSame($expectedProtectedTypedFloat, $object->protectedTypedFloat);
    }

    #[Test]
    public function dataWithMatchingPropertyCasingMapsToProtectedProperties(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protectedTypedString' => $expectedProtectedTypedString,
            'protectedTypedBool' => $expectedProtectedTypedBool,
            'protectedTypedInt' => $expectedProtectedTypedInt,
            'protectedTypedFloat' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, ProtectedPropertiesOnly::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(ProtectedPropertiesOnly::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('protectedTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('protectedTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('protectedTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('protectedTypedFloat')->getValue($object));
    }

    #[Test]
    public function dataWithMatchingSerializedNameAttributeMapsToProtectedPropertiesOnlyWithSerializedNameAttribute(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protected_typed_string' => $expectedProtectedTypedString,
            'protected_typed_bool' => $expectedProtectedTypedBool,
            'protected_typed_int' => $expectedProtectedTypedInt,
            'protected_typed_float' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, ProtectedPropertiesOnlyWithSerializedNameAttribute::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(ProtectedPropertiesOnlyWithSerializedNameAttribute::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('protectedTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('protectedTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('protectedTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('protectedTypedFloat')->getValue($object));
    }

    #[Test]
    public function dataWithMatchingPropertyCasingMapsToPrivateProperties(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'privateTypedString' => $expectedProtectedTypedString,
            'privateTypedBool' => $expectedProtectedTypedBool,
            'privateTypedInt' => $expectedProtectedTypedInt,
            'privateTypedFloat' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, PrivatePropertiesOnly::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(PrivatePropertiesOnly::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('privateTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('privateTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('privateTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('privateTypedFloat')->getValue($object));
    }

    #[Test]
    public function dataWithMatchingSerializedNameAttributeMapsToPrivatePropertiesOnlyWithSerializedNameAttribute(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'private_typed_string' => $expectedProtectedTypedString,
            'private_typed_bool' => $expectedProtectedTypedBool,
            'private_typed_int' => $expectedProtectedTypedInt,
            'private_typed_float' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, PrivatePropertiesOnlyWithSerializedNameAttribute::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(PrivatePropertiesOnlyWithSerializedNameAttribute::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('privateTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('privateTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('privateTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('privateTypedFloat')->getValue($object));
    }

    #[Test]
    public function dataCanBeConstructorMappedToProtectedConstructorProperties(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protectedTypedString' => $expectedProtectedTypedString,
            'protectedTypedBool' => $expectedProtectedTypedBool,
            'protectedTypedInt' => $expectedProtectedTypedInt,
            'protectedTypedFloat' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, ProtectedConstructorProperties::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(ProtectedConstructorProperties::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('protectedTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('protectedTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('protectedTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('protectedTypedFloat')->getValue($object));
    }

    #[Test]
    public function dataCanBeConstructorMappedToProtectedReadOnlyConstructorProperties(): void
    {
        $expectedProtectedTypedString = 'some-string';
        $expectedProtectedTypedBool = true;
        $expectedProtectedTypedInt = 123;
        $expectedProtectedTypedFloat = 987.675;
        $data = [
            'protectedTypedString' => $expectedProtectedTypedString,
            'protectedTypedBool' => $expectedProtectedTypedBool,
            'protectedTypedInt' => $expectedProtectedTypedInt,
            'protectedTypedFloat' => $expectedProtectedTypedFloat,
        ];
        $object = $this->createSubject()->map($data, ProtectedReadOnlyConstructorProperties::class);
        $objectReflection = new \ReflectionObject($object);
        $this->assertInstanceOf(ProtectedReadOnlyConstructorProperties::class, $object);
        $this->assertSame($expectedProtectedTypedString, $objectReflection->getProperty('protectedTypedString')->getValue($object));
        $this->assertSame($expectedProtectedTypedBool, $objectReflection->getProperty('protectedTypedBool')->getValue($object));
        $this->assertSame($expectedProtectedTypedInt, $objectReflection->getProperty('protectedTypedInt')->getValue($object));
        $this->assertSame($expectedProtectedTypedFloat, $objectReflection->getProperty('protectedTypedFloat')->getValue($object));
    }
}
