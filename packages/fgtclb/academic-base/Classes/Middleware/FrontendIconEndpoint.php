<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Middleware;

use FGTCLB\AcademicBase\Imaging\FrontendIconRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Answers `<site base>/_academic/icons.json?i=<id,id,...>&s=<size>&v=<token>` with the
 * markup of the icons asked for, `{"<id>": "<markup>", ...}`, for frontend TypeScript
 * that composes an icon identifier at runtime. What may be served and the markup are
 * {@see FrontendIconRenderer}'s, the same as the JSON map of the ViewHelper.
 *
 * Its position in the frontend stack (`Configuration/RequestMiddlewares.php`) is what
 * makes it cheap and safe: after the site resolver, so the site, the site language and
 * the route tail are known and every base - a subfolder, a `/de/` language - works;
 * after the maintenance mode, which therefore applies; before both authenticators, so
 * no session is started and no `Set-Cookie` is ever sent; and before the page resolver,
 * which would answer the path with a 404. Anything that is not this path below a real
 * site is passed on untouched.
 *
 * - `GET` and `HEAD` only, `405` for every other method.
 * - `i`: a comma separated list of at most {@see self::MAX_IDENTIFIERS} identifiers, each
 *   matching {@see FrontendIconRenderer::IDENTIFIER_PATTERN}; `400` otherwise. An
 *   identifier that may not be served is left out of the answer, which keeps the order
 *   of the request.
 * - `s`: `default`, `small` (the default), `medium`, `large` or `mega`; `400` otherwise.
 * - `v`: the version token of the icon set. A request for the current token is answered
 *   as immutable for a year, any other for five minutes. The token only decides the
 *   lifetime; the answer is the current markup either way.
 * - `ETag` is a hash of the body, and a matching `If-None-Match` is answered `304`.
 *   That only helps the five-minute answers: an immutable one is never revalidated,
 *   so whatever changes the markup has to change the token.
 *
 * The path ends in `.json`; a web server rule that serves `*.json` statically has to
 * pass it on to TYPO3.
 *
 * @internal Experimental until a consumer outside the academic extensions exists; the
 *           path, the parameters and the answer may change without a breaking change
 *           entry.
 */
final readonly class FrontendIconEndpoint implements MiddlewareInterface
{
    public const MAX_IDENTIFIERS = 32;

    private const CACHE_CONTROL_CURRENT = 'public, max-age=31536000, immutable';

    private const CACHE_CONTROL_OTHER = 'public, max-age=300';

    public function __construct(
        private FrontendIconRenderer $frontendIconRenderer,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routing = $request->getAttribute('routing');
        if (!$request->getAttribute('site') instanceof Site
            || !$routing instanceof SiteRouteResult
            || ltrim($routing->getTail(), '/') !== FrontendIconRenderer::ENDPOINT_PATH
        ) {
            return $handler->handle($request);
        }

        $method = $request->getMethod();
        if ($method !== 'GET' && $method !== 'HEAD') {
            return $this->error(405, 'Only GET and HEAD are allowed.')->withHeader('Allow', 'GET, HEAD');
        }

        $query = $request->getQueryParams();
        $list = $query['i'] ?? '';
        if (!is_string($list) || $list === '') {
            return $this->error(400, 'Parameter "i" has to name at least one icon identifier.');
        }
        $identifiers = explode(',', $list);
        if (count($identifiers) > self::MAX_IDENTIFIERS) {
            return $this->error(400, sprintf('Parameter "i" names more than %d icon identifiers.', self::MAX_IDENTIFIERS));
        }
        foreach ($identifiers as $identifier) {
            if (preg_match(FrontendIconRenderer::IDENTIFIER_PATTERN, $identifier) !== 1) {
                return $this->error(400, 'Parameter "i" holds a value that is not an icon identifier.');
            }
        }
        $sizeName = $query['s'] ?? 'small';
        $size = is_string($sizeName) ? FrontendIconRenderer::sizeFrom($sizeName) : null;
        if ($size === null) {
            return $this->error(400, 'Parameter "s" is not one of default, small, medium, large or mega.');
        }
        $version = $query['v'] ?? '';

        $body = json_encode(
            $this->frontendIconRenderer->render($identifiers, $size),
            JsonResponse::DEFAULT_JSON_FLAGS | JSON_FORCE_OBJECT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
        $etag = '"' . hash('xxh3', $body) . '"';
        $headers = [
            'Cache-Control' => is_string($version) && $version !== '' && hash_equals($this->frontendIconRenderer->getVersion(), $version)
                ? self::CACHE_CONTROL_CURRENT
                : self::CACHE_CONTROL_OTHER,
            'ETag' => $etag,
        ];

        if ($this->matches($request->getHeaderLine('If-None-Match'), $etag)) {
            return new Response(null, 304, $headers);
        }

        $response = new Response('php://temp', 200, $headers + [
            'Content-Type' => 'application/json; charset=utf-8',
            'Content-Length' => (string)strlen($body),
            'X-Content-Type-Options' => 'nosniff',
        ]);
        if ($method === 'GET') {
            $response->getBody()->write($body);
        }

        return $response;
    }

    /**
     * `If-None-Match` is a list; a weak validator matches as well (RFC 9110, 13.1.2).
     */
    private function matches(string $ifNoneMatch, string $etag): bool
    {
        if ($ifNoneMatch === '') {
            return false;
        }
        foreach (explode(',', $ifNoneMatch) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '*' || $candidate === $etag || $candidate === 'W/' . $etag) {
                return true;
            }
        }

        return false;
    }

    private function error(int $status, string $message): ResponseInterface
    {
        return new JsonResponse(['error' => $message], $status, ['Cache-Control' => 'no-store']);
    }
}
