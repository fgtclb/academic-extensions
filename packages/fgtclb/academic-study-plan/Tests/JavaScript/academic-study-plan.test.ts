import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { createKeyboardEvent, resetBody, settle } from "../../../../../Build/tests/dom.mjs";

/**
 * The study plan interaction, driven against the markup the content element
 * layout produces.
 *
 * Since ACE-702 the element renders through the "Default" layout of
 * EXT:fluid_styled_content, so two things sit around the study plan that were
 * not there before: the frame wrapper, and the content element header as a
 * bare "<header>" element in front of the ".academic-study-plan" container
 * rather than inside it. The filter and the accordion are looked up from that
 * container, so neither may reach them - and the layout's "<header>" must not
 * be mistaken for one of the ".header" semester headers the accordion toggles.
 * (The module dialogs are looked up from the document instead, which the frame
 * wrapper cannot affect either way.)
 *
 * The markup below is the shape "AcademicStudyPlanContentElementTest" asserts
 * in the rendered page, reduced to the elements this module selects.
 */
const layoutMarkup = (): string =>
  '<div id="c1" class="frame frame-ruler-before frame-type-academic_study_plan frame-layout-0">' +
  "<header><h2>Study plan B.Sc.</h2></header>" +
  '<div class="academic-study-plan container" data-study-plan="1">' +
  '<nav><ul class="filter"><li hidden>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder"' +
  ' style="--category-color: category-color-placeholder;">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<ul class="semesters row">' +
  '<li class="col">' +
  '<div class="header" aria-hidden="true" inert>First Semester</div>' +
  "<ul>" +
  '<li class="module" data-categories=\'[{"uid":1,"label":"Mandatory","colour":"#cc0000"}]\'>Mathematics I</li>' +
  '<li class="module" data-categories=\'[{"uid":2,"label":"Elective","colour":"#0066cc"}]\'>Programming Basics</li>' +
  "</ul>" +
  "</li>" +
  "</ul>" +
  "</div>" +
  "</div>";

/**
 * The same page with a category whose backend title carries markup. An editor
 * writes that title, and the script substitutes it into the filter item the
 * template rendered - so the title must arrive as text and never as markup.
 */
const hostileCategoryMarkup = (): string =>
  '<div class="academic-study-plan container" data-study-plan="2">' +
  '<nav><ul class="filter"><li hidden>' +
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

/**
 * Puts the markup in the document and starts the module on it.
 *
 * The module runs on import, as "f:asset.module" loads it: async, after the
 * document was parsed. The harness window is already in that state, so the
 * import reproduces the late load without arranging anything - and the module's
 * own "readyState" branch is the one a browser takes here. Node hands every
 * test of this file the same module instance, so every test after the first one
 * starts it through the exported initialiser instead.
 */
const start = async (markup: string): Promise<void> => {
  resetBody(markup);
  assert.notEqual(document.readyState, "loading");
  const { init } = await import("@fgtclb/academic-study-plan/frontend/academic-study-plan.js");
  init();
  await settle();
};

describe("the study plan inside the content element layout", () => {
  it("renders a category title as text, whatever it contains", async () => {
    await start(hostileCategoryMarkup());

    const button = document.querySelector<HTMLButtonElement>(".filter button");
    assert.ok(button !== null, "the filter was not built");

    // The title is the button's text, with its angle brackets intact - and it
    // is not markup: nothing it names reached the document.
    assert.equal(
      button.textContent,
      '<img src=x onerror="globalThis.__studyPlanInjected = true"><b class="injected">x</b>',
    );
    assert.equal(document.querySelector(".injected"), null);
    assert.equal(document.querySelector("img"), null);
    assert.equal(button.getAttribute("aria-label"), `Filter by category: ${button.textContent}`);
    assert.equal("__studyPlanInjected" in globalThis, false);

    // A colour is written into a "style" attribute, so a value that could close
    // the declaration and open another is dropped rather than passed through.
    assert.equal(button.getAttribute("data-category-color"), "");
    assert.equal(button.getAttribute("style"), "--category-color: ;");
  });

  it("filters by category and leaves the layout header alone", async () => {
    await start(layoutMarkup());

    // The filter was rebuilt from the categories the modules carry, which only
    // works when the container was found through the frame wrapper.
    const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>(".filter button"));
    assert.deepEqual(
      buttons.map((button) => button.textContent),
      ["Mandatory", "Elective"],
    );

    // The item Fluid renders is "hidden", so that its placeholder text is not on
    // screen on a page this module never reaches. The clones built from it are
    // real filter buttons, so the attribute must be gone from every one of them.
    assert.deepEqual(
      Array.from(document.querySelectorAll<HTMLElement>(".filter li")).map((item) =>
        item.hasAttribute("hidden"),
      ),
      [false, false],
    );

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
    buttons[0].dispatchEvent(new window.MouseEvent("click", { bubbles: true }));
    await settle();

    const modules = Array.from(document.querySelectorAll<HTMLElement>(".module"));
    assert.deepEqual(
      modules.map((module) => module.classList.contains("highlighted")),
      [true, false],
    );
    assert.ok(document.querySelector(".col")?.classList.contains("highlighted"));

    // The same by keyboard, which is the half of the interaction a pointer
    // test never reaches.
    buttons[1].dispatchEvent(createKeyboardEvent("keydown", { key: "Enter" }));
    await settle();

    assert.deepEqual(
      modules.map((module) => module.classList.contains("highlighted")),
      [false, true],
    );
  });
});
