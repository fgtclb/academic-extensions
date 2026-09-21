import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { createKeyboardEvent, resetBody, settle, setViewportWidth } from "../../../../../Build/tests/dom.mjs";

/**
 * The study plan interaction, driven against the markup the content element
 * renders.
 *
 * Since ACE-702 the element renders through the "Default" layout of
 * EXT:fluid_styled_content, so two things sit around the study plan that were
 * not there before: the frame wrapper, and the content element header as a
 * bare "<header>" element in front of the ".academic-study-plan" container
 * rather than inside it. The filter and the accordion are looked up from that
 * container, so neither may reach them - and the layout's "<header>" must not
 * be mistaken for one of the ".header" semester headers the accordion toggles.
 *
 * The markup below is extracted from
 * "Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html",
 * reduced to the elements this module selects: "f:translate" becomes the text
 * it resolves to and "core:icon" becomes nothing. Everything the module queries
 * is kept verbatim, so a template that drops one of them turns these red.
 *
 * The content element uid is an argument so that a test can say which record
 * it is rendering. It no longer decides whether a plan is started: the module
 * keys its instances by the container element since ACE-707, and the test below
 * that gives two plans the same value is what holds it to that.
 */
const layoutMarkup = (identifier: string): string =>
  '<div id="c1" class="frame frame-default frame-type-academic_study_plan frame-layout-0">' +
  "<header><h2>Study plan B.Sc.</h2></header>" +
  '<div class="academic-study-plan container" data-study-plan="' +
  identifier +
  '" data-filter-label="Filter by category">' +
  '<nav><ul class="filter"><li>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder"' +
  ' aria-label="Filter by category: category-label-placeholder"' +
  ' style="--category-color: category-color-placeholder;">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<ul class="semesters row">' +
  '<li class="col">' +
  '<div class="header" aria-hidden="true" inert>First Semester</div>' +
  "<ul>" +
  '<li class="module clickable"' +
  " data-categories='[{\"uid\":1,\"label\":\"Mandatory\",\"colour\":\"#cc0000\"}]'>Mathematics I" +
  '<button class="modal-trigger" data-dialog-id="popup-1"></button>' +
  '<dialog id="popup-1"><button>Close</button></dialog>' +
  "</li>" +
  '<li class="module"' +
  " data-categories='[{\"uid\":2,\"label\":\"Elective\",\"colour\":\"#0066cc\"}]'>Programming Basics</li>" +
  "</ul>" +
  "</li>" +
  "</ul>" +
  "</div>" +
  "</div>";

/**
 * The same page with a category whose backend title carries markup. An editor
 * writes that title, and the script substitutes it into the filter item the
 * template rendered - so the title must arrive as text and never as markup.
 * This is the defect ACE-705 fixed, and the test it could not bring with it
 * because this branch had no suite that runs JavaScript.
 */
const hostileCategoryMarkup = (): string =>
  '<div class="academic-study-plan container" data-study-plan="4">' +
  '<nav><ul class="filter"><li>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder"' +
  ' aria-label="Filter by category: category-label-placeholder"' +
  ' style="--category-color: category-color-placeholder;">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<ul class="semesters row"><li class="col">' +
  '<div class="header" aria-hidden="true" inert>First Semester</div>' +
  "<ul>" +
  '<li class="module" data-categories=\'[{"uid":1,' +
  '"label":"<img src=x onerror=\\"globalThis.__studyPlanInjected = true\\"><b class=\\"injected\\">x</b>",' +
  '"colour":"#cc0000; background: url(evil)"}]\'>Mathematics I</li>' +
  "</ul>" +
  "</li></ul>" +
  "</div>";

const SPECIFIER = "@fgtclb/academic-study-plan/frontend/academic-study-plan.js";

/**
 * Puts the markup in the document and starts the module on it.
 *
 * The module runs on import, as "f:asset.module" loads it: async, after the
 * document was parsed. The harness window is already in that state, so the
 * import reproduces the late load without arranging anything - and the module's
 * own "readyState" branch is the one a browser takes here. Node hands every
 * test of this file the same module instance, so the import only starts it
 * once; the plans of the later tests are started by the exported initialiser.
 */
const start = async (markup: string): Promise<void> => {
  resetBody(markup);
  assert.notEqual(document.readyState, "loading");
  const { init } = await import(SPECIFIER);
  init();
  await settle();
};

const click = (element: Element): void => {
  element.dispatchEvent(new window.MouseEvent("click", { bubbles: true }));
};

const press = (element: Element, key: string): void => {
  element.dispatchEvent(createKeyboardEvent("keydown", { key }));
};

const highlighted = (selector: string): boolean[] =>
  Array.from(document.querySelectorAll(selector)).map((element) =>
    element.classList.contains("highlighted"),
  );

