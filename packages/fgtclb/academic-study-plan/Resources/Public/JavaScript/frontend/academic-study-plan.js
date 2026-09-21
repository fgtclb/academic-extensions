/* Generated from Resources/Private/TypeScript — do not edit. */
const MOBILE_BREAKPOINT = 768;
const RESIZE_DEBOUNCE = 150;
let filterSequence = 0;
const CONTAINER = "data-study-plan";
const FILTER = "data-study-plan-filter";
const FILTER_COLLAPSIBLE = "data-study-plan-filter-collapsible";
const FILTER_TEMPLATE = "data-study-plan-filter-template";
const SEMESTER = "data-study-plan-semester";
const SEMESTER_HEADER = "data-study-plan-semester-header";
const MODULE = "data-study-plan-module";
const DIALOG_TRIGGER = "data-study-plan-dialog-trigger";
const DIALOG = "data-study-plan-dialog";
const LEGACY_CONTAINER = ".academic-study-plan";
const LEGACY_FILTER = ".filter";
const LEGACY_FILTER_TEMPLATE = "li";
const LEGACY_SEMESTER = ".col";
const LEGACY_SEMESTER_HEADER = ".header";
const LEGACY_MODULE = ".module";
const LEGACY_DIALOG_TRIGGER = ".modal-trigger";
const LEGACY_DIALOG = "dialog";
const isModuleCategory = (value) => typeof value === "object" && value !== null && "uid" in value;
const findAll = (root, attribute, legacy) => {
  const byAttribute = Array.from(root.querySelectorAll(`[${attribute}]`));
  return byAttribute.length > 0 ? byAttribute : Array.from(root.querySelectorAll(legacy));
};
const findOne = (root, attribute, legacy) => root.querySelector(`[${attribute}]`) ?? root.querySelector(legacy);
const closestOf = (element, attribute, legacy) => element.closest(`[${attribute}]`) ?? element.closest(legacy);
const categoriesOf = (module) => {
  const raw = module.dataset.categories;
  if (raw === void 0 || raw === "" || raw === "[]") {
    return [];
  }
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed.filter(isModuleCategory) : [];
  } catch {
    return [];
  }
};
const colourOf = (value) => /^(#[0-9a-fA-F]{3,8}|[a-zA-Z]{1,32}|rgba?\([0-9.,%\s]+\))$/.test(value) ? value : "";
const substitutePlaceholders = (node, apply) => {
  if (node instanceof Element) {
    Array.from(node.attributes).forEach((attribute) => {
      const replaced = apply(attribute.value);
      if (replaced !== attribute.value) {
        node.setAttribute(attribute.name, replaced);
      }
    });
  }
  if (node.nodeType === Node.TEXT_NODE && node.nodeValue !== null) {
    node.nodeValue = apply(node.nodeValue);
  }
  Array.from(node.childNodes).forEach((child) => substitutePlaceholders(child, apply));
};
const hexToRgba = (hex, alpha) => {
  if (hex === "" || hex.length < 7) {
    return `rgba(0, 0, 0, ${alpha})`;
  }
  const red = parseInt(hex.slice(1, 3), 16);
  const green = parseInt(hex.slice(3, 5), 16);
  const blue = parseInt(hex.slice(5, 7), 16);
  return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};
const onActivate = (element, handler) => {
  element.addEventListener("click", handler);
  element.addEventListener("keydown", (event) => {
    if (event.key !== "Enter" && event.key !== " ") {
      return;
    }
    if (event.target !== element) {
      return;
    }
    event.preventDefault();
    handler(event);
  });
};
class StudyPlan {
  container;
  modules;
  headers;
  filterList;
  // Declared and assigned rather than written as a constructor parameter
  // property: node strips types, it does not transform them, so a parameter
  // property cannot be loaded by the "testJs" suite at all.
  constructor(container) {
    this.container = container;
    this.modules = findAll(this.container, MODULE, LEGACY_MODULE);
    this.headers = findAll(this.container, SEMESTER_HEADER, LEGACY_SEMESTER_HEADER);
    this.filterList = findOne(this.container, FILTER, LEGACY_FILTER);
    this.buildCategoryFilter();
    this.initCategoryFilter();
    this.initCollapsibleFilter();
    this.initModuleDialogs();
    this.handleResize();
    this.initHeaderClicks();
  }
  /**
   * Rebuilds the filter from the categories the rendered modules actually
   * carry, using the single list item Fluid rendered as the template. The
   * markup therefore stays in the template rather than in here.
   */
  buildCategoryFilter() {
    const filterList = this.filterList;
    if (filterList === null) {
      return;
    }
    const filterItem = findOne(filterList, FILTER_TEMPLATE, LEGACY_FILTER_TEMPLATE);
    if (filterItem === null) {
      return;
    }
    const filterTemplate = filterItem.cloneNode(true);
    filterList.innerHTML = "";
    const categories = /* @__PURE__ */ new Map();
    this.modules.forEach((module) => {
      categoriesOf(module).forEach((category) => {
        const uid = String(category.uid);
        if (uid !== "" && uid !== "0" && !categories.has(uid)) {
          categories.set(uid, category);
        }
      });
    });
    categories.forEach((category) => {
      const item = filterTemplate.cloneNode(true);
      if (!(item instanceof HTMLElement)) {
        return;
      }
      substitutePlaceholders(item, (value) => value.replace(/category-id-placeholder/g, String(category.uid)).replace(/category-color-placeholder/g, colourOf(String(category.colour))).replace(/category-label-placeholder/g, String(category.label)));
      item.removeAttribute("hidden");
      item.removeAttribute(FILTER_TEMPLATE);
      filterList.appendChild(item);
    });
  }
  initCategoryFilter() {
    var _a;
    (_a = this.filterList) == null ? void 0 : _a.querySelectorAll("button").forEach((button) => {
      onActivate(button, () => {
        if (button.classList.contains("highlighted")) {
          this.clearHighlights();
          return;
        }
        this.highlightCategory(button.dataset.categoryId ?? "", button.dataset.categoryColor ?? "");
        button.classList.add("highlighted");
      });
    });
  }
  /**
   * Puts the filter behind a toggle button when the site asked for it.
   *
   * The toggle is built here rather than rendered by Fluid because the filter
   * itself is: a plan whose modules carry no category at all ends up with an
   * empty list, and a control that expands nothing would be worse than none.
   */
  initCollapsibleFilter() {
    const filterList = this.filterList;
    if (filterList === null || !filterList.hasAttribute(FILTER_COLLAPSIBLE)) {
      return;
    }
    const parent = filterList.parentElement;
    if (parent === null || filterList.querySelector("button") === null) {
      return;
    }
    const label = this.container.dataset.filterLabel ?? "";
    if (label === "") {
      return;
    }
    if (filterList.id === "") {
      filterSequence += 1;
      filterList.id = `study-plan-filter-${filterSequence}`;
    }
    const toggle = document.createElement("button");
    toggle.type = "button";
    toggle.className = "filter-toggle";
    toggle.textContent = label;
    toggle.setAttribute("aria-controls", filterList.id);
    toggle.setAttribute("aria-expanded", "false");
    filterList.hidden = true;
    parent.insertBefore(toggle, filterList);
    onActivate(toggle, () => {
      const expanded = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!expanded));
      filterList.hidden = expanded;
    });
  }
  highlightCategory(categoryId, colour) {
    this.clearHighlights();
    this.container.classList.add("highlighted");
    this.modules.forEach((module) => {
      var _a;
      const uids = categoriesOf(module).map((category) => String(category.uid));
      if (!uids.includes(categoryId)) {
        return;
      }
      module.classList.add("highlighted");
      (_a = closestOf(module, SEMESTER, LEGACY_SEMESTER)) == null ? void 0 : _a.classList.add("highlighted", "open");
      this.container.style.setProperty("--highlight-color", hexToRgba(colour, 0.25));
    });
  }
  clearHighlights() {
    this.container.querySelectorAll(".highlighted").forEach((highlighted) => {
      highlighted.classList.remove("highlighted", "open");
    });
    this.container.classList.remove("highlighted");
  }
  initHeaderClicks() {
    this.headers.forEach((header) => {
      onActivate(header, () => {
        if (window.innerWidth > MOBILE_BREAKPOINT) {
          return;
        }
        const column = closestOf(header, SEMESTER, LEGACY_SEMESTER);
        if (column === null) {
          return;
        }
        column.classList.toggle("open");
        header.setAttribute("aria-expanded", String(column.classList.contains("open")));
      });
    });
  }
  /**
   * On a narrow viewport every semester is an accordion: the header is a
   * button, and neither header nor module carries a fixed height.
   */
  enableMobile() {
    this.headers.forEach((header) => {
      header.style.height = "";
      header.setAttribute("aria-expanded", "false");
      header.setAttribute("tabindex", "0");
      header.setAttribute("role", "button");
      header.setAttribute("aria-hidden", "false");
      header.removeAttribute("inert");
    });
    this.modules.forEach((module) => {
      module.style.height = "";
    });
  }
  /**
   * On a wide viewport the semesters are columns side by side, so headers and
   * modules are levelled to the tallest of each and the header stops being
   * interactive.
   */
  disableMobile() {
    let headerHeight = 0;
    let moduleHeight = 0;
    this.headers.forEach((header) => {
      headerHeight = Math.max(headerHeight, header.offsetHeight);
    });
    this.headers.forEach((header) => {
      header.style.height = `${headerHeight}px`;
      header.setAttribute("tabindex", "-1");
      header.setAttribute("role", "");
      header.setAttribute("aria-hidden", "true");
      header.setAttribute("inert", "");
    });
    this.modules.forEach((module) => {
      moduleHeight = Math.max(moduleHeight, module.offsetHeight);
    });
    this.modules.forEach((module) => {
      module.style.height = `${moduleHeight}px`;
    });
  }
  /**
   * Wires every module with its own dialog: the triggers that open it and the
   * close button inside it.
   *
   * Both are resolved per module rather than per container, which is what lets
   * an override mark the module element itself as the trigger — the pairing is
   * then still with the dialog of that very module and not with the first one
   * on the page.
   */
  initModuleDialogs() {
    const wired = /* @__PURE__ */ new Set();
    this.modules.forEach((module) => {
      const dialogs = findAll(module, DIALOG, LEGACY_DIALOG).filter((element) => element instanceof HTMLDialogElement);
      this.triggersOf(module).forEach((trigger) => {
        const referenced = document.getElementById(trigger.dataset.dialogId ?? "");
        const dialog = referenced instanceof HTMLDialogElement ? referenced : dialogs[0];
        if (dialog === void 0) {
          return;
        }
        if (!dialogs.includes(dialog)) {
          dialogs.push(dialog);
        }
        onActivate(trigger, () => {
          if (!dialog.open) {
            dialog.showModal();
          }
        });
      });
      dialogs.forEach((dialog) => {
        if (!wired.has(dialog)) {
          wired.add(dialog);
          this.initDialogClose(dialog);
        }
      });
    });
  }
  /**
   * The triggers of one module: the module element itself when it carries the
   * attribute, and every trigger inside it.
   */
  triggersOf(module) {
    if (module.hasAttribute(DIALOG_TRIGGER)) {
      return [module, ...module.querySelectorAll(`[${DIALOG_TRIGGER}]`)];
    }
    return findAll(module, DIALOG_TRIGGER, LEGACY_DIALOG_TRIGGER);
  }
  initDialogClose(dialog) {
    const button = dialog.querySelector("button");
    if (button === null) {
      return;
    }
    onActivate(button, (event) => {
      this.closeModal(dialog);
      event.stopPropagation();
    });
  }
  /**
   * Closing the dialog stops whatever it was playing. A paused audio element
   * that keeps its position would resume mid sentence the next time the
   * dialog is opened.
   */
  closeModal(dialog) {
    dialog.querySelectorAll("audio").forEach((audio) => {
      audio.pause();
      audio.currentTime = 0;
    });
    dialog.close();
  }
  handleResize() {
    if (window.innerWidth > MOBILE_BREAKPOINT) {
      this.disableMobile();
    } else {
      this.enableMobile();
    }
  }
}
const instances = /* @__PURE__ */ new Map();
const init = () => {
  document.querySelectorAll(`[${CONTAINER}], ${LEGACY_CONTAINER}`).forEach((container) => {
    if (!instances.has(container)) {
      instances.set(container, new StudyPlan(container));
    }
  });
};
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
let resizeTimeout;
window.addEventListener("resize", () => {
  window.clearTimeout(resizeTimeout);
  resizeTimeout = window.setTimeout(() => {
    instances.forEach((instance) => instance.handleResize());
  }, RESIZE_DEBOUNCE);
});
export {
  init
};
