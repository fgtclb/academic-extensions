<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\EventListener;

use FGTCLB\AcademicBase\Security\InlineEventHandlerMutation;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;

/**
 * Takes the `script-src-attr` of the academic extensions out of a frontend policy that
 * already allows inline scripts, so the browser keeps reading `script-src` for event
 * handler attributes (ACE-924).
 *
 * An {@see InlineEventHandlerMutation} creates `script-src-attr` where the policy has
 * none. A browser reads that directive for every event handler attribute once it
 * exists, and ignores `'unsafe-inline'` next to a hash. A site that allows its own
 * inline handlers with `'unsafe-inline'` in `script-src` would therefore lose them on
 * every page. The mutations of a site apply after those of the packages, so the site
 * cannot repair it in its `csp.yaml` without removing the directive itself.
 *
 * The directive is removed only when the mutations of the academic extensions alone
 * created it, and the directive the browser falls back to, `script-src` or else
 * `default-src`, carries `'unsafe-inline'`, or neither exists. A `script-src-attr` a
 * site or another package declares as well keeps its sources. Only where it carries
 * `'unsafe-inline'` itself, the hashes of the extensions are taken out of it, because
 * next to them the browser would ignore it. `'unsafe-hashes'` stays, alone it does not
 * disable `'unsafe-inline'`. Whether a nonce stays in
 * `script-src` is decided after this event, on cached pages TYPO3 drops an unused
 * one. Where it stays, a browser ignores `'unsafe-inline'`, and the handlers of the
 * extensions follow the configuration of the site as the site's own handlers do.
 *
 * The policy without those mutations is built from a new policy, not from the default
 * policy of the event: the clones of a policy share the map of their directives, so the
 * default policy already carries every mutation once TYPO3 has applied them.
 *
 * Works on TYPO3 v12 to v14: `Policy::get()` does not exist on v12, the event carries
 * the mutation collections without their origin, and a hash has no equality. The hashes
 * are therefore taken out by instance, the instances the mutations of the extensions
 * hold, which the policy keeps. Recognising those mutations by their class relies on
 * TYPO3 handing over the objects `Configuration/ContentSecurityPolicies.php` returned.
 * A cache that serialised them would still keep the class, one that rebuilt them from
 * their values would not, see the `@todo add policy cache per scope` of
 * `PolicyProvider::provideFor()`.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[AsEventListener(identifier: 'academic-base/drop-inline-event-handler-hashes-where-inline-is-allowed')]
final readonly class DropInlineEventHandlerHashesWhereInlineIsAllowed
{
    public function __invoke(PolicyMutatedEvent $event): void
    {
        $policy = $event->getCurrentPolicy();
        if (!$event->scope->type->isFrontend() || !$policy->has(Directive::ScriptSrcAttr)) {
            return;
        }

        $foreignCollections = [];
        $ownMutations = [];
        foreach ($event->getMutationCollections() as $collection) {
            $foreignMutations = [];
            foreach ($collection->mutations as $mutation) {
                if ($mutation instanceof InlineEventHandlerMutation) {
                    $ownMutations[] = $mutation;
                } else {
                    $foreignMutations[] = $mutation;
                }
            }
            $foreignCollections[] = new MutationCollection(...$foreignMutations);
        }
        if ($ownMutations === []) {
            return;
        }

        if ((new Policy())->mutate(...$foreignCollections)->has(Directive::ScriptSrcAttr)) {
            if ($policy->containsDirective(Directive::ScriptSrcAttr, SourceKeyword::unsafeInline)) {
                $event->setCurrentPolicy($policy->reduce(Directive::ScriptSrcAttr, ...$this->hashesOf(...$ownMutations)));
            }
            return;
        }
        if ($this->allowsInlineScripts($policy)) {
            $event->setCurrentPolicy($policy->remove(Directive::ScriptSrcAttr));
        }
    }

    /**
     * The hash instances of the mutations, which the policy holds as they are.
     *
     * @return list<HashValue>
     */
    private function hashesOf(Mutation ...$mutations): array
    {
        $hashes = [];
        foreach ($mutations as $mutation) {
            foreach ($mutation->sources as $source) {
                if ($source instanceof HashValue) {
                    $hashes[] = $source;
                }
            }
        }
        return $hashes;
    }

    /**
     * Whether the directive a browser reads for event handler attributes without
     * `script-src-attr` allows inline scripts.
     */
    private function allowsInlineScripts(Policy $policy): bool
    {
        foreach (Directive::ScriptSrcAttr->getAncestors() as $ancestor) {
            if ($policy->has($ancestor)) {
                return $policy->containsDirective($ancestor, SourceKeyword::unsafeInline);
            }
        }
        return true;
    }
}
