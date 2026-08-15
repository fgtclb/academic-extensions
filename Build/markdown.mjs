#!/usr/bin/env node
/**
 * Markdown gate for the repository's own documentation.
 *
 * It enforces the four conventions "docs/" and the Markdown files around it
 * were written to, and which nothing else checks:
 *
 *   - relative links resolve to a file that exists
 *   - table rows are padded so the pipes line up
 *   - no trailing whitespace, and the file ends in exactly one newline
 *   - every page below "docs/" that is not an "Index.md" ends in a
 *     "## See also" section
 *
 * The first two are what actually rot: a page gets moved and the links that
 * pointed at it stay, a row gets added to a table and is not padded. Both are
 * invisible in a rendered diff and obvious in the source.
 *
 * Called without arguments it reports and changes nothing. With "--fix" it
 * repairs what can be repaired mechanically - padding and whitespace - and
 * then reports what is left, because a dead link and a missing section are
 * decisions rather than formatting.
 *
 * Deliberately dependency free, so the "lintMarkdown" suite runs in the node
 * container without installing anything first.
 */
import { existsSync, readdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join, relative, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const repositoryRoot = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const fix = process.argv.includes('--fix');

/**
 * Directories that never hold documentation of this repository: build output,
 * dependency trees, the development instances and the scratch trees an agent
 * or a maintainer keeps at the root.
 */
const skippedDirectories = new Set([
    '.Build',
    '.agent',
    '.git',
    '.idea',
    '.sbuerk',
    'documentation-rendered',
    'node_modules',
    'public',
    'var',
    'vendor',
]);

const isSkipped = (name) => skippedDirectories.has(name) || /^core-\d+$/.test(name);

/**
 * Collects every Markdown file below "directory".
 *
 * Symlinks are skipped rather than followed. "CLAUDE.md", "GEMINI.md" and
 * ".github/copilot-instructions.md" all point at "AGENTS.md", and reporting
 * the same problem four times helps nobody.
 */
function collect(directory, found = []) {
    for (const entry of readdirSync(directory, { withFileTypes: true }).sort((a, b) => a.name.localeCompare(b.name))) {
        const path = join(directory, entry.name);
        if (entry.isSymbolicLink()) {
            continue;
        }
        if (entry.isDirectory()) {
            if (!isSkipped(entry.name)) {
                collect(path, found);
            }
            continue;
        }
        if (entry.isFile() && entry.name.endsWith('.md')) {
            found.push(path);
        }
    }
    return found;
}

/** Splits a table row on unescaped pipes, dropping the outer empty cells. */
function splitRow(line) {
    const cells = [];
    let current = '';
    for (let index = 0; index < line.length; index++) {
        if (line[index] === '\\' && line[index + 1] === '|') {
            current += '\\|';
            index++;
            continue;
        }
        if (line[index] === '|') {
            cells.push(current);
            current = '';
            continue;
        }
        current += line[index];
    }
    cells.push(current);
    return cells.slice(1, -1).map((cell) => cell.trim());
}

const isSeparatorRow = (cells) => cells.length > 0 && cells.every((cell) => /^:?-+:?$/.test(cell));

/** Renders a table with every column padded to its widest cell. */
function renderTable(rows) {
    const columns = Math.max(...rows.map((row) => row.length));
    const padded = rows.map((row) => [...row, ...Array(columns - row.length).fill('')]);
    const separator = padded.findIndex(isSeparatorRow);

    const widths = [];
    for (let column = 0; column < columns; column++) {
        let width = 3;
        padded.forEach((row, index) => {
            if (index !== separator) {
                width = Math.max(width, row[column].length);
            }
        });
        widths.push(width);
    }

    return padded.map((row, index) => {
        const cells = row.map((cell, column) => {
            if (index !== separator) {
                return ` ${cell.padEnd(widths[column])} `;
            }
            const left = cell.startsWith(':');
            const right = cell.endsWith(':') && cell !== ':';
            const fill = widths[column] + 2 - (left ? 1 : 0) - (right ? 1 : 0);
            return `${left ? ':' : ''}${'-'.repeat(fill)}${right ? ':' : ''}`;
        });
        return `|${cells.join('|')}|`;
    });
}

const isTableRow = (line) => line.trim().startsWith('|') && line.trim().endsWith('|');

/**
 * Rewrites the tables of a file, leaving anything that is not a table alone.
 * Fenced code blocks are skipped, so a Markdown table shown as an example
 * keeps whatever shape the example needs.
 */
function formatTables(lines) {
    const result = [];
    let block = [];
    let inFence = false;

    const flush = () => {
        if (block.length === 0) {
            return;
        }
        const rows = block.map((line) => splitRow(line.trim()));
        result.push(...(rows.length >= 2 && rows.some(isSeparatorRow) ? renderTable(rows) : block));
        block = [];
    };

    for (const line of lines) {
        if (line.trimStart().startsWith('```')) {
            flush();
            inFence = !inFence;
            result.push(line);
            continue;
        }
        if (!inFence && isTableRow(line)) {
            block.push(line);
            continue;
        }
        flush();
        result.push(line);
    }
    flush();
    return result;
}

const problems = [];
const changed = [];
const files = collect(repositoryRoot);

for (const path of files) {
    const name = relative(repositoryRoot, path);
    let content = readFileSync(path, 'utf8');

    if (fix) {
        const repaired = `${formatTables(content.split('\n').map((line) => line.replace(/[ \t]+$/, ''))).join('\n').replace(/\n+$/, '')}\n`;
        if (repaired !== content) {
            writeFileSync(path, repaired, 'utf8');
            changed.push(name);
            content = repaired;
        }
    }

    const lines = content.split('\n');

    // --- links -------------------------------------------------------------
    let inFence = false;
    lines.forEach((line, index) => {
        if (line.trimStart().startsWith('```')) {
            inFence = !inFence;
            return;
        }
        if (inFence) {
            return;
        }
        for (const [, , target] of line.matchAll(/\[([^\]]*)\]\(([^)]+)\)/g)) {
            if (/^(https?:|#|mailto:)/.test(target)) {
                continue;
            }
            const file = target.split('#')[0];
            if (file !== '' && !existsSync(resolve(dirname(path), file))) {
                problems.push(`${name}:${index + 1}: dead link -> ${target}`);
            }
        }
    });

    // --- whitespace --------------------------------------------------------
    lines.forEach((line, index) => {
        if (line !== line.replace(/[ \t]+$/, '')) {
            problems.push(`${name}:${index + 1}: trailing whitespace`);
        }
    });
    if (content !== '' && !/[^\n]\n$/.test(content)) {
        problems.push(`${name}: file does not end in exactly one newline`);
    }

    // --- tables ------------------------------------------------------------
    inFence = false;
    let block = [];
    let start = 0;
    const checkBlock = () => {
        if (block.length >= 2 && new Set(block.map((line) => line.length)).size !== 1) {
            problems.push(`${name}:${start}: table rows are not padded to equal width`);
        }
        block = [];
    };
    lines.forEach((line, index) => {
        if (line.trimStart().startsWith('```')) {
            inFence = !inFence;
            checkBlock();
            return;
        }
        if (inFence) {
            return;
        }
        if (isTableRow(line)) {
            if (block.length === 0) {
                start = index + 1;
            }
            block.push(line.replace(/[ \t]+$/, ''));
            return;
        }
        checkBlock();
    });
    checkBlock();

    // --- see also ----------------------------------------------------------
    const inDocs = name.startsWith('docs/') && !name.endsWith('/Index.md');
    if (inDocs && !lines.some((line) => line.trim().toLowerCase() === '## see also')) {
        problems.push(`${name}: no '## See also' section`);
    }
}

for (const name of changed) {
    console.log(`formatted ${name}`);
}
if (changed.length > 0) {
    console.log('');
}
for (const problem of problems) {
    console.log(problem);
}
console.log(`\n${files.length} file(s) checked, ${changed.length} formatted, ${problems.length} problem(s)`);
process.exit(problems.length > 0 ? 1 : 0);
