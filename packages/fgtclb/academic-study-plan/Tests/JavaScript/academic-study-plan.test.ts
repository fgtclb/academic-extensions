import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { createKeyboardEvent, resetBody, settle } from "../../../../../Build/tests/dom.mjs";

/**
 * The study plan interaction, driven against four shapes of markup: the one the
 * content element renders, one that carries only the documented data attributes
 * and no upstream class at all, the class-based markup of version 3.0, and an
 * override that makes the module element itself the dialog trigger.
 *
 * The four together are the markup contract. The module finds every part by a
 * "data-study-plan-*" attribute and falls back to the 3.0 class selector of that
 * part when the attribute is nowhere to be found, so the middle two shapes have
 * to behave identically - and the last one is the reason the trigger is paired
 * with the dialog of its own module rather than with the first one of the page.
 *
 * The markup is extracted from the partials below
 * "Resources/Private/Frontend/Default/Partials/StudyPlan/", reduced to the
 * elements this module selects: "f:translate" becomes the text it resolves to
 * and "core:icon" becomes nothing. The functional test
 * "AcademicStudyPlanContentElementTest::contentElementCarriesTheDataAttributeContract()"
 * asserts the same inventory against the really rendered page, which is what
 * keeps this copy from drifting.
 */
const SPECIFIER = "@fgtclb/academic-study-plan/frontend/academic-study-plan.js";

/**
 * What the content element renders.
 *
 * Since ACE-702 it renders through the "Default" layout of
 * EXT:fluid_styled_content, so two things sit around the study plan that were
 * not there before: the frame wrapper, and the content element header as a
 * bare "<header>" element in front of the container rather than inside it. The
 * filter and the accordion are looked up from that container, so neither may
 * reach them - and the layout's "<header>" must not be mistaken for one of the
 * semester headers the accordion toggles.
 */
const layoutMarkup = (): string =>
  '<div id="c1" class="frame frame-ruler-before frame-type-academic_study_plan frame-layout-0">' +
  "<header><h2>Study plan B.Sc.</h2></header>" +
  '<div class="academic-study-plan container" data-study-plan="1" data-filter-label="Filter by category">' +
  '<nav><ul class="filter" data-study-plan-filter><li hidden data-study-plan-filter-template>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder"' +
  ' style="--category-color: category-color-placeholder;">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<ul class="semesters row">' +
  '<li class="col" data-study-plan-semester>' +
  '<div class="header" aria-hidden="true" inert data-study-plan-semester-header>First Semester</div>' +
  "<ul>" +
  '<li class="module clickable" data-study-plan-module' +
  " data-categories='[{\"uid\":1,\"label\":\"Mandatory\",\"colour\":\"#cc0000\"}]'>Mathematics I" +
  '<button class="modal-trigger" data-study-plan-dialog-trigger data-dialog-id="popup-1"></button>' +
  '<dialog id="popup-1" data-study-plan-dialog><button>Close</button></dialog>' +
  "</li>" +
  '<li class="module" data-study-plan-module' +
  " data-categories='[{\"uid\":2,\"label\":\"Elective\",\"colour\":\"#0066cc\"}]'>Programming Basics</li>" +
  "</ul>" +
  "</li>" +
  "</ul>" +
  "</div>" +
  "</div>";

/**
 * The same plan as an override with class names of its own: nothing an upstream
 * stylesheet selects is left, only the documented attributes.
 */
const attributeMarkup = (): string =>
  '<section class="plan" data-study-plan="9" data-filter-label="Filter by category">' +
  '<ol class="chips" data-study-plan-filter><li hidden data-study-plan-filter-template>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder">category-label-placeholder</button>' +
  "</li></ol>" +
  '<div class="term" data-study-plan-semester>' +
  '<p class="term-title" data-study-plan-semester-header>First Semester</p>' +
  '<article class="course" data-study-plan-module' +
  " data-categories='[{\"uid\":1,\"label\":\"Mandatory\",\"colour\":\"#cc0000\"}]'>Mathematics I" +
  '<button class="more" data-study-plan-dialog-trigger data-dialog-id="course-1"></button>' +
  '<dialog id="course-1" data-study-plan-dialog><button>Close</button></dialog>' +
  "</article>" +
  '<article class="course" data-study-plan-module' +
  " data-categories='[{\"uid\":2,\"label\":\"Elective\",\"colour\":\"#0066cc\"}]'>Programming Basics</article>" +
  "</div>" +
  "</section>";

