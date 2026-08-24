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
 * Draws the placeholder files the development seed references.
 *
 * The seed set of "packages-dev/dev-site" writes real "sys_file" and
 * "sys_file_reference" rows, so it needs real files. They are generated rather
 * than photographed: nobody has to hold a licence for a development instance,
 * every file is a few kilobytes, and - the point of the whole exercise - each
 * one says in large letters which record it belongs to. A page media image that
 * reads "PROFILE 03" is a wrong reference that is visible from across the room,
 * without opening the record.
 *
 *   php Build/Scripts/generateSeedFiles.php          # write the files
 *   php Build/Scripts/generateSeedFiles.php --list   # show what would be written
 *
 * Output goes to "packages-dev/dev-site/Resources/Public/SeedFiles/", which
 * mirrors the "fileadmin/" tree of a development instance one to one - see
 * "getting the files into an instance" in "docs/development/environment.md".
 *
 * Requires nothing but PHP with "ext-gd" (PNG support). It deliberately does not
 * go through "runTests.sh": it writes committed artifacts, it is run by hand
 * once every few months, and every environment that can run this repository -
 * the host, a DDEV instance, the "typo3/core-testing-*" images - has GD.
 *
 * DETERMINISM, AND WHY THERE IS NO "FILES ARE UNCHANGED" GATE
 *
 * The drawing is deterministic: no randomness, no timestamps, no font file, no
 * resampling. Text is rendered with GD's built-in bitmap font 5, whose glyphs
 * are compiled into GD and are the same everywhere, and is enlarged by drawing
 * one filled rectangle per source pixel - not by "imagecopyresized()", whose
 * interpolation is an implementation detail. The same invocation therefore
 * produces the same picture on any machine.
 *
 * The same picture is not the same bytes. "imagepng()" output depends on the
 * zlib and libpng build behind GD, so a gate analogous to "checkJsBuildClean"
 * would go red on a contributor's machine for no defect at all - a worse outcome
 * than no gate. There is deliberately none, and that is not an oversight: the
 * committed files are the artefact of record and this script is how they came to
 * be. What is worth gating is the pairing of file and database - that every
 * "sys_file" row in the committed sqlite template has a file behind it - and
 * that belongs to the seed, not here.
 *
 * SVG was the alternative, and it loses: an SVG is byte-reproducible by
 * construction, but "pages.media" hands its first file to "f:image", and a seed
 * consisting only of SVGs would never exercise the raster path that every real
 * installation uses. One SVG is generated for exactly that case, next to the
 * PNGs, so both paths are covered.
 */
const SEED_FILES_DIRECTORY = __DIR__ . '/../../packages-dev/dev-site/Resources/Public/SeedFiles';

/**
 * Where the images live inside "SeedFiles/", and inside "fileadmin/" once they
 * are delivered. The seed references them as "1:/academics-seed/<name>".
 */
const IMAGE_FOLDER = 'academics-seed';

/**
 * Folders that must exist in "fileadmin/" but hold no seeded file: the upload
 * targets of the two frontend forms. "academic_jobs" writes a job logo into the
 * first (constant "plugin.tx_academicjobs.jobAvatarImage.uploadFolder"), and
 * "academic_persons_edit" writes a profile image into the second (TypoScript
 * "targetFolder"). Neither creates the folder, so an upload into a missing one
 * fails at the moment somebody tries the form.
 *
 * A dot file keeps them in git and stays invisible in the TYPO3 file list, which
 * hides dot files by default - so the folder arrives and the placeholder does
 * not show up as a seeded file.
 */
const UPLOAD_FOLDERS = [
    'global-content/jobs/logos',
    'profile-images',
];

/**
 * One entry per generated image, in the order they are written.
 *
 * "usage" is drawn into the picture and is the table column the file belongs to,
 * so a misplaced reference names the field it should have gone to. "hue" indexes
 * the palette below and is unique per file, so eight portraits are eight
 * different pictures rather than one picture eight times.
 *
 * @return list<array{name: string, width: int, height: int, ratio: string, hue: int, headline: string, usage: string}>
 */
