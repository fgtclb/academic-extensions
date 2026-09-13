<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Tests\Functional\Plugins;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * Probe: does a profile data edit refresh the image reference metadata?
 */
final class AcademicPersonsEditProfileEditingImageReferenceMetadataTest extends AbstractFrontendProfilePluginTestCase
{
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    /**
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

    #[Test]
    public function editingTheAcademicTitleRewritesTheImageReferenceMetadata(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->seedProfileImage();

        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['title' => 'Prof. Dr.']],
        );
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $this->assertSame(
            ['title' => 'Prof. Dr. Max Müllermann', 'alternative' => 'Prof. Dr. Max Müllermann'],
            $this->fetchReferenceMetadata(self::PROFILE_ID),
        );
    }

    #[Test]
    public function aFullFormEditRewritesTheImageReferenceMetadata(): void
    {
        $this->setUpProfileEditingTestCase();
        $this->seedProfileImage();

        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['title' => 'Prof. Dr.', 'gender' => 'ms']],
        );
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $this->assertSame(
            ['title' => 'Prof. Dr. Max Müllermann', 'alternative' => 'Prof. Dr. Max Müllermann'],
            $this->fetchReferenceMetadata(self::PROFILE_ID),
        );
    }

    #[Test]
    public function editingProfileDataRewritesTheFileMetadataRecord(): void
    {
        $this->setUpProfileEditingTestCase();
        $fileUid = $this->seedProfileImage();
        $this->setProfileImageFileMetadata($fileUid, [
            'title' => 'Set earlier',
            'alternative' => 'Set earlier',
        ]);

        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['title' => 'Prof. Dr.']],
        );
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $this->assertSame(
            ['title' => 'Prof. Dr. Max Müllermann', 'alternative' => 'Prof. Dr. Max Müllermann'],
            $this->fetchFileMetadata($fileUid),
        );
    }

    /**
     * @return array{title: string, alternative: string}
     */
    private function fetchFileMetadata(int $fileUid): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_metadata')
            ->select(['title', 'alternative'], 'sys_file_metadata', ['file' => $fileUid])
            ->fetchAssociative();
        $this->assertIsArray($row, sprintf('File %d has no metadata record.', $fileUid));
        return ['title' => (string)$row['title'], 'alternative' => (string)$row['alternative']];
    }

    #[Test]
    public function aNameEditRefreshesTheMetadataOfTheTranslatedImageReferenceToo(): void
    {
        $this->setUpTranslationTestCase();
        $this->seedProfileImage();

        $updateUrl = $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url');
        $response = $this->postJson(
            $updateUrl,
            ['profile' => self::PROFILE_ID, 'data' => ['title' => 'Prof. Dr.']],
        );
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $translatedProfileUid = (int)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT uid FROM tx_academicpersons_domain_model_profile'
                    . ' WHERE l10n_parent = ? AND sys_language_uid = 1 AND deleted = 0',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertGreaterThan(0, $translatedProfileUid, 'The translation was not created.');

        $this->assertSame(
            ['title' => 'Prof. Dr. Max Müllermann', 'alternative' => 'Prof. Dr. Max Müllermann'],
            $this->fetchReferenceMetadata(self::PROFILE_ID),
            'The default language reference.',
        );
        $this->assertSame(
            ['title' => 'Prof. Dr. Max Müllermann', 'alternative' => 'Prof. Dr. Max Müllermann'],
            $this->fetchReferenceMetadata($translatedProfileUid),
            'The translated reference.',
        );
    }

    #[Test]
    public function editingInTheTranslatedLanguageRefreshesThatReferenceMetadata(): void
    {
        $this->setUpTranslationTestCase();
        $this->insertRecord('pages', [
            'uid' => 4,
            'pid' => 1,
            'doktype' => 1,
            'sys_language_uid' => 1,
            'l10n_parent' => 2,
            'slug' => '/home',
            'title' => 'Home (DE)',
        ]);
        $this->insertRecord('tt_content', [
            'uid' => 3,
            'pid' => 2,
            'sys_language_uid' => 1,
            'l18n_parent' => 1,
            'CType' => 'academicpersonsedit_profileediting',
        ]);
        $this->seedProfileImage();

        // Create the translation the way production does: an edit in the default language.
        $this->postJson(
            $this->extractDataUrl($this->renderProfileEditingPage(), 'data-update-url'),
            ['profile' => self::PROFILE_ID, 'data' => ['website' => 'https://example.org']],
        );
        $translatedProfileUid = (int)$this->getConnectionPool()
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->executeQuery(
                'SELECT uid FROM tx_academicpersons_domain_model_profile'
                    . ' WHERE l10n_parent = ? AND sys_language_uid = 1 AND deleted = 0',
                [self::PROFILE_ID],
            )
            ->fetchOne();
        $this->assertGreaterThan(0, $translatedProfileUid, 'The translation was not created.');

        // Now edit the profile while browsing the translated site.
        $listPage = $this->getPageAsFrontendUser('https://www.acme.com/de/home');
        $editingPage = $this->getPageAsFrontendUser(
            $this->extractPluginActionLink(
                $listPage,
                'tx_academicpersonsedit_profileediting',
                'index',
                'profileUid',
                self::PROFILE_ID,
            ),
        );
        $response = $this->postJson(
            $this->extractDataUrl($editingPage, 'data-update-url'),
            ['profile' => self::PROFILE_ID, 'data' => ['title' => 'Prof. Dr.']],
        );
        $this->assertSame(200, $response->getStatusCode(), (string)$response->getBody());

        $this->assertSame(
            'Prof. Dr.',
            (string)$this->getConnectionPool()
                ->getConnectionForTable('tx_academicpersons_domain_model_profile')
                ->executeQuery(
                    'SELECT title FROM tx_academicpersons_domain_model_profile WHERE uid = ?',
                    [$translatedProfileUid],
                )
                ->fetchOne(),
            'The translated profile row was not written.',
        );
        $this->assertSame(
            ['title' => 'Prof. Dr. Max Müllermann', 'alternative' => 'Prof. Dr. Max Müllermann'],
            $this->fetchReferenceMetadata($translatedProfileUid),
            'The translated reference metadata.',
        );
    }

    /**
     * An explicitly inserted uid does not advance the auto-increment sequence on
     * PostgreSQL, so the next generated uid collides with it. `importCSVDataSet()`
     * resets the sequence itself; a raw insert does not.
     *
     * @param array<string, mixed> $row
     */
    private function insertRecord(string $tableName, array $row): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable($tableName);
        $connection->insert($tableName, $row);
        Testbase::resetTableSequences($connection, $tableName);
    }

    private function setUpTranslationTestCase(): void
    {
        $this->setUpFrontendProfileTestCase(
            __DIR__ . '/Fixtures/AcademicPersonsEditProfileEditing/profileEditingPage.csv',
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    /**
     * @return array{title: string, alternative: string}
     */
    private function fetchReferenceMetadata(int $profileUid): array
    {
        $row = $this->getConnectionPool()
            ->getConnectionForTable('sys_file_reference')
            ->select(
                ['title', 'alternative'],
                'sys_file_reference',
                [
                    'tablenames' => 'tx_academicpersons_domain_model_profile',
                    'fieldname' => 'image',
                    'uid_foreign' => $profileUid,
                    'deleted' => 0,
                ],
            )
            ->fetchAssociative();
        $this->assertIsArray($row, sprintf('Profile %d has no image reference.', $profileUid));
        return ['title' => (string)$row['title'], 'alternative' => (string)$row['alternative']];
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
                ->withAddedHeader('X-Requested-With', 'XMLHttpRequest')
                ->withBody($body),
        );
    }
}