/**
 * The markup of version 3.0: class names only, and a container that carries no
 * "data-study-plan" either - which is why the container is looked up as a union
 * of the attribute and the class rather than as a fallback.
 */
const legacyMarkup = (): string =>
  '<div class="academic-study-plan container">' +
  '<nav><ul class="filter"><li>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder">category-label-placeholder</button>' +
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
  "</div>";

/**
 * A module override that marks the module element itself as the dialog trigger,
 * which the manual documents as possible and does not recommend. Neither module
 * names a dialog by id, so the only thing that can pair the two is the module
 * they share.
 */
const moduleAsTriggerMarkup = (): string =>
  '<div class="academic-study-plan" data-study-plan="3">' +
  '<div data-study-plan-semester>' +
  '<div data-study-plan-semester-header>First Semester</div>' +
  '<div data-study-plan-module data-study-plan-dialog-trigger>Mathematics I' +
  '<dialog data-study-plan-dialog id="first"><button>Close</button></dialog>' +
  "</div>" +
  '<div data-study-plan-module data-study-plan-dialog-trigger>Programming Basics' +
  '<dialog data-study-plan-dialog id="second"><button>Close</button></dialog>' +
  "</div>" +
  "</div>" +
  "</div>";

/**
 * One part overridden and the rest upstream: the module is a "<section>" with
 * classes of its own and its dialog is named by id, the filter and the semester
 * around it are exactly what the extension renders. This is the shape an
 * installation reaches for first, and the one a fallback that is not per part
 * would break.
 */
const mixedMarkup = (): string =>
  '<div class="academic-study-plan container" data-study-plan="5" data-filter-label="Filter by category">' +
  '<nav><ul class="filter" data-study-plan-filter><li hidden data-study-plan-filter-template>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<ul class="semesters row">' +
  '<li class="col" data-study-plan-semester>' +
  '<div class="header" aria-hidden="true" inert data-study-plan-semester-header>First Semester</div>' +
  "<ul>" +
  '<section class="course card" data-study-plan-module' +
  " data-categories='[{\"uid\":1,\"label\":\"Mandatory\",\"colour\":\"#cc0000\"}]'>Mathematics I" +
  '<a href="#" class="details" data-study-plan-dialog-trigger data-dialog-id="popup-1"></a>' +
  '<dialog id="popup-1" data-study-plan-dialog><button>Close</button></dialog>' +
  "</section>" +
  "</ul>" +
  "</li>" +
  "</ul>" +
  "</div>";

/** The filter of a site that switched the collapsible filter on. */
const collapsibleMarkup = (): string =>
  '<div class="academic-study-plan" data-study-plan="4" data-filter-label="Filter by category">' +
  '<nav><ul class="filter" data-study-plan-filter data-study-plan-filter-collapsible>' +
  '<li hidden data-study-plan-filter-template>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<div data-study-plan-semester><div data-study-plan-semester-header>First Semester</div>' +
  '<div data-study-plan-module' +
  " data-categories='[{\"uid\":1,\"label\":\"Mandatory\",\"colour\":\"#cc0000\"}]'>Mathematics I</div>" +
  "</div>" +
  "</div>";

/**
 * A category whose backend title carries markup. An editor writes that title,
 * and the script substitutes it into the filter item the template rendered - so
 * the title has to arrive as text and never as markup.
 */