function imageSpecifications(): array
{
    $specifications = [];

    // Profiles: 3:4 portrait, the ratio a person image is cropped to.
    for ($i = 1; $i <= 8; $i++) {
        $specifications[] = [
            'name' => sprintf('profile-%02d.png', $i),
            'width' => 600,
            'height' => 800,
            'ratio' => '3:4',
            'hue' => count($specifications),
            'headline' => sprintf('PROFILE %02d', $i),
            'usage' => 'tx_academicpersons_domain_model_profile.image',
        ];
    }

    // Page media: 16:9, what a page header renders.
    for ($i = 1; $i <= 8; $i++) {
        $specifications[] = [
            'name' => sprintf('media-%02d.png', $i),
            'width' => 1600,
            'height' => 900,
            'ratio' => '16:9',
            'hue' => count($specifications),
            'headline' => sprintf('PAGE MEDIA %02d', $i),
            'usage' => 'pages.media',
        ];
    }

    // Content element assets: 3:2, the ratio of an ordinary photograph.
    for ($i = 1; $i <= 4; $i++) {
        $specifications[] = [
            'name' => sprintf('content-%02d.png', $i),
            'width' => 1200,
            'height' => 800,
            'ratio' => '3:2',
            'hue' => count($specifications),
            'headline' => sprintf('CONTENT %02d', $i),
            'usage' => 'tt_content.assets',
        ];
    }

    // Job logos: square, because a logo is.
    for ($i = 1; $i <= 3; $i++) {
        $specifications[] = [
            'name' => sprintf('logo-%02d.png', $i),
            'width' => 800,
            'height' => 800,
            'ratio' => '1:1',
            'hue' => count($specifications),
            'headline' => sprintf('JOB LOGO %02d', $i),
            'usage' => 'tx_academicjobs_domain_model_job.image',
        ];
    }

    // Partner page media: same shape as the other page media, own name, because
    // the partner pages are a different doktype with their own template.
    for ($i = 1; $i <= 2; $i++) {
        $specifications[] = [
            'name' => sprintf('partner-%02d.png', $i),
            'width' => 1600,
            'height' => 900,
            'ratio' => '16:9',
            'hue' => count($specifications),
            'headline' => sprintf('PARTNER %02d', $i),
            'usage' => 'pages.media',
        ];
    }

    return $specifications;
}

/**
 * The one vector file. "pages.media" has neither an "allowed" list nor a
 * "maxitems", so an SVG is a case a real editor produces - and it is the case
 * where a template that assumes a rasterisable file breaks.
 *
 * @return array{name: string, width: int, height: int, ratio: string, hue: int, headline: string, usage: string}
 */
function vectorSpecification(): array
{
    return [
        'name' => 'media-09.svg',
        'width' => 1600,
        'height' => 900,
        'ratio' => '16:9',
        'hue' => 25,
        'headline' => 'PAGE MEDIA 09',
        'usage' => 'pages.media (vector)',
    ];
}

/**
 * The one audio file. "module.audio_file" is the only "type: file" column in
 * this repository whose "allowed" list is not images, so it is the only proof
 * that the non-image FAL path is wired at all.
 *
 * No PDF: "pages.media" accepts one, but ImageMagick's shipped "policy.xml"
 * refuses to rasterise PDFs on most distributions, so a seeded PDF would show an
 * error that belongs to the host rather than to these extensions.
 *
 * @return array{name: string, sampleRate: int, samples: int, frequency: int}
 */
function audioSpecification(): array
{
    return [
        'name' => 'module-audio.wav',
        'sampleRate' => 8000,
        'samples' => 4000, // 0.5 s
        'frequency' => 440,
    ];
}

/**
 * Twenty-six distinct base colours, one per generated image. Picked by hand for
 * distance from each other rather than generated from a formula, because "these
 * two look alike" is the only property that matters and no formula guarantees
 * it.
 *
 * @return list<array{int, int, int}>
 */
