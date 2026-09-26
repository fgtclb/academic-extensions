<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\AtImportInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\FileInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeTyposcriptInclude;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reads what a TypoScript record delivers to the frontend, and what the backend form keeps
 * of the static templates it stores.
 *
 * A static template is a string stored in `sys_template.include_static_file`, and the core
 * resolves it without a message when the folder it names holds nothing. Rendering a page
 * and looking for one value proves that this value arrived; it does not prove that the
 * rest did. These helpers build the whole TypoScript of one root record instead, through
 * `FrontendTypoScriptFactory` as the frontend does, so two ways of including an extension
 * can be compared as a whole.
 *
 * The record is built in memory, with `clear` set and no site, so nothing of the test
 * instance's own TypoScript records or site sets takes part. `FrontendTypoScriptFactory` is
 * marked `@internal` in both core versions; it is the one place that turns records into
 * the TypoScript the frontend uses, and a test is the right place to depend on it.
 */
trait StaticTemplateTypoScriptTrait
{
    /**
     * The settings (constants) and the setup a root TypoScript record builds.
     *
     * @return array{settings: array<string, string>, setup: array<string, mixed>}
     */
    private function typoScriptOfTemplateRecord(string $includeStaticFile, string $constants = '', string $setup = ''): array
    {
        $frontendTypoScript = $this->frontendTypoScriptOfTemplateRecord($includeStaticFile, $constants, $setup);

        return [
            'settings' => $frontendTypoScript->getFlatSettings(),
            'setup' => $frontendTypoScript->getSetupArray(),
        ];
    }

    /**
     * Every file the static templates and imports in the setup of a root TypoScript record
     * read, one entry per read, in the order they are read. A file named twice here is
     * parsed twice. The `ext_typoscript_setup.typoscript` files of the loaded extensions,
     * which core reads for every root record, are left out; no academic extension ships one.
     *
     * Only the nodes that read a file count. The core splits a file into segment and
     * condition nodes that carry the name of the file as well, so collecting every node
     * with a file name would list a file with an `@import` in it more than once.
     *
     * @return list<string>
     */
    private function setupFilesOfTemplateRecord(string $includeStaticFile, string $constants = '', string $setup = ''): array
    {
        $includeTree = $this->frontendTypoScriptOfTemplateRecord($includeStaticFile, $constants, $setup)
            ->getSetupIncludeTree();
        $this->assertNotNull($includeTree);

        $fileNames = [];
        $collect = static function (IncludeInterface $node) use (&$collect, &$fileNames): void {
            if ($node instanceof FileInclude || $node instanceof AtImportInclude || $node instanceof IncludeTyposcriptInclude) {
                $fileNames[] = $node->getName();
            }
            foreach ($node->getNextChild() as $child) {
                $collect($child);
            }
        };
        $collect($includeTree);

        return $fileNames;
    }

    /**
     * The static templates the backend form of a stored TypoScript record keeps.
     *
     * The form drops a stored value that is not among the items of the field, and saving
     * the form writes what it kept. The record is written as uid 1 on page 1; the page and
     * the backend user have to be set up by the test.
     *
     * @return list<string>
     */
    private function staticTemplatesTheFormKeeps(string $includeStaticFile): array
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'uid' => 1,
                'pid' => 1,
                'root' => 1,
                'clear' => 3,
                'title' => 'Stored',
                'include_static_file' => $includeStaticFile,
            ],
        );

        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $formData = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => 'sys_template',
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );

        return array_values(array_map('strval', (array)$formData['databaseRow']['include_static_file']));
    }

    private function frontendTypoScriptOfTemplateRecord(
        string $includeStaticFile,
        string $constants,
        string $setup,
    ): FrontendTypoScript {
        $sysTemplateRows = [
            [
                'uid' => 1,
                'pid' => 1,
                'title' => 'Probe',
                'root' => 1,
                'clear' => 3,
                'constants' => $constants,
                'config' => $setup,
                'include_static_file' => $includeStaticFile,
                'basedOn' => '',
                'includeStaticAfterBasedOn' => 0,
                'static_file_mode' => 0,
            ],
        ];
        $site = new NullSite();
        $factory = $this->get(FrontendTypoScriptFactory::class);
        $this->assertInstanceOf(FrontendTypoScriptFactory::class, $factory);

        $frontendTypoScript = $factory->createSettingsAndSetupConditions($site, $sysTemplateRows, [], null);
        $frontendTypoScript = $factory->createSetupConfigOrFullSetup(
            true,
            $frontendTypoScript,
            $site,
            $sysTemplateRows,
            [],
            '0',
            null,
            null,
        );

        return $frontendTypoScript;
    }
}
