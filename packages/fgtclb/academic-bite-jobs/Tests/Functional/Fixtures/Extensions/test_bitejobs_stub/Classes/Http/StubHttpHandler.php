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
 * The response carries two postings with different values in `department`, so a list
 * grouped by that field renders two groups.
 *
 * Stubbing happens at handler level on purpose. `BiteJobsService` is `final` and type
 * hinted in the controller, so it cannot be replaced, and `RequestFactory` builds its
 * client through `GuzzleClientFactory` rather than taking one.
 *
 * A search for the listing key `no-postings` is answered without postings, which is how a
 * test reaches the message of an empty list.
 *
 * The handler is registered in `ext_localconf.php` through
 * `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']`, which TYPO3 v12 and v13 evaluate
 * identically.
 */
final class StubHttpHandler
{
    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $search = json_decode((string)$request->getBody(), true);
        if (is_array($search) && ($search['key'] ?? null) === 'no-postings') {
            return Create::promiseFor(
                new Response(200, ['Content-Type' => 'application/json'], (string)json_encode(['jobPostings' => []]))
            );
        }

        return Create::promiseFor(
            new Response(
                200,
                ['Content-Type' => 'application/json'],
                (string)json_encode([
                    'jobPostings' => [
                        [
                            'id' => 4711,
                            'title' => 'Stubbed job posting',
                            'department' => 'Research',
                        ],
                        [
                            'id' => 4712,
                            'title' => 'Second stubbed job posting',
                            'department' => 'Teaching',
                        ],
                    ],
                ]),
            )
        );
    }
}