function palette(): array
{
    return [
        [0xC0, 0x39, 0x2B], [0x27, 0x74, 0xC0], [0x1E, 0x8E, 0x5A], [0xB7, 0x6E, 0x0B],
        [0x7D, 0x3C, 0x98], [0x0E, 0x7C, 0x86], [0xB0, 0x30, 0x60], [0x4A, 0x5D, 0x23],
        [0xD3, 0x54, 0x00], [0x2C, 0x3E, 0x50], [0x16, 0xA0, 0x85], [0x8E, 0x44, 0xAD],
        [0xC2, 0x1E, 0x56], [0x1A, 0x5C, 0x9E], [0x6B, 0x8E, 0x23], [0xA9, 0x3F, 0x0C],
        [0x34, 0x49, 0x5E], [0x0F, 0x6E, 0x4C], [0x95, 0x2B, 0x8A], [0xB8, 0x86, 0x0B],
        [0x2E, 0x86, 0xAB], [0x7F, 0x1D, 0x2E], [0x45, 0x7B, 0x2C], [0x5B, 0x2C, 0x83],
        [0x00, 0x6D, 0x77], [0xA0, 0x4E, 0x1F],
    ];
}

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
    $arguments = array_slice($argv, 1);
    $listOnly = in_array('--list', $arguments, true);

    if (array_intersect(['-h', '--help'], $arguments) !== []) {
        printf(
            "Usage: generateSeedFiles.php [--list]\n\n"
            . "Writes the placeholder files of the development seed into\n"
            . "  %s\n\n"
            . "  --list   print the file table without writing anything\n",
            relativePath(SEED_FILES_DIRECTORY),
        );
        return 0;
    }

    if (!$listOnly && !extension_loaded('gd')) {
        fwrite(STDERR, "This script needs PHP with \"ext-gd\". It is not loaded.\n");
        return 1;
    }
    if (!$listOnly && (gd_info()['PNG Support'] ?? false) !== true) {
        fwrite(STDERR, "This script needs GD with PNG support. It was built without.\n");
        return 1;
    }

    $imageDirectory = SEED_FILES_DIRECTORY . '/' . IMAGE_FOLDER;
    if (!$listOnly && !is_dir($imageDirectory) && !mkdir($imageDirectory, 0775, true)) {
        fwrite(STDERR, sprintf("Could not create \"%s\".\n", $imageDirectory));
        return 1;
    }

    $written = [];

    foreach (imageSpecifications() as $specification) {
        $file = $imageDirectory . '/' . $specification['name'];
        if (!$listOnly) {
            $image = drawImage($specification);
            $success = imagepng($image, $file, 9);
            imagedestroy($image);
            if (!$success) {
                fwrite(STDERR, sprintf("Could not write \"%s\".\n", $file));
                return 1;
            }
        }
        $written[] = $file;
    }

    $vector = vectorSpecification();
    $vectorFile = $imageDirectory . '/' . $vector['name'];
    if (!$listOnly && file_put_contents($vectorFile, buildVector($vector)) === false) {
        fwrite(STDERR, sprintf("Could not write \"%s\".\n", $vectorFile));
        return 1;
    }
    $written[] = $vectorFile;

    $audio = audioSpecification();
    $audioFile = $imageDirectory . '/' . $audio['name'];
    if (!$listOnly && file_put_contents($audioFile, buildWave($audio)) === false) {
        fwrite(STDERR, sprintf("Could not write \"%s\".\n", $audioFile));
        return 1;
    }
    $written[] = $audioFile;

    foreach (UPLOAD_FOLDERS as $folder) {
        $keepFile = SEED_FILES_DIRECTORY . '/' . $folder . '/.gitkeep';
        if (!$listOnly) {
            $directory = dirname($keepFile);
            if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
                fwrite(STDERR, sprintf("Could not create \"%s\".\n", $directory));
                return 1;
            }
            if (file_put_contents($keepFile, uploadFolderPlaceholder($folder)) === false) {
                fwrite(STDERR, sprintf("Could not write \"%s\".\n", $keepFile));
                return 1;
            }
        }
        $written[] = $keepFile;
    }

    report($written, $listOnly);

    return reportUnknownFiles($written, $listOnly);
}

