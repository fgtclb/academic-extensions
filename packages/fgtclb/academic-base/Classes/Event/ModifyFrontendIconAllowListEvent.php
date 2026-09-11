<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Event;

/**
 * Dispatched whenever {@see \FGTCLB\AcademicBase\Imaging\FrontendIconRenderer} decides
 * which icon identifiers it may hand to a frontend page, so a project can serve the
 * icons of its own extensions the same way:
 *
 *     #[AsEventListener(identifier: 'my-sitepackage/frontend-icons')]
 *     public function __invoke(ModifyFrontendIconAllowListEvent $event): void
 *     {
 *         $event->addPrefix('tx-mysitepackage-');
 *     }
 *
 * The list holds identifier **prefixes**, `tx-academic` and `category_types.` by
 * default. An identifier that matches one is still refused when it is not registered,
 * is registered as deprecated, or is registered with a provider that cannot inline an
 * SVG file - the sprite provider of the core icon set among them, see the renderer.
 *
 * A prefix is nevertheless a decision about what becomes public: a listener that
 * widens the list opens every identifier it matches. The system extensions register
 * some icons of their own with core's `SvgIconProvider`, which is inlined, so a prefix
 * such as `module-` serves `module-install-*` as well. The listener is responsible for
 * what it opens.
 *
 * @internal Experimental until a consumer outside the academic extensions exists; it
 *           may change without a breaking change entry.
 */
final class ModifyFrontendIconAllowListEvent
{
    /**
     * @var list<string>
     */
    private array $prefixes = [];

    /**
     * @param list<string> $prefixes
     */
    public function __construct(array $prefixes)
    {
        $this->setPrefixes($prefixes);
    }

    /**
     * @return list<string>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    /**
     * @param array<mixed> $prefixes Strings; an empty one is ignored rather than
     *        allowing every identifier.
     * @throws \InvalidArgumentException when an entry is not a string
     */
    public function setPrefixes(array $prefixes): void
    {
        $valid = [];
        foreach ($prefixes as $prefix) {
            // Checked here, where a listener passes it, rather than failing with a
            // TypeError on every frontend request that renders an icon.
            if (!is_string($prefix)) {
                throw new \InvalidArgumentException(
                    sprintf('A frontend icon prefix has to be a string, %s given.', get_debug_type($prefix)),
                    1789120002,
                );
            }
            $valid[] = $prefix;
        }
        $this->prefixes = $valid;
    }

    public function addPrefix(string $prefix): void
    {
        if (!in_array($prefix, $this->prefixes, true)) {
            $this->prefixes[] = $prefix;
        }
    }
}
