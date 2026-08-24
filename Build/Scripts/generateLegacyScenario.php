<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Writes "ScenarioLegacy.yaml" of the development seed from "Scenario.yaml".
 *
 * The "/legacy/" tree mirrors the "/" tree - the same content pages, the same
 * content elements, the same slugs, delivered through a root "sys_template"
 * record instead of through site sets. A mirror maintained by hand is a mirror
 * that drifts, so it is derived instead: a page added to "Scenario.yaml"
 * reaches the second tree by re-running this script.
 *
 * The storage folders of "/data" are the one thing it does not mirror. Their
 * records are shared - every plugin of the mirror names the pids of the "/"
 * tree - so a mirrored folder would be an empty folder in the backend that
 * looks like it should hold something (ACE-460, S3-3). 65 pages of "/" become
 * 56 pages of "/legacy/".
 *
 *   php Build/Scripts/generateLegacyScenario.php
 *   php Build/Scripts/generateLegacyScenario.php --check   # exit 1 if it would change
 *
 * It needs the composer install of the repository root for the YAML parser, so
 * run "Build/Scripts/runTests.sh -t 13 -s composerUpdate" first if ".Build/" is
 * empty. It writes a committed artifact and is run by hand when the "/" tree
 * changes, which is why it is a script and not a suite of "runTests.sh".
 *
 * WHAT IT REWRITES, AND WHAT IT LEAVES ALONE
 *
 * Uids move by a fixed offset per table, so a record of the mirror is found by
 * arithmetic: pages and "tt_content" by 1000, the four duplicated record tables
 * by 200. The German variant rule of "Scenario.yaml" - original plus 500 - is
 * preserved by both, because 1 + 1000 + 500 is 501 + 1000.
 *
 * A *reference* is a different question from a uid, and the two page ranges are
 * treated differently: a pointer at a storage folder (100-199, and 600-699 for
 * its translation) stays where it is, because those folders and everything in
 * them exist once and are read by both trees; every other page pointer moves
 * with the tree. That distinction is "isSharedStoragePage()", which decides
 * both what is skipped and what is left unmapped, and it is why the FlexForm
 * fields holding a page uid are listed explicitly rather than matched by a
 * "*Pid" pattern - "settings.pages" of the felogin plugin names the frontend
 * user folder and must not move.
 *
 * Comments are lost. The parser discards them, and re-attaching them to the
 * right node afterwards would be a second, less reliable program. The prose of
 * a mirrored page is in "Scenario.yaml", at its uid minus 1000.
 */
$autoload = __DIR__ . '/../../.Build/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "The composer install is missing. Run \"Build/Scripts/runTests.sh -t 13 -s composerUpdate\" first.\n");
    exit(1);
}
require $autoload;

use Symfony\Component\Yaml\Yaml;

const SET_PATH = __DIR__ . '/../../packages-dev/dev-site/Configuration/DataFactory/academics-instance';
const GENERATED_SET_PATH = __DIR__ . '/../../packages-dev/dev-site/Configuration/DataFactory/academics-instance-sets';

/**
 * Uid offsets of the mirror, per table group.
 */
const PAGE_OFFSET = 1000;
const CONTENT_OFFSET = 1000;
const RECORD_OFFSET = 200;

/**
 * The entities that are duplicated. Everything else the "/" tree declares is
 * addressed by a storage pid and is read by both trees from where it is.
 */
const MIRRORED_ENTITIES = ['content', 'contact', 'partnership', 'semester', 'module'];

/**
 * The FlexForm fields that hold a page uid. Listed rather than pattern matched:
 * "settings.pages" of the felogin plugin also holds one and must not move.
 */
const FLEXFORM_PAGE_FIELDS = ['settings.detailPid', 'settings.redirectPageId', 'settings.redirectPageLogin'];

/**
 * Page uid columns of the mirrored tables, per entity.
 */
const RECORD_UID_LISTS = [
    'page' => ['tx_academiccontacts4pages_contacts', 'tx_academicpartners_partnerships'],
    'content' => ['tx_academicstudyplan_semesters'],
    'semester' => ['modules'],
];

exit(main($argv));