/**
 * @param array{name: string, width: int, height: int, ratio: string, hue: int, headline: string, usage: string} $specification
 */
function drawImage(array $specification): \GdImage
{
    $width = $specification['width'];
    $height = $specification['height'];

    // A palette image, not a true colour one: these pictures use four colours,
    // and an 8 bit PNG of a flat surface is a fifth of the size of a 24 bit one.
    $image = imagecreate($width, $height);
    if (!$image instanceof \GdImage) {
        throw new \RuntimeException(sprintf('Could not create a %dx%d image.', $width, $height), 1755937100);
    }

    $base = palette()[$specification['hue'] % count(palette())];
    // The first allocated colour of a palette image fills the canvas.
    $background = allocate($image, mix($base, [0xFF, 0xFF, 0xFF], 84));
    $frame = allocate($image, $base);
    $text = allocate($image, mix($base, [0x00, 0x00, 0x00], 45));
    $band = allocate($image, mix($base, [0xFF, 0xFF, 0xFF], 30));

    $border = max(8, intdiv(min($width, $height), 24));
    imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, $frame);
    imagefilledrectangle($image, $border, $border, $width - $border - 1, $height - $border - 1, $background);

    // The width a text line may use: the framed area, minus one border width of
    // padding on each side.
    $available = $width - 4 * $border;
    $dimensions = sprintf('%d x %d px - %s', $width, $height, $specification['ratio']);

    // Each line is fitted to its own share of the width and then capped at the
    // scale of the line above it, so a short field name does not end up drawn
    // larger than the headline just because it has fewer characters.
    $headlineScale = fitScale($specification['headline'], $available, 85);
    $nameScale = min($headlineScale, fitScale($specification['name'], $available, 55));
    $usageScale = min($nameScale, fitScale($specification['usage'], $available, 75));
    $lines = [
        [$specification['headline'], $headlineScale],
        [$specification['name'], $nameScale],
        [$specification['usage'], $usageScale],
        [$dimensions, min($usageScale, fitScale($dimensions, $available, 45))],
    ];

    $glyphHeight = imagefontheight(5);
    $gap = max(6, intdiv($height, 40));
    $blockHeight = -$gap;
    foreach ($lines as [, $scale]) {
        $blockHeight += $glyphHeight * $scale + $gap;
    }

    $y = intdiv($height - $blockHeight, 2);

    // A band behind the last line - the dimensions - so that a thumbnail cropped
    // to a square still shows two different shades of the file's own hue and is
    // told apart from its neighbour at thumbnail size.
    $lastLineHeight = $glyphHeight * $lines[count($lines) - 1][1];
    $lastLineTop = $y + $blockHeight - $lastLineHeight;
    $padding = intdiv($gap, 2);
    imagefilledrectangle(
        $image,
        $border,
        $lastLineTop - $padding,
        $width - $border - 1,
        $lastLineTop + $lastLineHeight + $padding,
        $band,
    );

    foreach ($lines as [$line, $scale]) {
        $x = intdiv($width - textWidth($line, $scale), 2);
        drawScaledText($image, $x, $y, $line, $scale, $text);
        $y += $glyphHeight * $scale + $gap;
    }

    return $image;
}

/**
 * Enlarges GD's built-in bitmap font 5 by drawing one filled rectangle per
 * source pixel.
 *
 * "imagecopyresized()" would be the obvious way and is not used on purpose: how
 * it maps source to destination pixels is an implementation detail of GD, while
 * a rectangle per pixel is arithmetic. The built-in font itself is compiled into
 * GD, so there is no font file whose version could change the picture either.
 */
