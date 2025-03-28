<?php

declare(strict_types=1);

namespace FGTCLB\CategoryTypes\Tests\Unit\Collection;

use PHPUnit\Framework\Attributes\Test;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CategoryCollectionTest extends UnitTestCase
{
    #[Test]
    public function canBeCreatedUsingNew(): void
    {
        $subject = new CategoryCollection();
        $this->assertInstanceOf(CategoryCollection::class, $subject);
    }
}
