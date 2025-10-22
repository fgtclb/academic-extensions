<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Factory;

use FGTCLB\AcademicBase\Factory\SerializerFactory;
use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;

final class SerializerFactoryTest extends AbstractAcademicBaseTestCase
{
    #[Test]
    public function factoryCreatesInstance(): void
    {
        (new SerializerFactory())();
    }
}