const hostileCategoryMarkup = (): string =>
  '<div class="academic-study-plan container" data-study-plan="6">' +
  '<nav><ul class="filter" data-study-plan-filter><li hidden data-study-plan-filter-template>' +
  '<button data-category-id="category-id-placeholder"' +
  ' data-category-color="category-color-placeholder"' +
  ' aria-label="Filter by category: category-label-placeholder"' +
  ' style="--category-color: category-color-placeholder;">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<div data-study-plan-semester><div data-study-plan-semester-header>First Semester</div>' +
  '<div data-study-plan-module data-categories=\'[{"uid":1,' +
  '"label":"<img src=x onerror=\\"globalThis.__studyPlanInjected = true\\"><b class=\\"injected\\">x</b>",' +
  '"colour":"#cc0000; background: url(evil)"}]\'>Mathematics I</div>' +
  "</div>" +
  "</div>";

/**
 * A module override that is itself the dialog trigger and does what the manual
 * asks of such an override: it makes the element focusable and says what it is.
 * The link inside it is the reason the module may not swallow every key.
 */
const focusableModuleTriggerMarkup = (): string =>
  '<div class="academic-study-plan" data-study-plan="7">' +
  '<div data-study-plan-semester>' +
  '<div data-study-plan-semester-header>First Semester</div>' +
  '<div data-study-plan-module data-study-plan-dialog-trigger role="button" tabindex="0">' +
  'Mathematics I<a href="/module/1" class="deep-link">Permalink</a>' +
  '<dialog data-study-plan-dialog id="only"><button>Close</button></dialog>' +
  "</div>" +
  "</div>" +
  "</div>";

/** A collapsible filter on a plan whose modules carry no category at all. */
const collapsibleWithoutCategoriesMarkup = (): string =>
  '<div class="academic-study-plan" data-study-plan="8" data-filter-label="Filter by category">' +
  '<nav><ul class="filter" data-study-plan-filter data-study-plan-filter-collapsible>' +
  '<li hidden data-study-plan-filter-template>' +
  '<button data-category-id="category-id-placeholder">category-label-placeholder</button>' +
  "</li></ul></nav>" +
  '<div data-study-plan-semester><div data-study-plan-semester-header>First Semester</div>' +
  '<div data-study-plan-module>Mathematics I</div>' +
  "</div>" +
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

const labelsOf = (selector: string): (string | null)[] =>
  Array.from(document.querySelectorAll(selector)).map((button) => button.textContent);

const highlighted = (selector: string): boolean[] =>
  Array.from(document.querySelectorAll(selector)).map((element) =>
    element.classList.contains("highlighted"),
  );

/** Runs the body with a viewport below the mobile breakpoint of the module. */
const withMobileViewport = async (body: () => Promise<void>): Promise<void> => {
  const wide = window.innerWidth;
  Object.defineProperty(window, "innerWidth", { value: 500, configurable: true });
  try {
    await body();
  } finally {
    Object.defineProperty(window, "innerWidth", { value: wide, configurable: true });
  }
};

describe("the study plan inside the content element layout", () => {
  it("filters by category and leaves the layout header alone", async () => {
    await start(layoutMarkup());

    // The filter was rebuilt from the categories the modules carry, which only
    // works when the container was found through the frame wrapper.
    assert.deepEqual(labelsOf(".filter button"), ["Mandatory", "Elective"]);

    // The item Fluid renders is "hidden", so that its placeholder text is not on
    // screen on a page this module never reaches. The clones built from it are
    // real filter buttons, so the attribute must be gone from every one of them -
    // and so must the attribute that says the item is the template.
    assert.deepEqual(
      Array.from(document.querySelectorAll<HTMLElement>(".filter li")).map((item) => [
        item.hasAttribute("hidden"),
        item.hasAttribute("data-study-plan-filter-template"),
      ]),
      [
        [false, false],
        [false, false],
      ],
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
    const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>(".filter button"));
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
    await start(layoutMarkup());

    const dialog = document.querySelector<HTMLDialogElement>("#popup-1");
    assert.ok(dialog !== null, "the dialog is gone");
    assert.equal(dialog.open, false);

    click(document.querySelector(".modal-trigger") as HTMLElement);
    await settle();
    assert.equal(dialog.open, true);
    // As a modal, not as an inline panel: a "show()" would leave the rest of the
    // page operable behind it, and nothing else about the two differs here.
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
    await withMobileViewport(async () => {
      await start(layoutMarkup());

      const header = document.querySelector<HTMLElement>(".header");
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
    });
  });
});

