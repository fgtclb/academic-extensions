<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The `dates` block of a document section, configured against the shipped one.
 *
 * The seven shipped timeline sections all publish the year alone while asking
 * for a whole date, so nothing in the shipped configuration shows what the
 * other combinations do. The fixture extension
 * `test_document_date_publishing` replaces the `documentSections` map with four
 * sections that carry them:
 *
 * - `cooperation.date` asks for a whole date and publishes all three parts, so
 *   a visitor is shown the `MEDIUMDATE` of the site language.
 * - `cooperation.from` and `.to` ask for a year alone - a number control,
 *   because no browser has a year input - and publish exactly that. The two
 *   parts nobody was asked for are completed from opposite edges, so the same
 *   submitted year is the first of January at one end of the period and the
 *   thirty first of December at the other.
 * - `lectures.date` publishes the year and the month of a whole date, which is
 *   the second of the three sentences an editor can be shown, while
 *   `lectures.from` and `.to` are the browser's own month control and complete
 *   the day from opposite edges of the month.
 * - `memberships.date` publishes the month and the day but not the year, which
 *   is neither named case and therefore the third sentence - and the section
 *   configures no help text, so the sentence stands alone.
 * - `vita.date` is marked `required` and nothing else: no `date` flag and no
 *   `dates` block. It is a date because the field is one.
 *
 * The `cooperation` fields publish everything they ask for, so none of them
 * carries a sentence about published parts; the shipped `yearOnly` half is
 * covered by {@see AcademicPersonsEditProfileEditingTest}.
 */
