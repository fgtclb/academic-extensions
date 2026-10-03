<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Event;

use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CollectFrontendIconsEventTest extends UnitTestCase
{
    #[Test]
    public function iconsAreReturnedInTheFormatOfTheFile(): void
    {
        $subject = new CollectFrontendIconsEvent();
        $subject->addIcon('example-generated', SvgIconProvider::class, ['source' => 'generated.svg', 'spinning' => true]);

        $this->assertSame(
            ['example-generated' => ['provider' => SvgIconProvider::class, 'source' => 'generated.svg', 'spinning' => true]],
            $subject->getIcons(),
        );
    }

    #[Test]
    public function aLaterIconOfTheSameIdentifierWins(): void
    {
        $subject = new CollectFrontendIconsEvent();
        $subject->addIcon('example-generated', SvgIconProvider::class, ['source' => 'first.svg']);
        $subject->addIcon('example-generated', CurrentColorSvgIconProvider::class, ['source' => 'second.svg']);

        $this->assertSame(
            ['example-generated' => ['provider' => CurrentColorSvgIconProvider::class, 'source' => 'second.svg']],
            $subject->getIcons(),
        );
    }

    #[Test]
    public function aProviderThatIsNotAnIconProviderIsRejectedNamingTheIcon(): void
    {
        $subject = new CollectFrontendIconsEvent();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791061902);
        $this->expectExceptionMessage('"example-generated"');

        // @phpstan-ignore argument.type
        $subject->addIcon('example-generated', \stdClass::class);
    }
}