/**
 * @param string[] $argv
 */
function main(array $argv): int
{
    $check = in_array('--check', $argv, true);

    $scenario = Yaml::parseFile(SET_PATH . '/Scenario.yaml');
    if (!is_array($scenario) || !isset($scenario['entities']['page'][0])) {
        fwrite(STDERR, "\"Scenario.yaml\" declares no page tree.\n");
        return 1;
    }
    $sourceRoot = $scenario['entities']['page'][0];

    $artifacts = [
        GENERATED_SET_PATH . '/ScenarioLegacy.yaml' => render(buildRoot($sourceRoot), legacyFileHeader()),
        GENERATED_SET_PATH . '/Scenario.yaml' => setsScenario($scenario),
        GENERATED_SET_PATH . '/config.yml' => setsConfig(),
    ];

    $failed = false;
    foreach ($artifacts as $target => $rendered) {
        if ($check) {
            $current = is_file($target) ? file_get_contents($target) : '';
            if ($current === $rendered) {
                printf("%s is up to date.\n", basename($target));
                continue;
            }
            fwrite(STDERR, sprintf(
                "%s differs from what the \"academics-instance\" set produces.\n",
                basename($target),
            ));
            $failed = true;
            continue;
        }

        if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0775, true)) {
            fwrite(STDERR, sprintf("Could not create \"%s\".\n", dirname($target)));
            return 1;
        }
        file_put_contents($target, $rendered);
        printf("Written %s\n", $target);
    }

    return $failed ? 1 : 0;
}

/**
 * The "Scenario.yaml" of the set that delivers through site sets.
 *
 * It is the whole source scenario - every entity declaration, every top level
 * entity, the complete tree - with the three keys that deliver through a static
 * template removed: the "tsconfig_includes" of the root page, the "sys_template"
 * record below it, and the entity declaration that record needs.
 *
 * Removing rather than adding is deliberate. The source is the one a human
 * edits, and it is the "core-12" shape - the shape that works on both core
 * versions this branch supports. What the "core-13" instance does on top of it
 * is drop a delivery mechanism it does not need, and a deletion of three known
 * keys cannot lose anything else.
 *
 * It is dumped rather than rendered through {@see emitItem()}: this file is a
 * complete scenario, not one appended to another, so it has to carry everything
 * - which is exactly what the appending renderer of "ScenarioLegacy.yaml" does
 * not do.
 *
 * @param array<string, mixed> $scenario
 */
