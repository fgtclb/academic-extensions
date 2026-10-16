<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\EventListener;

use FGTCLB\AcademicBase\EventListener\DropInlineEventHandlerHashesWhereInlineIsAllowed;
use FGTCLB\AcademicBase\Security\InlineEventHandlerMutation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The listener takes the `script-src-attr` of the academic extensions out where the
 * directive a browser falls back to allows inline scripts, and leaves every other policy
 * as TYPO3 built it.
 */
final class DropInlineEventHandlerHashesWhereInlineIsAllowedTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        Scope::reset();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{list<Mutation>, bool}>
     */
    public static function policyDataProvider(): \Generator
    {
        $default = new Mutation(MutationMode::Extend, Directive::DefaultSrc, SourceKeyword::self);
        $nonce = new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::nonceProxy);
        $unsafeInline = new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::unsafeInline);

        yield 'the default frontend policy' => [
            [$default, $nonce, new InlineEventHandlerMutation('this.form.submit()')],
            true,
        ];
        yield 'script-src with unsafe-inline, set after the extension' => [
            [$default, $nonce, new InlineEventHandlerMutation('this.form.submit()'), $unsafeInline],
            false,
        ];
        yield 'script-src with unsafe-inline, set before the extension' => [
            [$default, $unsafeInline, new InlineEventHandlerMutation('this.form.submit()')],
            false,
        ];
        yield 'default-src with unsafe-inline and no script-src' => [
            [
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self, SourceKeyword::unsafeInline),
                new InlineEventHandlerMutation('this.form.submit()'),
            ],
            false,
        ];
        yield 'unsafe-inline in default-src only, script-src without it' => [
            [
                new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self, SourceKeyword::unsafeInline),
                new Mutation(MutationMode::Set, Directive::ScriptSrc, SourceKeyword::self),
                new InlineEventHandlerMutation('this.form.submit()'),
            ],
            true,
        ];
        yield 'neither script-src nor default-src' => [
            [new InlineEventHandlerMutation('this.form.submit()')],
            false,
        ];
        yield 'script-src-attr of the site next to unsafe-inline' => [
            [
                $default,
                new InlineEventHandlerMutation('this.form.submit()'),
                $unsafeInline,
                new Mutation(MutationMode::Extend, Directive::ScriptSrcAttr, SourceKeyword::unsafeHashes, HashValue::hash('alert(1)')),
            ],
            true,
        ];
        yield 'script-src-attr of the site with unsafe-inline' => [
            [
                $default,
                new InlineEventHandlerMutation('this.form.submit()'),
                new Mutation(MutationMode::Extend, Directive::ScriptSrcAttr, SourceKeyword::unsafeInline),
            ],
            true,
        ];
        yield 'script-src-attr of the site with the same hash as the extension' => [
            [
                $default,
                $unsafeInline,
                new Mutation(MutationMode::Extend, Directive::ScriptSrcAttr, SourceKeyword::unsafeHashes, HashValue::hash('this.form.submit()')),
            ],
            true,
        ];
    }

    /**
     * @param list<Mutation> $mutations
     */
    #[DataProvider('policyDataProvider')]
    #[Test]
    public function scriptSrcAttrIsKeptUnlessTheFallbackAllowsInlineScripts(array $mutations, bool $kept): void
    {
        $event = $this->dispatch(Scope::frontend(), new MutationCollection(...$mutations));

        $this->assertSame($kept, $event->getCurrentPolicy()->has(Directive::ScriptSrcAttr));
    }

    /**
     * Next to a hash a browser ignores `'unsafe-inline'`, so a `script-src-attr` the site
     * extends by it loses the hashes of the extensions, and keeps everything else.
     */
    #[Test]
    public function theHashesOfTheExtensionsLeaveAScriptSrcAttrWithUnsafeInline(): void
    {
        $own = new InlineEventHandlerMutation('this.form.submit()');
        $siteHash = HashValue::hash('alert(1)');
        $event = $this->dispatch(Scope::frontend(), new MutationCollection(
            new Mutation(MutationMode::Extend, Directive::DefaultSrc, SourceKeyword::self),
            $own,
            new Mutation(MutationMode::Extend, Directive::ScriptSrcAttr, SourceKeyword::unsafeInline, $siteHash),
        ));

        $policy = $event->getCurrentPolicy();
        $this->assertFalse($policy->containsDirective(Directive::ScriptSrcAttr, $own->sources[1]));
        $this->assertTrue($policy->containsDirective(Directive::ScriptSrcAttr, SourceKeyword::self, SourceKeyword::unsafeHashes, SourceKeyword::unsafeInline, $siteHash));
    }

    /**
     * Without `'unsafe-inline'` in it, a `script-src-attr` of the site keeps the hashes of
     * the extensions.
     */
    #[Test]
    public function theHashesOfTheExtensionsStayInAScriptSrcAttrOfTheSiteWithoutUnsafeInline(): void
    {
        $own = new InlineEventHandlerMutation('this.form.submit()');
        $event = $this->dispatch(Scope::frontend(), new MutationCollection(
            new Mutation(MutationMode::Extend, Directive::DefaultSrc, SourceKeyword::self),
            $own,
            new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::unsafeInline),
            new Mutation(MutationMode::Extend, Directive::ScriptSrcAttr, HashValue::hash('alert(1)')),
        ));

        $this->assertTrue($event->getCurrentPolicy()->containsDirective(Directive::ScriptSrcAttr, $own->sources[1]));
    }

    #[Test]
    public function theOtherDirectivesAreLeftAsTheyAre(): void
    {
        $event = $this->dispatch(Scope::frontend(), new MutationCollection(
            new Mutation(MutationMode::Extend, Directive::DefaultSrc, SourceKeyword::self),
            new InlineEventHandlerMutation('this.form.submit()'),
            new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::unsafeInline),
        ));

        $policy = $event->getCurrentPolicy();
        $this->assertTrue($policy->containsDirective(Directive::DefaultSrc, SourceKeyword::self));
        $this->assertTrue($policy->containsDirective(Directive::ScriptSrc, SourceKeyword::self, SourceKeyword::unsafeInline));
    }

    #[Test]
    public function theBackendPolicyIsLeftAsItIs(): void
    {
        $event = $this->dispatch(Scope::backend(), new MutationCollection(
            new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::unsafeInline),
            new InlineEventHandlerMutation('this.form.submit()'),
        ));

        $this->assertTrue($event->getCurrentPolicy()->has(Directive::ScriptSrcAttr));
    }

    #[Test]
    public function theMutationAllowsTheHashOfEveryHandlerText(): void
    {
        $mutation = new InlineEventHandlerMutation('this.form.submit()', 'this.form.reset()');

        $this->assertSame(MutationMode::Extend, $mutation->mode);
        $this->assertSame(Directive::ScriptSrcAttr, $mutation->directive);
        $this->assertSame(
            [
                "'unsafe-hashes'",
                "'sha256-osjxnKEPL/pQJbFk1dKsF7PYFmTyMWGmVSiL9inhxJY='",
                (string)HashValue::hash('this.form.reset()'),
            ],
            array_map(
                static fn(SourceInterface $source): string => match (true) {
                    $source instanceof SourceKeyword => "'" . $source->value . "'",
                    $source instanceof HashValue => (string)$source,
                    default => get_debug_type($source),
                },
                $mutation->sources,
            ),
        );
    }

    /**
     * Applies the mutations the way `PolicyProvider::provideFor()` does and dispatches the
     * event to the listener.
     */
    private function dispatch(Scope $scope, MutationCollection ...$collections): PolicyMutatedEvent
    {
        $defaultPolicy = new Policy();
        $event = new PolicyMutatedEvent($scope, null, $defaultPolicy, $defaultPolicy->mutate(...$collections), ...$collections);
        (new DropInlineEventHandlerHashesWhereInlineIsAllowed())($event);
        return $event;
    }
}
