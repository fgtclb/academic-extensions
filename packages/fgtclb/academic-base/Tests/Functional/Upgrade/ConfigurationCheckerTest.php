<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Upgrade;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use FGTCLB\AcademicBase\Upgrade\ConfigurationChecker;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFinding;
use FGTCLB\AcademicBase\Upgrade\ConfigurationFindingKind;
use FGTCLB\AcademicBase\Upgrade\TemplateOverrideChecker;
use FGTCLB\AcademicTestConfiguration\Replaceable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * The checks that read the database: the static templates a TypoScript record
 * stores, and the page TSconfig a page stores or selects.
 *
 * The fixture extension `academic_test_configuration` stands in for an academic
 * extension after an upgrade. It ships one static template folder that still
 * holds TypoScript and one that does not, and one page TSconfig file - the
 * second one, `Removed.tsconfig`, is what a project written against the
 * previous release imports and what the installed version no longer has.
 *
 * Both queries apply the restrictions core applies when it reads the same rows,
 * and the two differ: a hidden TypoScript record delivers nothing and is not
 * reported, while the page TSconfig of a hidden page is read all the same.
 */
final class ConfigurationCheckerTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/academic-test-configuration',
        // A project site package: its page TSconfig file is there and imports
        // an academic one that is not, which is the shape a resolving reference
        // is followed for.
        'tests/test-upgrade-check-project',
    ];

    private const FIXTURES = __DIR__ . '/Fixtures/ConfigurationCheck/';

    #[Test]
    public function aStaticTemplateThatDeliversNoTypoScriptIsReported(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $findings = $this->findingsOfKind(ConfigurationFindingKind::StaticTemplate);

        $this->assertSame(
            ['sys_template:1', 'sys_template:2', 'sys_template:7'],
            $this->subjectsOf($findings),
        );
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $findings[0]->severity);
        $this->assertStringContainsString(
            'includes the static template "EXT:academic_test_configuration/Configuration/TypoScript/Empty": '
            . 'it holds no TypoScript in the installed version',
            $findings[0]->message,
        );
        $this->assertStringContainsString('The TypoScript record "Main" on page 1', $findings[0]->message);
    }

    /**
     * A stored value that names no folder below the extension is the one case
     * core does *not* skip: `handleSingleIncludeStaticFile()` throws 1651138603
     * for it, and every page below the record loses its frontend. Telling the
     * integrator "TYPO3 skips it without a message" would send them looking for
     * a rendering problem instead of a 500.
     */
    #[Test]
    public function aStaticTemplateWithoutAFolderSaysThatTypo3ThrowsOnIt(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $findings = $this->findingsOfKind(ConfigurationFindingKind::StaticTemplate);

        $this->assertSame('sys_template:7', $findings[2]->subject);
        $this->assertStringContainsString('it names no folder below the extension', $findings[2]->message);
        $this->assertStringContainsString('1651138603', $findings[2]->message);
        $this->assertStringContainsString('TYPO3 does not skip such a value', $findings[2]->message);
    }

    /**
     * Core reads the stored path with `trimExplode()`, which trims the
     * remainder, so whitespace between the extension key and the folder is not
     * part of the path and the record works. Record 8 carries one.
     */
    #[Test]
    public function whitespaceInAStoredPathIsTrimmedAsCoreTrimsIt(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $this->assertNotContains(
            'sys_template:8',
            $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::StaticTemplate)),
        );
    }

    /**
     * The extension of a stored value being gone is the same silence: core
     * returns from `handleSingleIncludeStaticFile()` without a word.
     */
    #[Test]
    public function aStaticTemplateOfAnExtensionThatIsNotInstalledIsReported(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $findings = $this->findingsOfKind(ConfigurationFindingKind::StaticTemplate);

        $this->assertStringContainsString(
            'the extension "academic_gone" is not installed, so TYPO3 skips the value without a message',
            $findings[1]->message,
        );
    }

    /**
     * Two records the check has to stay away from: one that delivers a folder
     * that is there, and one of an extension that is none of its business.
     * Without them the two assertions above also hold for a check that reports
     * every value it sees.
     */
    #[Test]
    public function aWorkingOrForeignStaticTemplateIsNotReported(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $messages = implode("\n", array_map(
            static fn(ConfigurationFinding $finding): string => $finding->message,
            $this->findingsOfKind(ConfigurationFindingKind::StaticTemplate),
        ));

        $this->assertStringNotContainsString('TypoScript/Live', $messages);
        $this->assertStringNotContainsString('fluid_styled_content', $messages);
    }

    /**
     * A record that is hidden, deleted or not yet started contributes nothing
     * to any page, so there is nothing about it to fix. Records 3, 5 and 6 are
     * one of each.
     */
    #[Test]
    public function aTypoScriptRecordThatDeliversNothingAnywayIsNotReported(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $subjects = $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::StaticTemplate));

        $this->assertNotContains('sys_template:3', $subjects, 'hidden');
        $this->assertNotContains('sys_template:5', $subjects, 'deleted');
        $this->assertNotContains('sys_template:6', $subjects, 'starttime in the future');
    }

    #[Test]
    public function aPageImportingOrSelectingAFileThatIsGoneIsReported(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'pages.csv');

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertSame(['pages:2', 'pages:3', 'pages:5'], $this->subjectsOf($findings));
        $this->assertStringContainsString(
            'imports "EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig", '
            . 'which matches no file of the installed version',
            $findings[0]->message,
        );
        $this->assertStringContainsString(
            'selects the page TSconfig file '
            . '"EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig", which the '
            . 'installed version of "academic_test_configuration" does not ship',
            $findings[1]->message,
        );
        $this->assertStringContainsString('The page 5 ("Hidden")', $findings[2]->message);
    }

    /**
     * Page 4 imports and selects the file that is there, page 6 is deleted and
     * page 7 references an extension this check is not about. None of the three
     * may be reported, or the assertion above passes for a check that reports
     * every reference.
     */
    #[Test]
    public function aResolvingDeletedOrForeignPageIsNotReported(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'pages.csv');

        $subjects = $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::TsConfigImport));

        $this->assertNotContains('pages:4', $subjects);
        $this->assertNotContains('pages:6', $subjects);
        $this->assertNotContains('pages:7', $subjects);
    }

    /**
     * Both page TSconfig columns are `l10n_mode` = `exclude`, so `DataHandler`
     * writes a byte copy of the default language value into every translation.
     * Page 102 is the translation of page 2 and carries its dead import.
     *
     * Core never reads page TSconfig from a translation - a rootline entry
     * keeps the default language uid - so reporting it would name a record that
     * is not the one to correct, once per language of the installation.
     */
    #[Test]
    public function aPageTranslationDoesNotRepeatTheFindingOfItsParent(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'pages.csv');

        $subjects = $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::TsConfigImport));

        $this->assertSame(['pages:2', 'pages:3', 'pages:5'], $subjects);
        $this->assertNotContains('pages:102', $subjects);
    }

    /**
     * A workspace version of a page carries its own copy of both columns, and
     * core reads page TSconfig from neither: `PageRepository::versionOL()`
     * restores the live uid over the versioned one, exactly as the language
     * overlay keeps the default language uid. `sys_template` needs no such
     * condition - its TCA declares no `versioningWS`.
     */
    #[Test]
    public function aWorkspaceVersionOfAPageDoesNotRepeatTheFindingOfItsLiveRecord(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'pages.csv');
        $this->getConnectionPool()->getConnectionForTable('pages')->insert('pages', [
            'uid' => 202,
            'pid' => 1,
            'doktype' => 1,
            'slug' => '/renamed',
            'title' => 'Renamed folder',
            't3ver_oid' => 2,
            't3ver_wsid' => 1,
            'TSconfig' => "@import 'EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig'",
        ]);

        $subjects = $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::TsConfigImport));

        $this->assertSame(['pages:2', 'pages:3', 'pages:5'], $subjects);
        $this->assertNotContains('pages:202', $subjects);
    }

    /**
     * The four shapes `TreeFromLineStreamBuilder::processAtImport()` reads for
     * a page TSconfig import. A folder and a wildcard resolve when they match a
     * `.tsconfig` file and are dead when they match none - and "dead" is the
     * case a project that renamed a folder lands in.
     *
     * @param non-empty-string $import
     */
    #[Test]
    #[DataProvider('importShapes')]
    public function everyShapeOfAnImportIsResolvedTheWayCoreResolvesIt(string $import, bool $isReported): void
    {
        $this->insertPage(10, sprintf("@import '%s'\n", $import));

        $subjects = $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::TsConfigImport));

        $this->assertSame($isReported ? ['pages:10'] : [], $subjects);
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function importShapes(): array
    {
        $base = 'EXT:academic_test_configuration/Configuration/';

        return [
            'file' => [$base . 'TSconfig/Live.tsconfig', false],
            'file without its suffix' => [$base . 'TSconfig/Live', false],
            'folder' => [$base . 'TSconfig/', false],
            'wildcard' => [$base . 'TSconfig/*.tsconfig', false],
            'file that is gone' => [$base . 'TSconfig/Removed.tsconfig', true],
            'folder that is gone' => [$base . 'TsConfigOfTwoPointX/', true],
            'wildcard matching nothing' => [$base . 'TSconfig/Removed*.tsconfig', true],
            // Page TSconfig allows two suffixes, so a folder holding only a
            // ".typoscript" file resolves - core loops the allowed suffixes of
            // the type (TreeFromLineStreamBuilder::$atImportTypeToSuffixMap).
            'folder with only a typoscript file' => [$base . 'TypoScript/Live/', false],
            'folder with neither suffix' => [$base . 'TypoScript/Empty/', true],
            'typoscript file by its own name' => [$base . 'TypoScript/Live/setup.typoscript', false],
        ];
    }

    /**
     * TYPO3 v13 deprecated `<INCLUDE_TYPOSCRIPT:` and v14 removed it, so the
     * line is dead on one of the two supported versions whether or not the file
     * it names is there. The check says so for both, because the fix is the
     * same on both.
     */
    #[Test]
    public function theLegacyIncludeSyntaxIsReportedEvenWhenTheFileIsThere(): void
    {
        $this->insertPage(
            10,
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:academic_test_configuration/Configuration/TSconfig/Live.tsconfig">'
            . "\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigSyntax);

        $this->assertSame(['pages:10'], $this->subjectsOf($findings));
        $this->assertStringContainsString('TYPO3 v14 removed', $findings[0]->message);
        $this->assertStringContainsString(
            '@import \'EXT:academic_test_configuration/Configuration/TSconfig/Live.tsconfig\'',
            $findings[0]->message,
        );
    }

    /**
     * A comment is not an import, and neither is a reference to an extension
     * this check is not about. All three comment forms of `LossyTokenizer` are
     * covered: `#`, `//` and a `/* ... *\/` block over several lines, which it
     * skips in `ignoreUntilEndOfMultilineComment()`.
     */
    #[Test]
    public function aCommentedOutOrForeignImportIsNotReported(): void
    {
        $this->insertPage(10, implode("\n", [
            '# @import \'EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig\'',
            '// @import \'EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig\'',
            '/* the block below was replaced by the site set',
            '@import \'EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig\'',
            '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:academic_test_configuration/Configuration/TSconfig/Live.tsconfig">',
            '*/',
            '/* a block that ends on its own line */',
            '@import \'EXT:academic_test_configuration/Configuration/TSconfig/Live.tsconfig\'',
            '@import \'EXT:fluid_styled_content/Configuration/TsConfig/Gone.tsconfig\'',
            'TCEMAIN.table.pages.disablePrependAtCopy = 1',
            // The dead import behind the last comment is what makes this test
            // fail for a comment that never *ends*: without it every line after
            // the first block resolves or is foreign, so swallowing the rest of
            // the field would still report nothing.
            '@import \'EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig\'',
            '',
        ]));

        $this->assertSame(
            ['pages:10'],
            $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::TsConfigImport)),
        );
        $this->assertSame([], $this->findingsOfKind(ConfigurationFindingKind::TsConfigSyntax));
    }

    /**
     * `<INCLUDE_TYPOSCRIPT:` takes attributes, and `condition="..."` routinely
     * holds a `>`. Core only closes the tag on a `>` outside a double quoted
     * value (`LossyTokenizer::parseImportOld()`), so cutting at the first one
     * loses the `source` of exactly the includes that are hardest to find by
     * hand.
     */
    #[Test]
    public function aLegacyIncludeWithAConditionIsStillRead(): void
    {
        $this->insertPage(10, '<INCLUDE_TYPOSCRIPT: condition="[page[\'uid\'] > 1]" '
            . 'source="FILE:EXT:academic_test_configuration/Configuration/TSconfig/Live.tsconfig">' . "\n");

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigSyntax);

        $this->assertSame(['pages:10'], $this->subjectsOf($findings));
    }

    /**
     * `@import` of a folder reads its `*.tsconfig` files and does not descend,
     * while `DIR:` recursed - so the advice to rewrite it has to say so.
     */
    #[Test]
    public function aLegacyDirectoryIncludeIsToldThatImportDoesNotRecurse(): void
    {
        $this->insertPage(10, '<INCLUDE_TYPOSCRIPT: '
            . 'source="DIR:EXT:academic_test_configuration/Configuration/TSconfig/" extensions="tsconfig">' . "\n");

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigSyntax);

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('does not descend into subfolders', $findings[0]->message);
    }

    /**
     * The whole promise of the command in one assertion: it reads.
     */
    #[Test]
    public function theCheckChangesNoRecord(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'pages.csv');
        $this->importCSVDataSet(self::FIXTURES . 'sys_template.csv');

        $this->assertNotSame([], $this->get(ConfigurationChecker::class)->check());

        $this->assertCSVDataSet(self::FIXTURES . 'pages.csv');
        $this->assertCSVDataSet(self::FIXTURES . 'sys_template.csv');
    }

    /**
     * Three XCLASS registrations, three answers. Only the replaced class is
     * ever reflected - loading a subclass of a class the upgrade removed is a
     * fatal error, and a check that reports the problem by dying of it helps
     * nobody.
     */
    #[Test]
    public function anXclassIsReportedBySeverityOfTheReplacedClass(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] = [
            TemplateOverrideChecker::class => ['className' => 'MyProject\\Xclass\\TemplateOverrideChecker'],
            Replaceable::class => ['className' => 'MyProject\\Xclass\\Replaceable'],
            'FGTCLB\\AcademicTestConfiguration\\Removed' => ['className' => 'MyProject\\Xclass\\Removed'],
            \stdClass::class => ['className' => 'MyProject\\Xclass\\StdClass'],
        ];

        $findings = $this->findingsOfKind(ConfigurationFindingKind::Xclass);

        $this->assertSame(
            [
                TemplateOverrideChecker::class,
                'FGTCLB\\AcademicTestConfiguration\\Removed',
                Replaceable::class,
            ],
            $this->subjectsOf($findings),
            'Sorted by class name, and the XCLASS of a class outside the academic extensions is none '
            . 'of this check\'s business',
        );
        $this->assertSame(ContextualFeedbackSeverity::ERROR, $findings[0]->severity);
        $this->assertStringContainsString('it is final', $findings[0]->message);
        $this->assertSame(ContextualFeedbackSeverity::ERROR, $findings[1]->severity);
        $this->assertStringContainsString('does not ship it any more', $findings[1]->message);
        $this->assertSame(ContextualFeedbackSeverity::WARNING, $findings[2]->severity);
        $this->assertStringContainsString('is an API for subclassing', $findings[2]->message);
    }

    /**
     * Core tokenizes the Constants and Setup fields of a TypoScript record with
     * the same tokenizer it uses for page TSconfig, and drops an `@import` that
     * matches no file just as silently - only against the `.typoscript` suffix.
     */
    #[Test]
    public function anImportInATypoScriptFieldThatMatchesNoFileIsReported(): void
    {
        $this->insertTypoScriptRecord(
            constants: "@import 'EXT:academic_test_configuration/Configuration/TypoScript/Removed.typoscript'\n",
            config: "@import 'EXT:academic_test_configuration/Configuration/TypoScript/Live/setup.typoscript'\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TypoScriptImport);

        $this->assertSame(['sys_template:1'], $this->subjectsOf($findings));
        $this->assertStringContainsString('field "Constants"', $findings[0]->message);
        $this->assertStringContainsString(
            'imports "EXT:academic_test_configuration/Configuration/TypoScript/Removed.typoscript", '
            . 'which matches no file of the installed version',
            $findings[0]->message,
        );
        $this->assertStringNotContainsString('Setup', $findings[0]->message, 'The Setup import resolves');
    }

    /**
     * Without these three the assertion above also holds for a check that
     * reports every reference it sees: an import that resolves, one of an
     * extension this check is not about, and a record TYPO3 does not read.
     */
    #[Test]
    public function aResolvingForeignOrHiddenTypoScriptImportIsNotReported(): void
    {
        $this->insertTypoScriptRecord(
            config: "@import 'EXT:academic_test_configuration/Configuration/TypoScript/Live/setup.typoscript'\n"
                . "@import 'EXT:fluid_styled_content/Configuration/TypoScript/Gone/setup.typoscript'\n",
        );
        $this->insertTypoScriptRecord(
            uid: 2,
            config: "@import 'EXT:academic_test_configuration/Configuration/TypoScript/Removed.typoscript'\n",
            hidden: true,
        );

        $this->assertSame([], $this->get(ConfigurationChecker::class)->check());
    }

    /**
     * The syntax TYPO3 v13 deprecated and v14 removed hits a TypoScript record
     * exactly as it hits page TSconfig - and a record field is where it was
     * most used before "@import" existed, so it is reported whether or not the
     * file is there.
     */
    #[Test]
    public function theLegacyIncludeSyntaxInATypoScriptFieldIsReported(): void
    {
        $this->insertTypoScriptRecord(
            config: '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:academic_test_configuration/Configuration/'
                . 'TypoScript/Live/setup.typoscript">' . "\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TypoScriptSyntax);

        $this->assertSame(['sys_template:1'], $this->subjectsOf($findings));
        $this->assertStringContainsString('field "Setup"', $findings[0]->message);
        $this->assertStringContainsString('TYPO3 v14 removed', $findings[0]->message);
        $this->assertSame(
            [],
            $this->findingsOfKind(ConfigurationFindingKind::TypoScriptImport),
            'A legacy include is one finding, not two',
        );
    }

    /**
     * An extension that is not installed needs the message that says so: the
     * advice "depend on the site set of the extension instead" cannot be
     * followed for a package that is not there.
     */
    #[Test]
    public function anImportOfAnExtensionThatIsNotInstalledSaysSo(): void
    {
        $this->insertTypoScriptRecord(
            config: "@import 'EXT:academic_gone/Configuration/TypoScript/setup.typoscript'\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TypoScriptImport);

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('the extension "academic_gone" is not installed', $findings[0]->message);
        $this->assertStringNotContainsString('depend on the site set', $findings[0]->message);
    }

    /**
     * `DIR:` recursed and `@import` of a folder does not, so the advice to
     * rewrite it has to say so here as well - with the suffix a TypoScript
     * record reads.
     */
    #[Test]
    public function aLegacyDirectoryIncludeInATypoScriptFieldSaysImportDoesNotRecurse(): void
    {
        $this->insertTypoScriptRecord(
            config: '<INCLUDE_TYPOSCRIPT: source="DIR:EXT:academic_test_configuration/Configuration/'
                . 'TypoScript/" extensions="typoscript">' . "\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TypoScriptSyntax);

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('"*.typoscript" files', $findings[0]->message);
        $this->assertStringContainsString('does not descend into subfolders', $findings[0]->message);
    }

    /**
     * TYPO3 follows the imports inside a file it reads, so a page whose own
     * import is sound still ends up with configuration that does not resolve.
     * The finding names the page, because that is where an integrator starts,
     * and the file, because that is where the line is.
     */
    #[Test]
    public function aPageTsConfigFileThatResolvesIsFollowed(): void
    {
        $this->insertPage(
            10,
            "@import 'EXT:test_upgrade_check_project/Configuration/TSconfig/Project.tsconfig'\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertSame(['pages:10'], $this->subjectsOf($findings));
        $this->assertStringContainsString(
            'The file "EXT:test_upgrade_check_project/Configuration/TSconfig/Project.tsconfig", '
            . 'reached from the page 10 ("Probe"),',
            $findings[0]->message,
            'The file is named as an EXT: path, not as the expression that led to it',
        );
        $this->assertStringContainsString(
            'imports "EXT:academic_test_configuration/Configuration/TSconfig/Removed.tsconfig"',
            $findings[0]->message,
        );
    }

    /**
     * Two files importing each other. Core has no guard here and is protected
     * by the file system; this check has to terminate on its own, and report
     * the dead import of the pair once rather than never or twice.
     */
    #[Test]
    public function aCycleBetweenTwoPageTsConfigFilesTerminates(): void
    {
        $this->insertPage(
            10,
            "@import 'EXT:academic_test_configuration/Configuration/TSconfig/Chain/CycleA.tsconfig'\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertCount(1, $findings);
        $this->assertSame('pages:10', $findings[0]->subject);
        $this->assertStringContainsString('CycleA.tsconfig', $findings[0]->message);
        $this->assertStringContainsString('Removed.tsconfig', $findings[0]->message);
    }

    /**
     * A selected page TSconfig value is read with one exact file lookup, not
     * with the four shapes of an `@import`. A folder therefore delivers
     * nothing - core's `file_exists()` accepts it and `file_get_contents()`
     * then reads nothing and warns - so the folder itself is the finding, and
     * nothing inside it may be reported. `Chain/` holds a file with a dead
     * academic import, which is what makes this more than a smoke test: with
     * `@import` semantics that file would be read and reported instead.
     */
    #[Test]
    public function aSelectedFolderIsNotExpandedTheWayAnImportIs(): void
    {
        $this->insertPage(
            10,
            tsconfigIncludes: 'EXT:academic_test_configuration/Configuration/TSconfig/Chain/',
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertCount(1, $findings);
        $this->assertStringContainsString('which is a folder', $findings[0]->message);
        $this->assertStringNotContainsString(
            'CycleA',
            $findings[0]->message,
            'Nothing inside the folder is read, because TYPO3 reads nothing there either',
        );
    }

    /**
     * The other half: a selected file that TYPO3 does read is followed, so a
     * dead academic import inside a project file is reported with the page
     * that selects it.
     */
    #[Test]
    public function aSelectedFileThatResolvesIsFollowed(): void
    {
        $this->insertPage(
            10,
            tsconfigIncludes: 'EXT:test_upgrade_check_project/Configuration/TSconfig/Project.tsconfig',
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertSame(['pages:10'], $this->subjectsOf($findings));
        $this->assertStringContainsString(
            'The file "EXT:test_upgrade_check_project/Configuration/TSconfig/Project.tsconfig"',
            $findings[0]->message,
        );
    }

    /**
     * The same for a selected file of an *academic* extension, which takes the
     * other branch of the lookup: that one resolves the path itself rather
     * than falling through to the shared one, so a test naming a project file
     * leaves it uncovered.
     */
    #[Test]
    public function aSelectedAcademicFileThatResolvesIsFollowed(): void
    {
        $this->insertPage(
            10,
            tsconfigIncludes: 'EXT:academic_test_configuration/Configuration/TSconfig/Chain/CycleA.tsconfig',
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertCount(1, $findings);
        $this->assertSame('pages:10', $findings[0]->subject);
        $this->assertStringContainsString('CycleA.tsconfig', $findings[0]->message);
        $this->assertStringContainsString('Removed.tsconfig', $findings[0]->message);
    }

    /**
     * Core checks every file an `@import` resolves to against the deny pattern
     * and contributes nothing when it refuses, so such an import is dead. The
     * check has to report it rather than read the file and call the import
     * resolved - which is what happens when the guard covers the folder shape
     * only, as it did before the review.
     */
    #[Test]
    public function anImportOfAFileTheDenyPatternRefusesIsReported(): void
    {
        $this->insertPage(
            10,
            "@import 'EXT:academic_test_configuration/Configuration/TSconfig/Denied.php.tsconfig'\n",
        );

        $findings = $this->findingsOfKind(ConfigurationFindingKind::TsConfigImport);

        $this->assertSame(['pages:10'], $this->subjectsOf($findings));
        $this->assertStringContainsString('a name TYPO3 refuses', $findings[0]->message);
        $this->assertStringContainsString('Denied.php.tsconfig', $findings[0]->message);
        $this->assertStringNotContainsString(
            'matches no file of the installed version',
            $findings[0]->message,
            'The file is there and the integrator can see it - the reason has to be the name',
        );
    }

    /**
     * The `seen` set exists to stop a cycle, not to report a file once per
     * installation: two pages importing the same broken file are two records
     * to correct, and each has to be named. It is therefore created per page.
     */
    #[Test]
    public function twoPagesReachingTheSameFileAreBothReported(): void
    {
        $import = "@import 'EXT:test_upgrade_check_project/Configuration/TSconfig/Project.tsconfig'\n";
        $this->insertPage(10, $import);
        $this->insertPage(11, $import);

        $this->assertSame(
            ['pages:10', 'pages:11'],
            $this->subjectsOf($this->findingsOfKind(ConfigurationFindingKind::TsConfigImport)),
        );
    }

    private function insertTypoScriptRecord(
        int $uid = 1,
        string $constants = '',
        string $config = '',
        bool $hidden = false,
    ): void {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert('sys_template', [
            'uid' => $uid,
            'pid' => 1,
            'hidden' => $hidden ? 1 : 0,
            'title' => 'Probe',
            'root' => 1,
            'clear' => 0,
            'constants' => $constants,
            'config' => $config,
            'include_static_file' => '',
        ]);
    }

    private function insertPage(int $uid, string $tsConfig = '', string $tsconfigIncludes = ''): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')->insert('pages', [
            'uid' => $uid,
            'pid' => 0,
            'doktype' => 1,
            'slug' => '/probe-' . $uid,
            'title' => 'Probe',
            'TSconfig' => $tsConfig,
            'tsconfig_includes' => $tsconfigIncludes,
        ]);
    }

    /**
     * @return list<ConfigurationFinding>
     */
    private function findingsOfKind(ConfigurationFindingKind $kind): array
    {
        return array_values(array_filter(
            $this->get(ConfigurationChecker::class)->check(),
            static fn(ConfigurationFinding $finding): bool => $finding->kind === $kind,
        ));
    }

    /**
     * @param list<ConfigurationFinding> $findings
     * @return list<string>
     */
    private function subjectsOf(array $findings): array
    {
        return array_map(
            static fn(ConfigurationFinding $finding): string => $finding->subject,
            $findings,
        );
    }
}
