import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { resetBody, settle } from "../../../../../../Build/tests/dom.mjs";

/**
 * The resolve hook of "Build/tests/resolve-hook.mjs", exercised as it is used:
 * registered on node's loader thread by "register.mjs", not re-instantiated
 * here. A second instance would prove that the code can work, not that the
 * harness the other tests run under does.
 *
 * Every specifier below is imported through a variable rather than as a
 * literal, deliberately. TypeScript would have to resolve a literal one at
 * compile time, and what is under test is node's resolution - the shipped
 * modules are not packages, and a specifier that is meant to have no source
 * behind it could not be written as a literal at all.
 */
const importModule = async (specifier: string): Promise<Record<string, unknown>> =>
  (await import(specifier)) as Record<string, unknown>;

describe("the modules of this repository", () => {
  it("resolves the import map specifier to the TypeScript source", async () => {
    const body = resetBody(
      '<div class="academic-study-plan" data-study-plan="99">' +
        '<nav><ul class="filter"><li>' +
        '<button data-category-id="category-id-placeholder">category-label-placeholder</button>' +
        "</li></ul></nav>" +
        '<ul class="semesters"><li class="col"><div class="header"></div><ul>' +
        '<li class="module" data-categories=\'[{"uid":7,"label":"Mandatory","colour":"#cc0000"}]\'>M</li>' +
        "</ul></li></ul>" +
        "</div>",
    );

    await importModule("@fgtclb/academic-study-plan/frontend/academic-study-plan.js");
    await settle();

    // The module initialises on evaluation, so this asserts three things at
    // once: the specifier resolved, node stripped the types of a ".ts" file
    // reached through a ".js" specifier, and the DOM was installed before the
    // module ran.
    const button = body.querySelector(".filter button");
    assert.ok(button !== null);
    assert.equal(button.textContent?.trim(), "Mandatory");
  });

  it("names the specifier and the file it looked for when there is no source", async () => {
    await assert.rejects(
      () => importModule("@fgtclb/academic-study-plan/frontend/nowhere.js"),
      (error: Error) => {
        assert.match(
          error.message,
          /No TypeScript source for the module specifier "@fgtclb\/academic-study-plan\/frontend\/nowhere\.js"/,
        );
        assert.match(
          error.message,
          /academic-study-plan\/Resources\/Private\/TypeScript\/frontend\/nowhere\.ts/,
        );

        return true;
      },
    );
  });

  it("leaves a specifier that is not one of ours to node", async () => {
    // The distinction the error above exists for: a typo in a module of this
    // repository is ours to report, an unknown package is node's.
    await assert.rejects(
      () => importModule("@fgtclb/not-an-extension/frontend/anything.js"),
      /Cannot find package/,
    );
  });
});
