<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Security;

use FGTCLB\AcademicBase\EventListener\DropInlineEventHandlerHashesWhereInlineIsAllowed;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\HashValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;

/**
 * Allows inline event handlers of exactly the given texts: extends `script-src-attr` by
 * `'unsafe-hashes'` and the sha256 hash of every text. An extension returns it from its
 * `Configuration/ContentSecurityPolicies.php` for the frontend scope.
 *
 * It is a mutation of its own class so {@see DropInlineEventHandlerHashesWhereInlineIsAllowed}
 * recognises it among the mutations of a policy, those of a site and of other packages
 * included, which TYPO3 hands over without their origin.
 *
 * @internal only to be used within `EXT:academic_*` extensions and not part of public API.
 */
#[Exclude]
final class InlineEventHandlerMutation extends Mutation
{
    /**
     * @var list<string>
     */
    public readonly array $handlers;

    public function __construct(string ...$handlers)
    {
        $this->handlers = array_values($handlers);
        parent::__construct(
            MutationMode::Extend,
            Directive::ScriptSrcAttr,
            SourceKeyword::unsafeHashes,
            ...array_map(
                static fn(string $handler): HashValue => HashValue::hash($handler),
                $this->handlers,
            ),
        );
    }
}