function setsScenario(array $scenario): string
{
    unset(
        $scenario['entities']['page'][0]['self']['tsconfig_includes'],
        $scenario['entities']['page'][0]['entities']['template'],
        $scenario['entitySettings']['template'],
    );

    return setsFileHeader() . "\n" . Yaml::dump($scenario, 12, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
}

/**
 * @param array<string, mixed> $sourceRoot
 * @return array<string, mixed>
 */
function buildRoot(array $sourceRoot): array
{
    $root = mapItem('page', $sourceRoot);

    // The three columns that make this root a root of its own. Everything else
    // on it is mirrored.
    $root['self']['title'] = 'Academic extensions (legacy delivery)';
    $root['self']['is_siteroot'] = 1;
    $root['self']['tsconfig_includes'] = pageTsConfigIncludes();
    $root['languageVariants'][0]['self']['title'] = 'Academic Extensions (klassische Auslieferung)';

    // The record that delivers the TypoScript, first among the entities of the
    // root page so it is the first thing read in the file.
    $root['entities'] = ['template' => [['self' => templateRecord(PAGE_OFFSET)]]] + ($root['entities'] ?? []);

    return $root;
}

/**
 * @param array<string, mixed> $item
 * @return array<string, mixed>
 */
function mapItem(string $entity, array $item): array
{
    $mapped = ['self' => mapSelf($entity, $item['self'])];

    foreach ($item['languageVariants'] ?? [] as $variant) {
        if (($variant['entities'] ?? []) !== []) {
            throw new RuntimeException('A language variant with nested entities is not handled by this script.', 1787260101);
        }
        $mapped['languageVariants'][] = ['self' => mapSelf($entity, $variant['self'])];
    }

    foreach ($item['entities'] ?? [] as $name => $items) {
        if (!in_array($name, MIRRORED_ENTITIES, true)) {
            continue;
        }
        foreach ($items as $sub) {
            $mapped['entities'][$name][] = mapItem((string)$name, $sub);
        }
    }

    foreach ($item['children'] ?? [] as $child) {
        // The storage folders are not mirrored: their records are shared, so a
        // mirrored folder would be empty. Skipping the folder skips its
        // subtree, which is what is wanted - every one of them is a leaf of
        // storage folders only.
        if (isSharedStoragePage((int)($child['self']['id'] ?? 0))) {
            continue;
        }
        $mapped['children'][] = mapItem('page', $child);
    }

    return $mapped;
}

/**
 * @param array<string, mixed> $self
 * @return array<string, mixed>
 */
function mapSelf(string $entity, array $self): array
{
    $mapped = [];
    foreach ($self as $key => $value) {
        $mapped[$key] = mapValue($entity, (string)$key, $value);
    }
    return $mapped;
}

function mapValue(string $entity, string $key, mixed $value): mixed
{
    if ($key === 'id') {
        // Every uid this function still sees belongs to a mirrored record: the
        // storage folders are dropped in mapItem(), and a *pointer* at one is
        // left alone by mapPageUid().
        return match ($entity) {
            'page' => (int)$value + PAGE_OFFSET,
            'content' => (int)$value + CONTENT_OFFSET,
            default => (int)$value + RECORD_OFFSET,
        };
    }
    if (in_array($key, RECORD_UID_LISTS[$entity] ?? [], true)) {
        return mapUidList((string)$value, static fn(int $uid): int => $uid + RECORD_OFFSET);
    }
    if ($entity === 'content' && $key === 'pages') {
        return mapUidList((string)$value, mapPageUid(...));
    }
    if ($entity === 'content' && $key === 'pi_flexform') {
        return mapFlexForm((string)$value);
    }
    if ($entity === 'content' && $key === 'bodytext') {
        return mapPageLinks((string)$value);
    }
    if ($entity === 'partnership' && $key === 'partner') {
        return mapPageUid((int)$value);
    }

    return $value;
}

/**
 * The shared storage folders and their translations keep their uid; every other
 * page pointer moves with the tree.
 */
function mapPageUid(int $uid): int
{
    return isSharedStoragePage($uid) ? $uid : $uid + PAGE_OFFSET;
}

/**
 * A storage folder of "/data", or its German variant. "Scenario.yaml" reserves
 * 100-199 for them and writes every translation at its original plus 500.
 */
function isSharedStoragePage(int $uid): bool
{
    return ($uid >= 100 && $uid <= 199) || ($uid >= 600 && $uid <= 699);
}

/**
 * @param callable(int): int $map
 */
function mapUidList(string $list, callable $map): string
{
    $mapped = [];
    foreach (explode(',', $list) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $mapped[] = (string)$map((int)$part);
    }

    return implode(',', $mapped);
}

function mapFlexForm(string $xml): string
{
    foreach (FLEXFORM_PAGE_FIELDS as $field) {
        $pattern = '#(<field index="' . preg_quote($field, '#') . '">\s*<value index="vDEF">)([^<]*)(</value>)#';
        $xml = preg_replace_callback(
            $pattern,
            static fn(array $matches): string => $matches[1] . mapUidList($matches[2], mapPageUid(...)) . $matches[3],
            $xml,
        ) ?? $xml;
    }

    return $xml;
}

function mapPageLinks(string $text): string
{
    return preg_replace_callback(
        '#t3://page\?uid=(\d+)#',
        static fn(array $matches): string => 't3://page?uid=' . mapPageUid((int)$matches[1]),
        $text,
    ) ?? $text;
}

/**
 * The whole file, as a string.
 *
 * @param array<string, mixed> $root
 */
function render(array $root, string $header): string
{
    $lines = explode("\n", rtrim($header, "\n"));
    $lines[] = '';
    $lines[] = 'entitySettings:';
    $lines[] = '  # The only entity this file adds. It does not repeat the forty lines';
    $lines[] = '  # "Scenario.yaml" declares: "ScenarioComposer" merges the "entitySettings" of';
    $lines[] = '  # the files before the factory is built, so every entity of that file is in';
    $lines[] = '  # force here - and the "*" wildcard is merged into this entry as well, which';
    $lines[] = '  # is where "pid", "id" -> "uid" and the default values come from.';
    $lines[] = '  template:';
    $lines[] = "    tableName: 'sys_template'";
    $lines[] = '';
    $lines[] = 'entities:';
    $lines[] = '  page:';
    emitItem($lines, 4, 'page', $root);

    return implode("\n", $lines) . "\n";
}

/**
 * @param list<string> $lines
 * @param array<string, mixed> $item
 */
function emitItem(array &$lines, int $indent, string $entity, array $item): void
{
    $pad = str_repeat(' ', $indent);
    if ($entity === 'page') {
        foreach (pageNotes()[(int)($item['self']['id'] ?? 0)] ?? [] as $note) {
            $lines[] = rtrim($pad . '# ' . $note);
        }
    }

    $lines[] = $pad . '- self:';
    foreach ($item['self'] as $key => $value) {
        emitField($lines, $indent + 6, (string)$key, $value);
    }

    if (($item['languageVariants'] ?? []) !== []) {
        $lines[] = $pad . '  languageVariants:';
        foreach ($item['languageVariants'] as $variant) {
            emitItem($lines, $indent + 4, $entity, $variant);
        }
    }

    if (($item['entities'] ?? []) !== []) {
        $lines[] = $pad . '  entities:';
        foreach ($item['entities'] as $name => $items) {
            $lines[] = $pad . '    ' . $name . ':';
            foreach ($items as $sub) {
                emitItem($lines, $indent + 6, (string)$name, $sub);
            }
        }
    }

    if (($item['children'] ?? []) !== []) {
        $lines[] = $pad . '  children:';
        foreach ($item['children'] as $child) {
            emitItem($lines, $indent + 4, 'page', $child);
        }
    }
}

/**
 * @param list<string> $lines
 */
function emitField(array &$lines, int $indent, string $key, mixed $value): void
{
    $pad = str_repeat(' ', $indent);
    if (is_string($value) && str_contains($value, "\n")) {
        $lines[] = $pad . $key . ': |';
        foreach (explode("\n", rtrim($value, "\n")) as $line) {
            $lines[] = rtrim($pad . '  ' . $line);
        }
        return;
    }

    $lines[] = $pad . $key . ': ' . scalar($value);
}

/**
 * One scalar, written the way "Scenario.yaml" writes it.
 */
function scalar(mixed $value): string
{
    if (is_bool($value)) {
        return $value ? '1' : '0';
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }
    if ($value === null) {
        return 'null';
    }
    $text = (string)$value;
    if ($text === '') {
        return "''";
    }

    return "'" . str_replace("'", "''", $text) . "'";
}

/**
 * The static TypoScript templates and the constants of the root "sys_template"
 * record - everything the "/" site gets from its "dependencies" and its
 * "settings.yaml", said the way a site without site sets says it.
 *
 * @return array<string, int|string>
 */
function templateRecord(int $offset): array
{
    $staticFiles = implode(',', [
        // The theme, and it is available here - unlike on the "main" branch,
        // where "/legacy/" renders unstyled. EXT:bootstrap_package 15 ships
        // "Configuration/TypoScript/" and registers it with addStaticFile(),
        // and it ships "Configuration/Sets/" as well, so both delivery
        // mechanisms reach the same theme on this branch. Version 16, which
        // dropped the static template, needs TYPO3 v14 and is out of reach here.
        // The minimal page object of the seed package comes first and the theme
        // after it, so the theme wins where it is installed. It is not decoration
        // either: the functional suite loads this repository's packages and not
        // "bk2k/bootstrap-package", which the instances require and the repository
        // root does not - and without a page object the mirror renders nothing
        // there, which would make the markup comparison of "LegacyDeliveryTest"
        // pass by comparing two empty pages.
        'EXT:academics_dev_site/Configuration/TypoScript',
        // The nine academic extensions that ship TypoScript, each through the
        // aggregate folder that names every component of the extension.
        // "academic_base" has none - it ships page TSconfig only - and neither
        // do "academic_persons_sync" and "category_types".
        //
        // "EXT:academic_persons/Configuration/TypoScript/Standalone" is
        // deliberately absent: it defines a "page" object of its own and would
        // replace the theme.
        'EXT:academic_persons/Configuration/TypoScript/Full',
        'EXT:academic_persons_edit/Configuration/TypoScript/Full',
        'EXT:academic_contacts4pages/Configuration/TypoScript/Full',
        'EXT:academic_jobs/Configuration/TypoScript/Full',
        'EXT:academic_bite_jobs/Configuration/TypoScript/Full',
        'EXT:academic_partners/Configuration/TypoScript/Full',
        'EXT:academic_programs/Configuration/TypoScript/Full',
        'EXT:academic_projects/Configuration/TypoScript/Full',
        'EXT:academic_study_plan/Configuration/TypoScript/Full',
        // The theme last, and that position is load-bearing twice over. It has to
        // come after the page object of the seed package so that it wins where it
        // is installed - and it has to come after everything else because the
        // functional suite does not install it at all: an "include_static_file"
        // entry naming an absent extension costs every entry after it, silently.
        'EXT:bootstrap_package/Configuration/TypoScript',
    ]);

    // The page uids the plugins point at. A tree points at its own pages, so
    // they move with the tree - except the storage folders, which exist once
    // and are read by both.
    $detailPid = $offset + 205;
    $jobsDetailPid = $offset + 233;
    $jobsListPid = $offset + 231;
    $jobsFallbackPid = $offset + 235;

    $constants = <<<TYPOSCRIPT
        # What a site's "settings.yaml" says through site settings, said the way a
        # site without site sets has to say it. On TYPO3 v12 a site provides no
        # TypoScript at all, and its settings are inserted at the position this
        # record clears - before its static includes - so a static template would
        # overwrite every one of them. This field is applied after the children and
        # therefore wins.
        #
        # Bootstrap package fetches its font from fonts.googleapis.com through a
        # PageRenderer hook with no try/catch, so on a machine without outbound
        # HTTPS the first frontend request ends in a 500. A development instance has
        # no business calling out anyway.
        page.theme.googleFont.enable = 0
        # Nothing here sets a cookie worth consenting to, and the banner covers the
        # page while looking at a plugin.
        page.theme.cookieconsent.enable = 0

        plugin.tx_academicpersons {
            detailPid = {$detailPid}
            demand.sortBy = lastName
            demand.sortByDirection = asc
            pagination.resultsPerPage = 10
        }

        plugin.tx_academicjobs {
            persistence.storagePid = 120
            detailPid = {$jobsDetailPid}
            listPid = {$jobsListPid}
            saveForm.fallbackRedirectPageId = {$jobsFallbackPid}
            email.from = seed-noreply@example.org
            email.recipientEmail = jobs@example.org
        }
        TYPOSCRIPT;

    return [
        // "1" is free here, unlike on the root page of the source tree: the
        // content elements of the mirror are offset by 1000, so nothing else
        // below page 1001 declares it.
        'id' => 1,
        'title' => 'Academic extensions (static template delivery)',
        'root' => 1,
        'clear' => 3,
        'include_static_file' => $staticFiles,
        'constants' => $constants . "\n",
    ];
}

/**
 * The "tsconfig_includes" of the legacy root page.
 */
function pageTsConfigIncludes(): string
{
    // "academic_base" is in this list and is not in the static template list: it
    // ships page TSconfig only - the CType group and the hide/re-enable pair of
    // ACE-458 live there.
    //
    // The backend layouts of EXT:bootstrap_package are NOT in this list, and the
    // "backend_layout" values of the mirrored pages therefore name layouts the
    // legacy tree does not define. That is the same finding as in the static
    // template list above: version 16 of that extension delivers its page
    // TSconfig through site sets and dropped the registerPageTSConfigFile()
    // calls, so there is no pre-site-set path to it any more.
    return implode(',', [
        'EXT:academic_base/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_bite_jobs/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_contacts4pages/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_jobs/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_partners/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_persons/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_persons_edit/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_programs/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_projects/Configuration/TSconfig/Full/page.tsconfig',
        'EXT:academic_study_plan/Configuration/TSconfig/Full/page.tsconfig',
    ]);
}

/**
 * The few comments the generated file carries, keyed by the mirrored page uid.
 *
 * @return array<int, list<string>>
 */
function pageNotes(): array
{
    return [
        1001 => [
            'The second site root. It is a top level page and not a branch of page 1: a',
            'site with the base "/legacy/" needs a root page of its own, and',
            '"core-NN/config/sites/academics-legacy/" is committed and therefore read',
            'before the import runs, so TYPO3 writes no "config/sites/autogenerated-*"',
            'for it and answers no 404 below it.',
            '',
            'Its slugs are those of the mirrored pages, unchanged. Slug uniqueness is',
            'evaluated per site ("uniqueInSite") and this page is a site of its own, so',
            '"/persons/list" exists twice without either being renamed - which is what',
            'makes a page of one tree findable from the other by prefixing "/legacy".',
        ],
    ];
}

/**
 * The "config.yml" of the generated set.
 *
 * It is the source set's descriptor with three changes: a different identifier,
 * the two scenario files of this set, and the file references of the mirror
 * added to the ones of the "/" tree.
 *
 * The reference list is the reason this is generated rather than written twice.
 * A "sys_file_reference" names its record by uid, so every reference of a
 * mirrored record has a mirrored counterpart - and a counterpart that is missing
 * is not an error anywhere: the record simply renders without its image.
 *
 * @return string
 */
function setsConfig(): string
{
    /** @var array<string, mixed> $source */
    $source = Yaml::parseFile(SET_PATH . '/config.yml');

    $references = [];
    foreach ($source['references'] ?? [] as $reference) {
        $references[] = $reference;
        $mirrored = mirrorReference($reference);
        if ($mirrored !== null) {
            $references[] = $mirrored;
        }
    }

    $definition = [
        'identifier' => 'academics-instance-sets',
        'title' => 'Academic extensions development instance (site sets)',
        'description' => $source['description'] ?? '',
        'scenarios' => [
            'EXT:academics_dev_site/Configuration/DataFactory/academics-instance-sets/Scenario.yaml',
            'EXT:academics_dev_site/Configuration/DataFactory/academics-instance-sets/ScenarioLegacy.yaml',
        ],
        'files' => $source['files'] ?? [],
        'references' => $references,
    ];

    return setsConfigHeader() . "\n" . Yaml::dump($definition, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
}

/**
 * The mirror of one file reference, or null when the record it names is shared.
 *
 * Profiles and jobs are shared between the two trees - every plugin of the
 * mirror names the storage folder of the "/" tree - so their references are not
 * duplicated. Pages, content elements and study plan modules are mirrored, by
 * the same offsets the records are.
 *
 * @param array<string, mixed> $reference
 * @return array<string, mixed>|null
 */
function mirrorReference(array $reference): ?array
{
    $offsets = [
        'pages' => PAGE_OFFSET,
        'tt_content' => CONTENT_OFFSET,
        'tx_academicstudyplan_domain_model_module' => RECORD_OFFSET,
    ];
    $table = (string)($reference['table'] ?? '');
    if (!isset($offsets[$table])) {
        return null;
    }
    if ($table === 'pages' && isSharedStoragePage((int)$reference['uid'])) {
        return null;
    }

    $reference['uid'] = (int)$reference['uid'] + $offsets[$table];

    return $reference;
}

function setsConfigHeader(): string
{
    return <<<'HEADER'
        # The seed set of the "core-13" instance, which delivers the "/" tree through
        # site sets and the "/legacy/" tree through a root "sys_template" record.
        #
        # GENERATED FILE. Written by "Build/Scripts/generateLegacyScenario.php" from
        # the set "academics-instance" - edit that one and run the script, never this.
        #
        # The "core-12" instance imports "academics-instance" instead: site sets
        # arrived in TYPO3 v13.1, so on v12 there is only one way to deliver and only
        # one tree worth having.
        HEADER;
}

function setsFileHeader(): string
{
    return <<<'HEADER'
        # The "/" tree of the seed set "academics-instance-sets".
        #
        # GENERATED FILE. Written by "Build/Scripts/generateLegacyScenario.php" from
        # "../academics-instance/Scenario.yaml" - edit that file and run the script,
        # never this one.
        #
        # It is that tree with two fields taken off the root page: the
        # "tsconfig_includes" and the root "sys_template" record, both of which
        # deliver what the site sets of this instance deliver. Everything else is
        # identical, and the comments of the source are lost on the way - the parser
        # discards them.
        HEADER;
}

function legacyFileHeader(): string
{
    return <<<'HEADER'
        # The "/legacy/" tree of the seed set "academics-instance".
        #
        # GENERATED FILE. Written by "Build/Scripts/generateLegacyScenario.php" from
        # "Scenario.yaml" - edit that file and run the script, never this one.
        #
        # "Scenario.yaml" holds the "/" tree, which is delivered through site sets. This
        # file holds a full mirror of it below a second site root, delivered through a
        # root "sys_template" record and page "tsconfig_includes" instead - the way an
        # installation that predates site sets is configured, and the only way the two
        # delivery mechanisms can be compared page for page.
        #
        # The two files are composed into one scenario before anything is written:
        # "ScenarioComposer" merges the "entitySettings" and appends the "entities" per
        # entity name, in the order "config.yml" lists the files. A record here may
        # therefore name a record of the other file, and does, for the shared storage.
        #
        # WHAT IS MIRRORED, AND WHAT IS SHARED
        #
        # The mirror is the page tree, and only the page tree. Roughly fifteen of the
        # seeded tables are addressed by a storage pid - a "persistence.storagePid"
        # constant or a "tt_content.pages" list - so they exist once, under "/data" of
        # the "/" tree, and every plugin below names those pids. Four tables cannot be
        # shared, because the code that reads them selects by a page or by a content
        # element rather than by a storage pid, so they are duplicated with the page
        # that owns them:
        #
        #   tx_academiccontacts4pages_domain_model_contact  selected by "page"
        #   tx_academicpartners_domain_model_partnership    selected by "page"
        #   tx_academicstudyplan_domain_model_semester      selected by "content_element"
        #   tx_academicstudyplan_domain_model_module        selected by its semester
        #
        # The storage folders of "/data" are not mirrored at all. Mirroring a folder
        # whose records are shared leaves an empty folder in the backend that looks like
        # it should hold something, so "/" has 65 pages and "/legacy/" has 56.
        #
        # UIDS
        #
        #   pages       the uid of the mirrored page plus 1000, so 1 -> 1001 and
        #               345 -> 1345 - the whole tree lives in 1000-1399, with
        #               1100-1199 unused because the storage folders are not
        #               mirrored
        #   tt_content  the uid of the mirrored element plus 1000, so 1 -> 1001
        #   the four    the uid of the mirrored record plus 200, so 1 -> 201
        #   duplicated
        #   tables
        #
        # and, as in "Scenario.yaml", a German variant carries the uid of its original
        # plus 500 - which the offsets above preserve: page 501, the German variant of
        # page 1, becomes 1501, and 1501 is 1001 plus 500.
        #
        # A note on the ranges: the header of "Scenario.yaml" reserves "tt_content"
        # 500-999 for this tree. It cannot have it - the German variants of the 63
        # elements of the "/" tree occupy 501-563 under the plus-500 rule, and that rule
        # wins. This tree takes the 1000 block in "tt_content" as well, which is also
        # what makes the two tables read alike: the legacy tree is the 1000 block.
        #
        # Everything else - the shared storage pids, "sys_category" uid lists, contract
        # and role uids - is written unchanged, because it points into the shared
        # storage on purpose.
        #
        # This file carries almost no prose, and that is a property of being generated
        # rather than an omission: the parser discards comments, so the explanation of a
        # mirrored page is in "Scenario.yaml", at its uid minus 1000.
        HEADER;
}