function drawScaledText(\GdImage $image, int $x, int $y, string $text, int $scale, int $color): void
{
    $font = 5;
    $glyphWidth = imagefontwidth($font);
    $glyphHeight = imagefontheight($font);
    $width = $glyphWidth * strlen($text);

    $buffer = imagecreatetruecolor(max(1, $width), $glyphHeight);
    if (!$buffer instanceof \GdImage) {
        throw new \RuntimeException('Could not create the text buffer.', 1755937101);
    }
    imagefilledrectangle($buffer, 0, 0, max(1, $width) - 1, $glyphHeight - 1, imagecolorallocate($buffer, 0, 0, 0));
    imagestring($buffer, $font, 0, 0, $text, imagecolorallocate($buffer, 255, 255, 255));

    for ($row = 0; $row < $glyphHeight; $row++) {
        for ($column = 0; $column < $width; $column++) {
            if ((imagecolorat($buffer, $column, $row) & 0xFF) === 0) {
                continue;
            }
            imagefilledrectangle(
                $image,
                $x + $column * $scale,
                $y + $row * $scale,
                $x + ($column + 1) * $scale - 1,
                $y + ($row + 1) * $scale - 1,
                $color,
            );
        }
    }

    imagedestroy($buffer);
}

/**
 * The largest whole scale factor at which the line still fits into the given
 * percentage of the available width. Whole numbers only - a fractional one would
 * mean resampling, and that is what the drawing avoids. The result is at least
 * 1, so a long line overflows rather than disappearing.
 */
function fitScale(string $text, int $available, int $percent): int
{
    $unscaledWidth = imagefontwidth(5) * max(1, strlen($text));

    return max(1, intdiv($available * $percent, 100 * $unscaledWidth));
}

function textWidth(string $text, int $scale): int
{
    return imagefontwidth(5) * strlen($text) * $scale;
}

/**
 * @param array{int, int, int} $color
 */
function allocate(\GdImage $image, array $color): int
{
    $index = imagecolorallocate($image, $color[0], $color[1], $color[2]);
    if ($index === false) {
        throw new \RuntimeException('Could not allocate a colour.', 1755937102);
    }

    return $index;
}

/**
 * @param array{int, int, int} $color
 * @param array{int, int, int} $towards
 * @return array{int, int, int}
 */
function mix(array $color, array $towards, int $percent): array
{
    return [
        intdiv($color[0] * (100 - $percent) + $towards[0] * $percent, 100),
        intdiv($color[1] * (100 - $percent) + $towards[1] * $percent, 100),
        intdiv($color[2] * (100 - $percent) + $towards[2] * $percent, 100),
    ];
}

/**
 * @param array{name: string, width: int, height: int, ratio: string, hue: int, headline: string, usage: string} $specification
 */
function buildVector(array $specification): string
{
    $width = $specification['width'];
    $height = $specification['height'];
    $base = palette()[$specification['hue'] % count(palette())];
    $background = rgb(mix($base, [0xFF, 0xFF, 0xFF], 84));
    $frame = rgb($base);
    $text = rgb(mix($base, [0x00, 0x00, 0x00], 45));
    $border = intdiv(min($width, $height), 24);

    // "width", "height" and "viewBox" are all three present on purpose: which of
    // them a dimension extractor reads differs between core versions, and a
    // vector without dimensions indexes as 0x0 and renders as nothing.
    return sprintf(
        <<<'SVG'
            <?xml version="1.0" encoding="UTF-8"?>
            <svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%2$d" viewBox="0 0 %1$d %2$d">
              <rect x="0" y="0" width="%1$d" height="%2$d" fill="%3$s"/>
              <rect x="%4$d" y="%4$d" width="%5$d" height="%6$d" fill="%7$s"/>
              <g fill="%8$s" font-family="monospace" text-anchor="middle">
                <text x="%9$d" y="%10$d" font-size="110" font-weight="bold">%11$s</text>
                <text x="%9$d" y="%12$d" font-size="60">%13$s</text>
                <text x="%9$d" y="%14$d" font-size="46">%15$s</text>
                <text x="%9$d" y="%16$d" font-size="38">%17$d x %18$d px - %19$s</text>
              </g>
            </svg>

            SVG,
        $width,
        $height,
        $frame,
        $border,
        $width - 2 * $border,
        $height - 2 * $border,
        $background,
        $text,
        intdiv($width, 2),
        intdiv($height * 40, 100),
        htmlspecialchars($specification['headline'], ENT_XML1),
        intdiv($height * 52, 100),
        htmlspecialchars($specification['name'], ENT_XML1),
        intdiv($height * 62, 100),
        htmlspecialchars($specification['usage'], ENT_XML1),
        intdiv($height * 71, 100),
        $width,
        $height,
        $specification['ratio'],
    );
}