describe("the study plan inside the content element layout", () => {
  it("filters by category and leaves the layout header alone", async () => {
    await start(layoutMarkup("1"));

    // The filter was rebuilt from the categories the modules carry, which only
    // works when the container was found through the frame wrapper.
    const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>(".filter button"));
    assert.deepEqual(
      buttons.map((button) => button.textContent?.trim()),
      ["Mandatory", "Elective"],
    );
    // The label announced to a screen reader is substituted as well.
    assert.equal(buttons[0].getAttribute("aria-label"), "Filter by category: Mandatory");

    // The layout header is not a semester header: it must not have been turned
    // into an accordion control.
    const layoutHeader = document.querySelector("header");
    assert.ok(layoutHeader !== null, "the layout header is gone");
    assert.equal(layoutHeader.getAttribute("role"), null);
    assert.equal(layoutHeader.getAttribute("tabindex"), null);
    // ... while the semester header, inside the container, was prepared. The
    // jsdom window is 1024 wide, above the 768 breakpoint, so "disableMobile()"
    // ran and wrote these two attributes. Asserting them rather than the mere
    // presence of the element is what proves the module reached it through the
    // container.
    const semesterHeader = document.querySelector(".academic-study-plan .header");
    assert.ok(semesterHeader !== null, "the semester header is gone");
    assert.equal(semesterHeader.getAttribute("role"), "");
    assert.equal(semesterHeader.getAttribute("tabindex"), "-1");

    // Activating a category highlights the modules that carry it, and the
    // column they sit in.
    click(buttons[0]);
    await settle();

    assert.deepEqual(highlighted(".module"), [true, false]);
    assert.ok(document.querySelector(".col")?.classList.contains("highlighted"));

    // The same by keyboard, which is the half of the interaction a pointer
    // test never reaches.
    press(buttons[1], "Enter");
    await settle();

    assert.deepEqual(highlighted(".module"), [false, true]);
  });

  it("opens and closes a module dialog, by pointer and by keyboard", async () => {
    await start(layoutMarkup("2"));

    const dialog = document.querySelector<HTMLDialogElement>("#popup-1");
    assert.ok(dialog !== null, "the dialog is gone");
    assert.equal(dialog.open, false);

    click(document.querySelector(".modal-trigger") as HTMLElement);
    await settle();
    assert.equal(dialog.open, true);
    // As a modal, not as an inline panel: a "show()" would leave the rest of
    // the page operable behind it, and nothing else about the two differs here.
    assert.equal(dialog.getAttribute("data-test-dialog"), "modal");

    // The close button of the dialog is its first button, and it works by
    // keyboard as well - the dialog is opened as a modal, so the pointer is not
    // the only way in.
    press(dialog.querySelector("button") as HTMLElement, " ");
    await settle();
    assert.equal(dialog.open, false);

    press(document.querySelector(".modal-trigger") as HTMLElement, "Enter");
    await settle();
    assert.equal(dialog.open, true);
  });

  it("toggles a semester by keyboard on a narrow viewport", async () => {
    const wide = window.innerWidth;
    setViewportWidth(500);
    try {
      await start(layoutMarkup("3"));

      const header = document.querySelector<HTMLElement>(".academic-study-plan .header");
      assert.ok(header !== null, "the semester header is gone");
      // Below the breakpoint the header is the accordion control.
      assert.equal(header.getAttribute("role"), "button");
      assert.equal(header.getAttribute("tabindex"), "0");
      assert.equal(header.getAttribute("aria-expanded"), "false");

      press(header, "Enter");
      await settle();

      assert.equal(header.getAttribute("aria-expanded"), "true");
      assert.ok(document.querySelector(".col")?.classList.contains("open"));

      press(header, " ");
      await settle();

      assert.equal(header.getAttribute("aria-expanded"), "false");
      assert.equal(document.querySelector(".col")?.classList.contains("open"), false);
    } finally {
      setViewportWidth(wide);
    }
  });

  it("starts every plan of a page, even two that are the same record", async () => {
    // The same content element twice, which an "Insert records" element or a
    // shortcut produces - and, with the attribute left out by an override, two
    // containers that carry no identifier at all.
    await start(layoutMarkup("5") + layoutMarkup("5"));

    const plans = Array.from(document.querySelectorAll<HTMLElement>(".academic-study-plan"));
    assert.equal(plans.length, 2);

    // Both filters were rebuilt from the categories their own modules carry: a
    // plan that was never started still shows the single template item.
    assert.deepEqual(
      plans.map((plan) => Array.from(plan.querySelectorAll(".filter button")).length),
      [2, 2],
    );
    // And both semester headers were prepared by the layout branch.
    assert.deepEqual(
      plans.map((plan) => plan.querySelector(".header")?.getAttribute("tabindex")),
      ["-1", "-1"],
    );

    // Filtering in the second plan leaves the first one alone.
    const second = plans[1].querySelectorAll<HTMLButtonElement>(".filter button");
    click(second[0]);
    await settle();

    assert.deepEqual(
      plans.map((plan) => plan.querySelector(".module")?.classList.contains("highlighted")),
      [false, true],
    );
  });


  it("renders a category title as text, whatever it contains", async () => {
    await start(hostileCategoryMarkup());

    const button = document.querySelector<HTMLButtonElement>(".filter button");
    assert.ok(button !== null, "the filter was not built");

    // The title is the button's text, with its angle brackets intact - and it
    // is not markup: nothing it names reached the document.
    assert.equal(
      button.textContent?.trim(),
      '<img src=x onerror="globalThis.__studyPlanInjected = true"><b class="injected">x</b>',
    );
    assert.equal(document.querySelector(".injected"), null);
    assert.equal(document.querySelector("img"), null);
    assert.equal("__studyPlanInjected" in globalThis, false);

    // A colour is written into a "style" attribute, so a value that could close
    // the declaration and open another is dropped rather than passed through.
    assert.equal(button.getAttribute("data-category-color"), "");
    assert.equal(button.getAttribute("style"), "--category-color: ;");
  });
});