final class AcademicPersonsEditDocumentDatePublishingTest extends AbstractFrontendProfilePluginTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtensionsToLoad('tests/test-document-date-publishing');
        parent::setUp();
    }

    /**
     * A section publishing all three parts shows the whole date, in the notation of
     * the site language and not in the ISO format the control exchanges it in.
     */
    #[Test]
    public function aDateFieldPublishingAllThreePartsShowsTheWholeDate(): void
    {
        $urls = $this->documentEndpointUrls();
        $uid = $this->createCooperation($urls, ['title' => 'Whole date', 'date' => '2019-03-14']);

        $fields = $this->documentFormFields($urls, $uid);

        $this->assertSame('date', $fields['date']['type'] ?? null);
        $this->assertSame('date', $fields['date']['granularity'] ?? null);
        $this->assertSame('', $fields['date']['placeholder'] ?? null);
        $this->assertSame('2019-03-14', $fields['date']['value'] ?? null);
        // The literal an `en-US` visitor reads. Computing it with the very
        // `MEDIUMDATE` call the controller makes would pin that the code calls
        // `MEDIUMDATE`, not what a visitor is shown.
        $this->assertSame('Mar 14, 2019', $fields['date']['displayValue'] ?? null);
        // The configured helptext and nothing else. A field that published less
        // than it asks for would have the sentence saying so appended here, which
        // is what every shipped timeline field shows.
        $this->assertSame(
            $this->configuredHelptext('date'),
            $fields['date']['helptext'] ?? null,
        );
    }

    /**
     * The settings file documents a help text as an LLL key **or** literal text.
     * A literal used to reach `LocalizationUtility::translate()` as a short key
     * without an extension name, which raises `InvalidArgumentException` instead
     * of answering `null`, so every form endpoint of a section configured that
     * way answered 500. The literal reaches the descriptor untouched now, and
     * the reference next to it still resolves.
     */
    #[Test]
    public function aLiteralHelptextReachesTheFieldUntouched(): void
    {
        $urls = $this->documentEndpointUrls();
        $uid = $this->createCooperation($urls, ['title' => 'Literal helptext', 'date' => '2019-03-14']);

        $fields = $this->documentFormFields($urls, $uid);

        $this->assertSame(
            'Give the entry a name your visitors will recognise.',
            $fields['title']['helptext'] ?? null,
        );
        $this->assertSame($this->configuredHelptext('date'), $fields['date']['helptext'] ?? null);
    }

    /**
     * A field asking for a year alone is a number control carrying the bounds the
     * timeline years always had, and the parts it did not ask for are completed from
     * the edge its section names - so the same `2019` is the first of January as a
     * start and the thirty first of December as an end.
     */
    #[Test]
    public function aYearGranularityFieldIsANumberControlAndCompletesTheRestOfTheDate(): void
    {
        $urls = $this->documentEndpointUrls();

        $uid = $this->createCooperation($urls, [
            'title' => 'A period given by its years',
            'date' => '2019-03-14',
            'dateStart' => '2019',
            'dateEnd' => '2019',
        ]);

        $this->assertSame(
            ['date' => '2019-03-14', 'date_start' => '2019-01-01', 'date_end' => '2019-12-31'],
            $this->storedDates($uid),
        );
        $fields = $this->documentFormFields($urls, $uid);
        foreach (['dateStart', 'dateEnd'] as $yearField) {
            $this->assertSame('date', $fields[$yearField]['type'] ?? null, $yearField);
            $this->assertSame('year', $fields[$yearField]['granularity'] ?? null, $yearField);
            $this->assertSame('', $fields[$yearField]['placeholder'] ?? null, $yearField);
            $this->assertSame('2019', $fields[$yearField]['value'] ?? null, $yearField);
            $this->assertSame('2019', $fields[$yearField]['displayValue'] ?? null, $yearField);
            $this->assertSame(1000, $fields[$yearField]['min'] ?? null, $yearField);
            $this->assertSame(9999, $fields[$yearField]['max'] ?? null, $yearField);
            $this->assertSame(1, $fields[$yearField]['step'] ?? null, $yearField);
        }
        // Again the configured helptext alone: a year control that publishes the
        // year is not told anything about published parts either.
        $this->assertSame($this->configuredHelptext('from'), $fields['dateStart']['helptext'] ?? null);
        $this->assertSame($this->configuredHelptext('to'), $fields['dateEnd']['helptext'] ?? null);
    }

    /**
     * The compact row of such a section prints each date with its own published
     * parts: the whole date for `date`, the year alone for the two ends.
     */
    #[Test]
    public function aCompactRowPrintsEachDateWithItsOwnPublishedParts(): void
    {
        $urls = $this->documentEndpointUrls();
        $this->createCooperation($urls, [
            'title' => 'Row of a mixed section',
            'date' => '2019-03-14',
            'dateStart' => '2019',
            'dateEnd' => '2020',
        ]);

        $content = $this->renderProfileEditingPage();

        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML($content, LIBXML_NOERROR | LIBXML_NOWARNING));
        $xpath = new \DOMXPath($document);
        $this->assertSame(
            ['Mar 14, 2019', '2019', '2020'],
            [
                $this->renderedRowValue($xpath, 'date'),
                $this->renderedRowValue($xpath, 'dateStart'),
                $this->renderedRowValue($xpath, 'dateEnd'),
            ],
        );
    }

    /**
     * A whole date whose day is not published is the second of the three sentences
     * an editor can be shown, and the sentence is appended to the help text the
     * section configured rather than replacing it.
     *
     * A visitor sees `Mar 2019` - the month and the year in the notation of the site
     * language - while the control still holds the day the editor entered.
     */
    #[Test]
    public function aDatePublishingTheYearAndTheMonthSaysSoAndShowsExactlyThose(): void
    {
        $urls = $this->documentEndpointUrls();
        $uid = $this->createDocument($urls, 'lectures', [
            'title' => 'A lecture given in March',
            'date' => '2019-03-14',
        ]);

        $fields = $this->documentFormFields($urls, $uid, 'lectures');

        $this->assertSame('date', $fields['date']['type'] ?? null);
        $this->assertSame('date', $fields['date']['granularity'] ?? null);
        $this->assertSame('2019-03-14', $fields['date']['value'] ?? null);
        $this->assertSame('Mar 2019', $fields['date']['displayValue'] ?? null);
        $this->assertSame(
            $this->configuredHelptext('date')
                . ' ' . $this->editorLabel('profileEditing.date.published.yearAndMonth'),
            $fields['date']['helptext'] ?? null,
        );
    }

    /**
     * Anything that is neither the year alone nor the year and the month falls to
     * the third sentence, which names no part because there is no short way to say
     * "the month and the day but not the year".
     *
     * The section configures no help text for the field, so the sentence is the
     * whole of it - which is the case a hint appended to an existing text cannot
     * show.
     */
    #[Test]
    public function aDatePublishedWithoutItsYearFallsBackToThePartialSentence(): void
    {
        $urls = $this->documentEndpointUrls();
        $uid = $this->createDocument($urls, 'memberships', [
            'title' => 'A membership without a year',
            'date' => '2019-03-14',
        ]);

        $fields = $this->documentFormFields($urls, $uid, 'memberships');

        $this->assertSame('2019-03-14', $fields['date']['value'] ?? null);
        $this->assertSame('Mar 14', $fields['date']['displayValue'] ?? null);
        $this->assertSame(
            $this->editorLabel('profileEditing.date.published.partial'),
            $fields['date']['helptext'] ?? null,
        );
    }

    /**
     * A field asking for a year and a month is the browser's own month control: it
     * exchanges `Y-m` and carries none of the bounds a year control needs, because a
     * month control has its own.
     *
     * The day nobody was asked for is completed from the edge the section names, so
     * the same submitted `2019-05` is the first of May at one end of the period and
     * the thirty first at the other - and both sections publish that day, so the
     * completion is what a visitor reads rather than an invisible detail of the row.
     */
    #[Test]
    public function aMonthGranularityFieldIsAMonthControlAndCompletesTheDay(): void
    {
        $urls = $this->documentEndpointUrls();

        $uid = $this->createDocument($urls, 'lectures', [
            'title' => 'A lecture series given in May',
            'date' => '2019-05-06',
            'dateStart' => '2019-05',
            'dateEnd' => '2019-05',
        ]);

        $this->assertSame(
            ['date' => '2019-05-06', 'date_start' => '2019-05-01', 'date_end' => '2019-05-31'],
            $this->storedDates($uid),
        );
        $fields = $this->documentFormFields($urls, $uid, 'lectures');
        foreach (['dateStart', 'dateEnd'] as $monthField) {
            $this->assertSame('date', $fields[$monthField]['type'] ?? null, $monthField);
            $this->assertSame('month', $fields[$monthField]['granularity'] ?? null, $monthField);
            $this->assertSame('', $fields[$monthField]['placeholder'] ?? null, $monthField);
            // `Y-m`, which is what an `<input type="month">` shows and submits.
            $this->assertSame('2019-05', $fields[$monthField]['value'] ?? null, $monthField);
            // A month control brings its own bounds; only a year control needs ours.
            // The keys are there and hold `null`, which is what the control reads as
            // "no attribute" - they are never simply left out.
            foreach (['min', 'max', 'step'] as $bound) {
                $this->assertArrayHasKey($bound, $fields[$monthField], $monthField);
                $this->assertNull($fields[$monthField][$bound], $monthField . '.' . $bound);
            }
            // Publishing everything, a month control is told nothing about the parts.
            $this->assertSame('', $fields[$monthField]['helptext'] ?? null, $monthField);
        }
        $this->assertSame('May 1, 2019', $fields['dateStart']['displayValue'] ?? null);
        $this->assertSame('May 31, 2019', $fields['dateEnd']['displayValue'] ?? null);
    }

    /**
     * A month control reads its value at its own granularity: the full ISO date is
     * accepted, because a client written against the endpoint before the control
     * became a native one keeps working, and a month that does not exist is refused
     * rather than rolled into the next year.
     */
    #[Test]
    public function aMonthGranularityFieldReadsTheIsoDateAndRefusesAnImpossibleMonth(): void
    {
        $urls = $this->documentEndpointUrls();

        $accepted = $this->createDocument($urls, 'lectures', [
            'title' => 'Submitted as a whole date',
            'date' => '2019-05-06',
            'dateStart' => '2019-05-06',
        ]);
        $this->assertSame('2019-05-06', $this->storedDates($accepted)['date_start']);

        $refused = $this->createDocumentResponse($urls, 'lectures', [
            'title' => 'The thirteenth month',
            'date' => '2019-05-06',
            'dateStart' => '2019-13',
        ]);

        $this->assertSame(422, $refused->getStatusCode(), (string)$refused->getBody());
        $body = $this->decodedBody($refused);
        $this->assertSame('validation_failed', $body['error'] ?? null);
        $this->assertContains('The value must be a valid date.', $body['errors']['dateStart'] ?? []);
    }

    /**
     * The three timeline dates are dates whatever a section says about them. The
     * `vita` section of the fixture marks its `date` `required` and nothing else -
     * no `date` flag and no `dates` block - and the field is still exchanged as the
     * string its control accepts.
     *
     * Keyed on the flag alone, the descriptor would carry the stored `\DateTime`
     * into the JSON payload, where it serialises as an object of date, timezone
     * type and timezone that no control can show.
     */
    #[Test]
    public function aTimelineDateIsFormattedEvenWhenItsSectionForgetsTheDateFlag(): void
    {
        $urls = $this->documentEndpointUrls();
        $uid = $this->createDocument($urls, 'vita', [
            'title' => 'A station of a career',
            'date' => '2019-03-14',
        ]);

        $fields = $this->documentFormFields($urls, $uid, 'vita');

        $this->assertSame('date', $fields['date']['type'] ?? null);
        $this->assertSame('date', $fields['date']['granularity'] ?? null);
        // A string, never the `\DateTime` a descriptor keyed on the flag alone
        // would have carried into the JSON payload.
        $this->assertIsString($fields['date']['value'] ?? null);
        $this->assertSame('2019-03-14', $fields['date']['value']);
        // The neutral default publishes everything, so a visitor reads the whole
        // date and the editor is told nothing about published parts.
        $this->assertSame('Mar 14, 2019', $fields['date']['displayValue'] ?? null);
        $this->assertSame('', $fields['date']['helptext'] ?? null);
    }

    /**
     * The lower bound of a year control is `1000` and not `0`: a number control
     * cannot emit the leading zeros a year below it needs, so a year the control
     * could never round trip is refused rather than advertised.
     *
     * Both edges are checked here, because the bound is enforced twice - once as an
     * attribute of the control and once by the endpoint, for every client that is
     * not a browser. `0500` is the case that makes the second one reachable: it is a
     * four digit year that parses and is then refused.
     *
     * @return \Generator<string, array{0: string, 1: bool, 2: string}>
     */
    public static function yearBoundDataSets(): \Generator
    {
        yield 'the lower bound itself is accepted' => ['1000', true, '1000-01-01'];
        yield 'the upper bound itself is accepted' => ['9999', true, '9999-01-01'];
        yield 'a year below the lower bound is refused' => [
            '0500', false, 'The year must be between 1000 and 9999.',
        ];
        yield 'the year just below the lower bound is refused' => [
            '0999', false, 'The year must be between 1000 and 9999.',
        ];
        yield 'a five digit year is not a year at all' => [
            '10000', false, 'The value must be a valid date.',
        ];
    }

    #[DataProvider('yearBoundDataSets')]
    #[Test]
    public function aYearControlEnforcesItsBoundsAtBothEdges(
        string $submitted,
        bool $accepted,
        string $expected,
    ): void {
        $urls = $this->documentEndpointUrls();

        $response = $this->createDocumentResponse($urls, 'cooperation', [
            'title' => 'A year at the edge',
            'date' => '2019-03-14',
            'dateStart' => $submitted,
        ]);

        if (!$accepted) {
            $this->assertSame(422, $response->getStatusCode(), (string)$response->getBody());
            $body = $this->decodedBody($response);
            $this->assertSame('validation_failed', $body['error'] ?? null);
            $this->assertContains($expected, $body['errors']['dateStart'] ?? []);
            return;
        }
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        $uid = $this->decodedBody($response)['item']['uid'] ?? null;
        $this->assertIsInt($uid);
        $this->assertSame($expected, $this->storedDates($uid)['date_start']);
    }

    /**
     * The bounds are what the control advertises as well, so a browser refuses the
     * same values before the endpoint has to.
     */
    #[Test]
    public function aYearControlAdvertisesTheSameBoundsItEnforces(): void
    {
        $urls = $this->documentEndpointUrls();
        $uid = $this->createCooperation($urls, ['title' => 'Bounds', 'date' => '2019-03-14']);

        $fields = $this->documentFormFields($urls, $uid);

        $this->assertSame(1000, $fields['dateStart']['min'] ?? null);
        $this->assertSame(9999, $fields['dateStart']['max'] ?? null);
    }

    private function renderedRowValue(\DOMXPath $xpath, string $field): string
    {
        $found = $xpath->query(sprintf(
            '//*[@data-section-key="cooperation"]//*[@data-pe-document-value="%s"]',
            $field,
        ));
        $this->assertNotFalse($found);
        $node = $found->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node, sprintf('No row cell for "%s".', $field));

        return trim((string)$node->textContent);
    }

    private function configuredHelptext(string $fieldIdentifier): string
    {
        return $this->get(LanguageServiceFactory::class)->create('default')->sL(
            'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:'
                . 'helptext.documentSections.' . $fieldIdentifier,
        );
    }

    /**
     * @return array{create: string, form: string}
     */
    private function documentEndpointUrls(): array
    {
        $this->setUpProfileEditingTestCase();
        $content = $this->renderProfileEditingPage();

        return [
            'create' => $this->extractDataUrl($content, 'data-create-document-url'),
            'form' => $this->extractDataUrl($content, 'data-document-form-url'),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function postJson(string $url, array $payload): ResponseInterface
    {
        $body = new Stream('php://temp', 'rw');
        $body->write(json_encode($payload, JSON_THROW_ON_ERROR));
        $body->rewind();
        return $this->requestAsFrontendUser(
            (new InternalRequest($url))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/json')
                ->withAddedHeader('X-Requested-With', 'XMLHttpRequest')
                ->withBody($body),
        );
    }

    private function extractDataUrl(string $content, string $attribute): string
    {
        $pattern = sprintf('@\b%s="([^"]+)"@', preg_quote($attribute, '@'));
        $this->assertSame(
            1,
            preg_match($pattern, $content, $match),
            sprintf('The rendered component has no "%s" URL.', $attribute),
        );
        $url = html_entity_decode($match[1]);
        return str_starts_with($url, '/') ? 'https://www.acme.com' . $url : $url;
    }

    /**
     * @param array{create: string, form: string} $urls
     * @param array<string, string> $fields
     */
    private function createCooperation(array $urls, array $fields): int
    {
        return $this->createDocument($urls, 'cooperation', $fields);
    }

    /**
     * @param array{create: string, form: string} $urls
     * @param array<string, string> $fields
     */
    private function createDocument(array $urls, string $section, array $fields): int
    {
        $response = $this->createDocumentResponse($urls, $section, $fields);
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $uid = $body['item']['uid'] ?? null;
        $this->assertIsInt($uid);
        $this->assertGreaterThan(0, $uid);

        return $uid;
    }

    /**
     * @param array{create: string, form: string} $urls
     * @param array<string, string> $fields
     */
    private function createDocumentResponse(
        array $urls,
        string $section,
        array $fields,
    ): ResponseInterface {
        return $this->postJson($urls['create'], [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => $section, 'fields' => $fields],
        ]);
    }

    /**
     * @param array{create: string, form: string} $urls
     * @return array<string, array<string, mixed>>
     */
    private function documentFormFields(array $urls, int $uid, string $section = 'cooperation'): array
    {
        $response = $this->postJson($urls['form'], [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => $section, 'record' => $uid, 'mode' => 'edit'],
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return array_column($body['fields'] ?? [], null, 'name');
    }

    /**
     * The English source of a label of the editing extension, which is what a
     * visitor of this test's `en-US` site reads.
     */
    private function editorLabel(string $key): string
    {
        return $this->get(LanguageServiceFactory::class)->create('default')->sL(
            'LLL:EXT:academic_persons_edit/Resources/Private/Language/locallang.xlf:' . $key,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodedBody(ResponseInterface $response): array
    {
        $body = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);

        return $body;
    }

    /**
     * @return array{date: ?string, date_start: ?string, date_end: ?string}
     */
    private function storedDates(int $uid): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile_information')
            ->executeQuery(
                'SELECT date, date_start, date_end'
                    . ' FROM tx_academicpersons_domain_model_profile_information'
                    . ' WHERE uid = ? AND deleted = 0',
                [$uid],
            )
            ->fetchAssociative();
        $this->assertIsArray($row);

        /** @var array{date: ?string, date_start: ?string, date_end: ?string} $row */
        return $row;
    }
}
