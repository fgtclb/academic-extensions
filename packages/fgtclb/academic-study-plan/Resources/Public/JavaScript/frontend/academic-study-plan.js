/* Generated from Resources/Private/TypeScript — do not edit. */
"use strict";
(() => {
  // packages/fgtclb/academic-study-plan/Resources/Private/TypeScript/frontend/academic-study-plan.ts
  var MOBILE_BREAKPOINT = 768;
  var RESIZE_DEBOUNCE = 150;
  var isModuleCategory = (value) => typeof value === "object" && value !== null && "uid" in value;
  var categoriesOf = (module) => {
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
  var hexToRgba = (hex, alpha) => {
    if (hex === "" || hex.length < 7) {
      return `rgba(0, 0, 0, ${alpha})`;
    }
    const red = parseInt(hex.slice(1, 3), 16);
    const green = parseInt(hex.slice(3, 5), 16);
    const blue = parseInt(hex.slice(5, 7), 16);
    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
  };
  var StudyPlan = class {
    constructor(container) {
      this.container = container;
      this.modules = this.container.querySelectorAll(".module");
      this.headers = this.container.querySelectorAll(".header");
      this.buildCategoryFilter();
      this.initCategoryFilter();
      this.initModuleClicks();
      this.initModal();
      this.handleResize();
      this.initHeaderClicks();
    }
    modules;
    headers;
    /**
     * Rebuilds the filter from the categories the rendered modules actually
     * carry, using the single list item Fluid rendered as the template. The
     * markup therefore stays in the template rather than in here.
     */
    buildCategoryFilter() {
      var _a;
      const filterList = this.container.querySelector(".filter");
      const filterTemplate = (_a = this.container.querySelector(".filter li")) == null ? void 0 : _a.outerHTML;
      if (filterList === null || filterTemplate === void 0) {
        return;
      }
      filterList.innerHTML = "";
      const categories = /* @__PURE__ */ new Map();
      this.container.querySelectorAll(".module[data-categories]").forEach((module) => {
        categoriesOf(module).forEach((category) => {
          const uid = String(category.uid);
          if (uid !== "" && uid !== "0" && !categories.has(uid)) {
            categories.set(uid, category);
          }
        });
      });
      categories.forEach((category) => {
        const markup = filterTemplate.replace(/category-id-placeholder/g, `${category.uid}`).replace(/category-color-placeholder/g, `${category.colour}`).replace(/category-label-placeholder/g, `${category.label}`);
        const holder = document.createElement("li");
        holder.innerHTML = markup;
        if (holder.firstElementChild !== null) {
          filterList.appendChild(holder.firstElementChild);
        }
      });
    }
    initCategoryFilter() {
      this.container.querySelectorAll(".filter button").forEach((button) => {
        const toggle = () => {
          if (button.classList.contains("highlighted")) {
            this.clearHighlights();
            return;
          }
          this.highlightCategory(button.dataset.categoryId ?? "", button.dataset.categoryColor ?? "");
          button.classList.add("highlighted");
        };
        button.addEventListener("click", toggle);
        button.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            toggle();
          }
        });
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
        (_a = module.closest(".col")) == null ? void 0 : _a.classList.add("highlighted", "open");
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
        const toggle = () => {
          if (window.innerWidth > MOBILE_BREAKPOINT) {
            return;
          }
          const column = header.closest(".col");
          if (column === null) {
            return;
          }
          column.classList.toggle("open");
          header.setAttribute("aria-expanded", String(column.classList.contains("open")));
        };
        header.addEventListener("click", toggle);
        header.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            toggle();
          }
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
    initModuleClicks() {
      this.container.querySelectorAll(".modal-trigger").forEach((trigger) => {
        const open = () => {
          const dialog = document.getElementById(trigger.dataset.dialogId ?? "");
          if (dialog instanceof HTMLDialogElement) {
            dialog.showModal();
          }
        };
        trigger.addEventListener("click", open);
        trigger.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            open();
          }
        });
      });
    }
    initModal() {
      document.querySelectorAll(".module dialog").forEach((dialog) => {
        const button = dialog.querySelector("button");
        if (button === null) {
          return;
        }
        const close = (event) => {
          this.closeModal(dialog);
          event.stopPropagation();
        };
        button.addEventListener("click", close);
        button.addEventListener("keydown", (event) => {
          if (event.key === "Enter" || event.key === " ") {
            event.preventDefault();
            close(event);
          }
        });
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
  };
  var instances = /* @__PURE__ */ new Map();
  var init = () => {
    document.querySelectorAll(".academic-study-plan").forEach((container) => {
      const identifier = container.dataset.studyPlan ?? "";
      if (!instances.has(identifier)) {
        instances.set(identifier, new StudyPlan(container));
      }
    });
  };
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
  var resizeTimeout;
  window.addEventListener("resize", () => {
    window.clearTimeout(resizeTimeout);
    resizeTimeout = window.setTimeout(() => {
      instances.forEach((instance) => instance.handleResize());
    }, RESIZE_DEBOUNCE);
  });
})();
