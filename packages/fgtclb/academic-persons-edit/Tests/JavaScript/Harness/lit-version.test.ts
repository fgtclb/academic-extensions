import assert from "node:assert/strict";
import { existsSync, readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { describe, it } from "node:test";

/**
 * The early warning under the Lit pin.
 *
 * TYPO3 core delivers Lit through the import map, so the browser runs core's
 * copy and nothing in this repository chooses its version. The tests run node,
 * which cannot reach an import map, so "Build/package.json" pins a development
 * copy instead - and the whole value of testing a Lit element depends on those
 * two being the same Lit.
 *
 * Nothing else notices when they drift apart. A core update that ships a newer
 * Lit changes what the browser runs and leaves the pinned copy where it was;
 * the tests stay green and stop describing the shipped behaviour. This test is
 * what turns that into a failure with the two version numbers in the message.
 *
 * The three packages are pinned through npm "overrides" because "lit" is a
 * facade: it re-exports "lit-element", "lit-html" and "@lit/reactive-element",
 * and only those three carry a version marker of their own.
 *
 * See "docs/testing/javascript-tests.md".
 */
const repositoryRoot = fileURLToPath(new URL("../../../../../../", import.meta.url));
const contrib = `${repositoryRoot}.Build/vendor/typo3/cms-core/Resources/Public/JavaScript/Contrib/`;

/**
 * Where each version is written down, on both sides.
 *
 * The core side is read out of the delivered source rather than out of a
 * manifest, because there is none: "Contrib/" holds bare ES modules. Each one
 * registers itself on a global so that a page loading two copies warns, and
 * that registration carries the version.
 */
const packages = [
  { name: "lit-html", file: "lit-html/lit-html.js", marker: "litHtmlVersions" },
  { name: "lit-element", file: "lit-element/lit-element.js", marker: "litElementVersions" },
  { name: "@lit/reactive-element", file: "@lit/reactive-element/reactive-element.js", marker: "reactiveElementVersions" },
];

const versionShippedByCore = (file: string, marker: string): string => {
  const source = readFileSync(`${contrib}${file}`, "utf8");
  const found = new RegExp(`${marker}\\s*\\?\\?=\\s*\\[\\]\\)\\.push\\("([^"]+)"\\)`).exec(source);
  assert.ok(
    found !== null,
    `No "${marker}" version marker in "${file}". Core changed how it delivers Lit; teach this test the new shape rather than deleting it.`,
  );

  return found[1];
};

const versionInstalledHere = (name: string): string => {
  const manifest = `${repositoryRoot}Build/node_modules/${name}/package.json`;
  assert.ok(existsSync(manifest), `"${name}" is not installed. Run the suite through "runTests.sh -s testJs".`);

  return (JSON.parse(readFileSync(manifest, "utf8")) as { version: string }).version;
};

describe("the pinned Lit", () => {
  it("is pinned exactly, never as a range", () => {
    const manifest = JSON.parse(readFileSync(`${repositoryRoot}Build/package.json`, "utf8")) as {
      devDependencies: Record<string, string>;
      overrides: Record<string, string>;
    };

    assert.match(manifest.devDependencies.lit, /^\d+\.\d+\.\d+$/);
    for (const { name } of packages) {
      assert.match(
        manifest.overrides[name] ?? "",
        /^\d+\.\d+\.\d+$/,
        `"${name}" needs an exact override: "lit" itself only carries a range for it.`,
      );
    }
  });

  it("matches what the installed TYPO3 core delivers", (context) => {
    if (!existsSync(contrib)) {
      // The node suites run without a "composerUpdate", so there is nothing to
      // compare against in a bare checkout or in the "frontend-assets" job of
      // CI. The guard bites in the local gate chain, where a core is installed
      // - and a skipped test says so rather than passing quietly.
      context.skip("No TYPO3 core is installed; run a composerUpdate first.");

      return;
    }

    for (const { name, file, marker } of packages) {
      assert.equal(
        versionInstalledHere(name),
        versionShippedByCore(file, marker),
        `"${name}" differs between "Build/package.json" and the installed TYPO3 core. `
          + "Repin it in \"Build/package.json\" (and its \"overrides\") to the version core ships.",
      );
    }
  });
});
