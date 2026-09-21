import assert from "node:assert/strict";
import { beforeEach, describe, it } from "node:test";
import { createKeyboardEvent, resetBody, settle, setViewportWidth } from "../../../../../../Build/tests/dom.mjs";

/**
 * The harness's own tests: that the window "register.mjs" installs is the one
 * the shipped modules are written against.
 *
 * They live below an extension because "node --test" is pointed at
 * "packages/<vendor>/<package>/Tests/JavaScript/", and "Build/tests/" is not an
 * extension. "academic_study_plan" is the extension this suite was built for on
 * this branch - it ships the only module here that does more than configure a
 * library loaded from elsewhere.
 *
 * Every assertion stands for a browser behaviour a shipped module relies on
 * without importing anything. Where jsdom does not provide it, "dom.mjs" models
 * it - and a model that is wrong is worse than an absent one, because it turns
 * a defect into a green test.
 */
describe("the installed DOM", () => {
  beforeEach(() => {
    resetBody();
  });

  it("gives a module a document while it is being evaluated", () => {
    // The proof is the import above: "register.mjs" installs the window before
    // node loads the first test file, and a module that reached for "document"
    // at evaluation time would already have failed.
    assert.equal(typeof document, "object");
    assert.equal(document.body.tagName, "BODY");
  });

  it("puts exactly the globals the shipped modules reach for on globalThis", () => {
    // The list is short on purpose: four frontend modules, and these are what
    // they use unqualified. A source reaching for something else has to add it
    // to "dom.mjs" rather than discover a node global of the same name.
    for (const name of ["Element", "Event", "HTMLDialogElement", "HTMLElement", "HTMLTextAreaElement", "Node"]) {
      assert.equal(typeof (globalThis as Record<string, unknown>)[name], "function", name);
    }
    assert.equal(document.createElement("textarea") instanceof HTMLTextAreaElement, true);
  });

  it("replaces the body per test rather than the window", () => {
    const first = resetBody("<p id=\"one\">one</p>");
    assert.notEqual(document.getElementById("one"), null);

    const second = resetBody("<p id=\"two\">two</p>");

    assert.equal(document.getElementById("one"), null);
    assert.notEqual(document.getElementById("two"), null);
    // The same body element throughout: a fresh window per test would leave the
    // module level state of a shipped module pointing at a document nobody sees.
    assert.equal(first, second);
  });

  it("hands over a keyboard event the window itself constructed", () => {
    const event = createKeyboardEvent("keydown", { key: "Enter" });

    assert.equal(event.type, "keydown");
    assert.equal(event.key, "Enter");
    assert.equal(event.bubbles, true);
    assert.equal(event.cancelable, true);
    assert.equal(event instanceof window.KeyboardEvent, true);
  });

  it("lets a test choose the viewport width", () => {
    const wide = window.innerWidth;

    setViewportWidth(500);
    assert.equal(window.innerWidth, 500);

    setViewportWidth(wide);
    assert.equal(window.innerWidth, wide);
  });

  it("settles the microtasks a handler queued", async () => {
    let done = false;
    void Promise.resolve().then(() => Promise.resolve()).then(() => {
      done = true;
    });

    assert.equal(done, false);
    await settle();
    assert.equal(done, true);
  });
});

describe("what jsdom does not provide", () => {
  beforeEach(() => {
    resetBody();
  });

  it("opens and closes a dialog through the attribute jsdom reflects", () => {
    const body = resetBody('<dialog id="d"><button>Close</button></dialog>');
    const dialog = body.querySelector<HTMLDialogElement>("#d");
    assert.ok(dialog !== null);

    assert.equal(dialog.open, false);

    dialog.showModal();

    // "open" is jsdom's own reflection of the attribute the model sets, so a
    // module reading it reads what a browser would.
    assert.equal(dialog.open, true);
    assert.equal(dialog.hasAttribute("open"), true);
    // Which of the two ways it was opened, which nothing else can observe.
    assert.equal(dialog.getAttribute("data-test-dialog"), "modal");
    // The opening moves the focus into the dialog, as a browser does.
    assert.equal(document.activeElement, dialog.querySelector("button"));

    let closed = 0;
    dialog.addEventListener("close", () => {
      closed += 1;
    });
    dialog.close("done");

    assert.equal(dialog.open, false);
    assert.equal(dialog.getAttribute("data-test-dialog"), null);
    assert.equal(dialog.returnValue, "done");
    assert.equal(closed, 1);

    // A dialog that is already closed fires nothing, exactly as in a browser.
    dialog.close();

    assert.equal(closed, 1);
  });

  it("reports the non-modal open of a dialog as such", () => {
    const body = resetBody("<dialog><p>Note</p></dialog>");
    const dialog = body.querySelector<HTMLDialogElement>("dialog");
    assert.ok(dialog !== null);

    dialog.show();

    assert.equal(dialog.open, true);
    assert.equal(dialog.getAttribute("data-test-dialog"), "open");
  });

  it("refuses to open a shown dialog as a modal, and ignores a repeat", () => {
    const body = resetBody("<dialog><p>Note</p></dialog>");
    const dialog = body.querySelector<HTMLDialogElement>("dialog");
    assert.ok(dialog !== null);

    dialog.show();

    // The one mistake a module can make by accident: a dialog that is already
    // open without a backdrop does not gain one, it throws.
    assert.throws(() => dialog.showModal(), /already open/);
    dialog.show();
    assert.equal(dialog.getAttribute("data-test-dialog"), "open");
  });

  it("skips a disabled control when it moves the focus into a dialog", () => {
    const body = resetBody(
      '<dialog id="d"><button disabled>No</button><button id="yes">Yes</button></dialog>',
    );
    const dialog = body.querySelector<HTMLDialogElement>("#d");
    assert.ok(dialog !== null);

    dialog.showModal();

    assert.equal(document.activeElement, body.querySelector("#yes"));
  });

  it("refuses to open a detached dialog as a modal", () => {
    const dialog = document.createElement("dialog");

    assert.throws(() => dialog.showModal(), /not in a document/);
    assert.equal(dialog.open, false);
  });
});