describe("the markup contract", () => {
  it("drives an override that carries the data attributes and none of the classes", async () => {
    await start(attributeMarkup());

    assert.deepEqual(labelsOf(".chips button"), ["Mandatory", "Elective"]);

    const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>(".chips button"));
    click(buttons[0]);
    await settle();

    // The module and the semester it sits in, both found by attribute.
    assert.deepEqual(highlighted(".course"), [true, false]);
    assert.ok(document.querySelector(".term")?.classList.contains("highlighted"));

    const dialog = document.querySelector<HTMLDialogElement>("#course-1");
    assert.ok(dialog !== null, "the dialog is gone");
    click(document.querySelector(".more") as HTMLElement);
    await settle();
    assert.equal(dialog.open, true);

    click(dialog.querySelector("button") as HTMLElement);
    await settle();
    assert.equal(dialog.open, false);
  });

  it("still drives the class based markup of version 3.0", async () => {
    await start(legacyMarkup());

    assert.deepEqual(labelsOf(".filter button"), ["Mandatory", "Elective"]);

    const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>(".filter button"));
    press(buttons[0], "Enter");
    await settle();

    assert.deepEqual(highlighted(".module"), [true, false]);
    assert.ok(document.querySelector(".col")?.classList.contains("highlighted"));

    const dialog = document.querySelector<HTMLDialogElement>("#popup-1");
    assert.ok(dialog !== null, "the dialog is gone");
    click(document.querySelector(".modal-trigger") as HTMLElement);
    await settle();
    assert.equal(dialog.open, true);

    click(dialog.querySelector("button") as HTMLElement);
    await settle();
    assert.equal(dialog.open, false);
  });

  it("drives an override of one part while the others stay upstream", async () => {
    await start(mixedMarkup());

    // The filter is upstream markup and was built from the overridden module.
    assert.deepEqual(labelsOf(".filter button"), ["Mandatory"]);

    const buttons = Array.from(document.querySelectorAll<HTMLButtonElement>(".filter button"));
    click(buttons[0]);
    await settle();

    assert.deepEqual(highlighted(".course"), [true]);
    assert.ok(document.querySelector(".col")?.classList.contains("highlighted"));

    // The trigger of the overridden module is not a button at all, and it opens
    // the dialog the upstream partial would have rendered.
    const dialog = document.querySelector<HTMLDialogElement>("#popup-1");
    assert.ok(dialog !== null, "the dialog is gone");
    click(document.querySelector(".details") as HTMLElement);
    await settle();

    assert.equal(dialog.open, true);
  });

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

  it("leaves a key pressed inside the module to the control it was pressed on", async () => {
    await start(focusableModuleTriggerMarkup());

    const module = document.querySelector<HTMLElement>("[data-study-plan-module]");
    const link = document.querySelector<HTMLElement>(".deep-link");
    const dialog = document.querySelector<HTMLDialogElement>("#only");
    assert.ok(module !== null && link !== null && dialog !== null);

    // Enter on the link follows the link: the module must neither open its
    // dialog nor cancel the event.
    const event = createKeyboardEvent("keydown", { key: "Enter" });
    link.dispatchEvent(event);
    await settle();

    assert.equal(dialog.open, false);
    assert.equal(event.defaultPrevented, false);

    // Enter on the module itself is what opens it, which is why the override
    // has to make the element focusable in the first place.
    press(module, "Enter");
    await settle();

    assert.equal(dialog.open, true);
  });

  it("opens the dialog of the module that was activated, not the first one", async () => {
    await start(moduleAsTriggerMarkup());

    const first = document.querySelector<HTMLDialogElement>("#first");
    const second = document.querySelector<HTMLDialogElement>("#second");
    assert.ok(first !== null && second !== null, "a dialog is gone");

    const modules = Array.from(document.querySelectorAll<HTMLElement>("[data-study-plan-module]"));
    click(modules[1]);
    await settle();

    assert.equal(second.open, true);
    assert.equal(first.open, false);

    // Closing from inside the dialog must not reach the module that is the
    // trigger, or the dialog would reopen in the same gesture.
    click(second.querySelector("button") as HTMLElement);
    await settle();

    assert.equal(second.open, false);
  });
});

