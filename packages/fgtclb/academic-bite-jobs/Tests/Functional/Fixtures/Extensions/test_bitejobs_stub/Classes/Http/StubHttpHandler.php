<?php

declare(strict_types=1);

namespace TESTS\TestBitejobsStub\Http;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle handler answering every request with a canned b-ite API response, so functional
 * tests rendering the `academicbitejobs_list` plugin perform no outgoing HTTP request.
 *
 * Stubbing happens at handler level on purpose. Neither `RequestFactory` nor
 * `GuzzleClientFactory` can be subclassed for both supported core versions: TYPO3 v13
 * declares them as normal classes while v14 declares them `readonly`, and a readonly class
 * can neither extend nor be extended by a non-readonly one. `BiteJobsService` itself is
 * `final` and type hinted in the controller, so it cannot be replaced either.
 *
 * The handler is registered in `ext_localconf.php` through
 * `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']`, which TYPO3 v13 and v14 evaluate
 * identically.
 */
final class StubHttpHandler
{
    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        return Create::promiseFor(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                (string)json_encode([
                    'jobPostings' => [
                        [
                            'id' => 4711,
                            'title' => 'Stubbed job posting',
                        ],
                    ],
                ]),
            )
        );
    }
}
