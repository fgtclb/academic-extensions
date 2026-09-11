<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Event;

use FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyFrontendIconAllowListEventTest extends UnitTestCase
{
    #[Test]
    public function addsAPrefixOnce(): void
    {
        $event = new ModifyFrontendIconAllowListEvent(['tx-academic']);

        $event->addPrefix('tx-mysitepackage-');
        $event->addPrefix('tx-mysitepackage-');

        $this->assertSame(['tx-academic', 'tx-mysitepackage-'], $event->getPrefixes());
    }

    #[Test]
    public function replacesThePrefixesAsAList(): void
    {
        $event = new ModifyFrontendIconAllowListEvent(['tx-academic']);

        $event->setPrefixes(['a' => 'tx-one-', 'b' => 'tx-two-']);

        $this->assertSame(['tx-one-', 'tx-two-'], $event->getPrefixes());
    }

    /**
     * A listener passing something else fails where it does so, not with a TypeError
     * on every frontend request that renders an icon.
     */
    #[Test]
    public function refusesAPrefixThatIsNotAString(): void
    {
        $event = new ModifyFrontendIconAllowListEvent(['tx-academic']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1789120002);

        $event->setPrefixes(['tx-academic', 42]);
    }
}