describe("the collapsible filter", () => {
  it("hides the filter behind a toggle and expands it by keyboard", async () => {
    await start(collapsibleMarkup());

    const list = document.querySelector<HTMLElement>(".filter");
    const toggle = document.querySelector<HTMLButtonElement>(".filter-toggle");
    assert.ok(list !== null, "the filter list is gone");
    assert.ok(toggle !== null, "no toggle was inserted");

    // The toggle stands in front of the list it controls, names it, and reads
    // its label from the container - the only place the markup carries one.
    assert.equal(toggle.nextElementSibling, list);
    assert.equal(toggle.textContent, "Filter by category");
    assert.notEqual(list.id, "");
    assert.equal(toggle.getAttribute("aria-controls"), list.id);
    assert.equal(toggle.getAttribute("aria-expanded"), "false");
    assert.equal(list.hidden, true);
    // The filter itself was built either way.
    assert.deepEqual(labelsOf(".filter button"), ["Mandatory"]);

    press(toggle, "Enter");
    await settle();

    assert.equal(toggle.getAttribute("aria-expanded"), "true");
    assert.equal(list.hidden, false);

    press(toggle, " ");
    await settle();

    assert.equal(toggle.getAttribute("aria-expanded"), "false");
    assert.equal(list.hidden, true);
  });

  it("inserts no toggle for a filter with no category in it", async () => {
    await start(collapsibleWithoutCategoriesMarkup());

    // The list is empty, so there is nothing a toggle could expand.
    assert.equal(document.querySelectorAll(".filter button").length, 0);
    assert.equal(document.querySelector(".filter-toggle"), null);
    assert.equal(document.querySelector<HTMLElement>(".filter")?.hidden, false);
  });

  it("inserts no toggle for markup that carries no label for it", async () => {
    await start(collapsibleMarkup().replace(' data-filter-label="Filter by category"', ""));

    // An unnamed button is worse than the filter everybody can already see.
    assert.deepEqual(labelsOf(".filter button"), ["Mandatory"]);
    assert.equal(document.querySelector(".filter-toggle"), null);
    assert.equal(document.querySelector<HTMLElement>(".filter")?.hidden, false);
  });

  it("gives the two filters of one page ids of their own", async () => {
    const one = collapsibleMarkup();
    // The same plan twice, with the value that is supposed to tell them apart
    // left as it is - which is what an insert-records page produces.
    await start(one + one);

    const lists = Array.from(document.querySelectorAll<HTMLElement>(".filter"));
    const toggles = Array.from(document.querySelectorAll<HTMLButtonElement>(".filter-toggle"));
    assert.equal(lists.length, 2);
    assert.equal(toggles.length, 2);
    assert.notEqual(lists[0].id, lists[1].id);
    assert.deepEqual(
      toggles.map((toggle) => toggle.getAttribute("aria-controls")),
      lists.map((list) => list.id),
    );

    // And each toggle expands its own filter.
    press(toggles[1], "Enter");
    await settle();

    assert.equal(lists[1].hidden, false);
    assert.equal(lists[0].hidden, true);
  });

  it("inserts no toggle for a filter the site did not switch on", async () => {
    await start(layoutMarkup());

    assert.equal(document.querySelector(".filter-toggle"), null);
    assert.equal(document.querySelector<HTMLElement>(".filter")?.hidden, false);
  });
});