/**
 * @param array{int, int, int} $color
 */
function rgb(array $color): string
{
    return sprintf('#%02x%02x%02x', $color[0], $color[1], $color[2]);
}

/**
 * A 8 bit unsigned mono PCM sine, written as a 44 byte RIFF header plus samples
 * computed from a closed formula. Nothing here depends on an encoder.
 *
 * @param array{name: string, sampleRate: int, samples: int, frequency: int} $specification
 */
function buildWave(array $specification): string
{
    $samples = '';
    for ($i = 0; $i < $specification['samples']; $i++) {
        $angle = 2 * M_PI * $specification['frequency'] * $i / $specification['sampleRate'];
        $samples .= chr(max(0, min(255, (int)round(128 + 100 * sin($angle)))));
    }

    $length = strlen($samples);
    $channels = 1;
    $bitsPerSample = 8;
    $blockAlign = intdiv($channels * $bitsPerSample, 8);

    return 'RIFF'
        . pack('V', 36 + $length)
        . 'WAVE'
        . 'fmt '
        . pack('V', 16)
        . pack('v', 1) // PCM
        . pack('v', $channels)
        . pack('V', $specification['sampleRate'])
        . pack('V', $specification['sampleRate'] * $blockAlign)
        . pack('v', $blockAlign)
        . pack('v', $bitsPerSample)
        . 'data'
        . pack('V', $length)
        . $samples;
}

function uploadFolderPlaceholder(string $folder): string
{
    return sprintf(
        "This folder exists because \"1:/%s/\" is an upload target of a frontend\n"
        . "form and nothing creates it at runtime. It is copied into an instance's\n"
        . "\"fileadmin/\" together with the seeded files, and it is invisible in the\n"
        . "TYPO3 file list, which hides dot files.\n\n"
        . "Written by \"Build/Scripts/generateSeedFiles.php\".\n",
        $folder,
    );
}

/**
 * @param list<string> $files
 */
function report(array $files, bool $listOnly): void
{
    $total = 0;
    foreach ($files as $file) {
        $size = is_file($file) ? (int)filesize($file) : 0;
        $total += $size;
        printf("%-8s %s\n", $listOnly ? '' : formatBytes($size), relativePath($file));
    }

    printf(
        "\n%d files%s.\n",
        count($files),
        $listOnly ? '' : sprintf(', %s in total', formatBytes($total)),
    );
}

/**
 * Names files below the target directory that this script did not write. It does
 * not delete them: a leftover is usually a rename that wants a look, not a
 * cleanup that wants doing silently.
 *
 * @param list<string> $written
 */
function reportUnknownFiles(array $written, bool $listOnly): int
{
    if ($listOnly || !is_dir(SEED_FILES_DIRECTORY)) {
        return 0;
    }

    $known = array_flip(array_filter(array_map('realpath', $written)));
    $unknown = [];
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(SEED_FILES_DIRECTORY, \FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $item) {
        if ($item instanceof \SplFileInfo && $item->isFile() && !isset($known[$item->getRealPath()])) {
            $unknown[] = relativePath($item->getPathname());
        }
    }

    if ($unknown === []) {
        return 0;
    }

    sort($unknown);
    fwrite(STDERR, sprintf(
        "\nThese files are in the target directory and were not written by this script:\n  %s\n"
        . "Remove them by hand if they are leftovers of a rename.\n",
        implode("\n  ", $unknown),
    ));

    return 1;
}

function formatBytes(int $bytes): string
{
    return $bytes < 1024 ? sprintf('%d B', $bytes) : sprintf('%.1f kB', $bytes / 1024);
}

function relativePath(string $path): string
{
    $root = realpath(__DIR__ . '/../..');
    $real = realpath($path);
    if ($root === false || $real === false || !str_starts_with($real, $root . '/')) {
        return $path;
    }

    return substr($real, strlen($root) + 1);
}

exit(main($argv));
