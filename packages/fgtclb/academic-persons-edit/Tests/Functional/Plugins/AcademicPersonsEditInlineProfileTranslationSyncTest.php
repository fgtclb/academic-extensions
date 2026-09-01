<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Drives the JSON endpoints of the `academicpersonsedit_inlineprofile` plugin and
 * asserts they trigger the translation synchronisation (ACE-485).
 *
 * The inline editor is the second frontend editing surface of this extension and it
 * persists without a form round trip: every action answers JSON and writes through
 * `AbstractActionController::persistAndDispatchProfileUpdate()`, which is the single
 * place the `AfterProfileUpdateEvent` is raised. `SyncChangesToTranslationsDispatchSurfaceTest`
 * pins that structurally, by scanning the source; this test pins it where it is
 * observable, by the translated row a real request produces.
 *
 * Two of the endpoints are driven rather than all of them: `update`, which writes the
 * profile record itself, and `sortDocument`, whose step-wise branch reaches the
 * database through `ListSortingService` instead of a repository and is therefore the
 * one that most easily loses its dispatch. The depth of what a synchronisation writes
 * is covered by the synchroniser's own tests in `EXT:academic_persons`.
 *
 * @see AcademicPersonsEditProfileEditTranslationSyncTest for the same pin on the
 *      form driven `academicpersonsedit_profileediting` plugin.
 */
final class AcademicPersonsEditInlineProfileTranslationSyncTest extends AbstractFrontendProfilePluginTestCase
{
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    /**
     * Allows translating profiles into site language 1 - the gate the listener reads
     * through `ProfileTranslator::getAllowedLanguageIds()`.
     *
     * @param array<string, mixed> $additionalConfiguration
     * @return array<string, mixed>
     */
    protected function frontendPluginTestConfiguration(array $additionalConfiguration = []): array
    {
        return parent::frontendPluginTestConfiguration(array_replace_recursive([
            'EXTENSIONS' => [
                'academic_persons_edit' => [
                    'profile' => [
                        'allowedLanguages' => '1',
                    ],
                ],
            ],
        ], $additionalConfiguration));
    }

    /**
     * Sets the inline plugin up with a site that has the target language, which the
     * synchroniser resolves against the site of the request.
     */
    private function setUpTranslationSyncTestCase(): void
    {
        $this->setUpFrontendProfileTestCase(
            __DIR__ . '/Fixtures/AcademicPersonsEditInlineProfile/inlineProfilePage.csv',
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    private function seedStructuredDocumentSections(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsEditInlineProfile/structuredDocumentSections.csv');
        $this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->update(
                'tx_academicpersons_domain_model_profile',
                ['cooperation' => 2],
                ['uid' => self::PROFILE_ID],
            );
    }

    private function renderInlineProfilePage(): string
    {
        $listPage = $this->getPageAsFrontendUser('https://www.acme.com/home');
        return $this->getPageAsFrontendUser(
            $this->extractPluginActionLink(
                $listPage,
                'tx_academicpersonsedit_inlineprofile',
                'index',
                'profileUid',
                self::PROFILE_ID,
            ),
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
                ->withBody($body),
        );
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function getProfileRows(): array
    {
        return array_values($this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT uid, pid, sys_language_uid, l10n_parent, l10n_source, first_name, website'
                . ' FROM tx_academicpersons_domain_model_profile WHERE deleted = 0 ORDER BY uid',
            )
            ->fetchAllAssociative());
    }

    #[Test]
    public function inlineProfileUpdateCreatesTheConfiguredTranslation(): void
    {
        $this->setUpTranslationSyncTestCase();
        $updateUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-update-url');
        $this->assertCount(1, $this->getProfileRows(), 'Precondition: only the default language profile exists.');

        $response = $this->postJson($updateUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['website' => 'https://submitted.example.org'],
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $rows = $this->getProfileRows();
        $this->assertCount(2, $rows, 'The inline edit creates exactly one translated profile row.');
        $this->assertSame('https://submitted.example.org', (string)$rows[0]['website'], 'The edit itself was persisted.');
        $this->assertSame(1, (int)$rows[1]['sys_language_uid']);
        $this->assertSame(self::PROFILE_ID, (int)$rows[1]['l10n_parent']);
        $this->assertSame(self::PROFILE_ID, (int)$rows[1]['l10n_source']);
        $this->assertSame(self::PROFILE_PAGE_ID, (int)$rows[1]['pid']);
        $this->assertSame('Max', (string)$rows[1]['first_name']);
    }

    #[Test]
    public function inlineDocumentSortCreatesTheConfiguredTranslation(): void
    {
        $this->setUpTranslationSyncTestCase();
        $this->seedStructuredDocumentSections();
        $sortUrl = $this->extractDataUrl($this->renderInlineProfilePage(), 'data-sort-document-url');
        $this->assertCount(1, $this->getProfileRows(), 'Precondition: only the default language profile exists.');

        $response = $this->postJson($sortUrl, [
            'profile' => self::PROFILE_ID,
            'data' => ['section' => 'cooperation', 'record' => 2, 'direction' => 'up'],
        ]);
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $rows = $this->getProfileRows();
        $this->assertCount(2, $rows, 'The reordering announces the change, so the profile is synchronised.');
        $this->assertSame(1, (int)$rows[1]['sys_language_uid']);
        $this->assertSame(self::PROFILE_ID, (int)$rows[1]['l10n_parent']);
    }
}
