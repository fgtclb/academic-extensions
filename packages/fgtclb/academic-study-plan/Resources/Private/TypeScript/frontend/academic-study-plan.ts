/**
 * The interactive study plan: a category filter, per module dialogs, and a
 * mobile layout that collapses each semester into an accordion.
 *
 * One instance per container, keyed by the element itself so a page may carry
 * several.
 *
 * ## How the parts are found
 *
 * Every part this module drives is found by a "data-study-plan-*" attribute,
 * never by a class name. The attributes are the contract with the templates:
 * an installation may replace any partial, with its own classes and its own
 * nesting, and the interaction keeps working as long as the attributes are
 * where the manual says they are.
 *
 * The class selectors of version 3.0 are still read, per part, when the
 * attribute finds nothing at all — so an override written for 3.0 keeps
 * working, and an override that carries the attribute on some parts and not on
 * others works too. That fallback is deprecated and is removed in 4.0. It
 * reports nothing: a console message would reach visitors, not integrators.
 *
 * Two class names stay class names on purpose: "highlighted" and "open" are
 * state this module writes and the stylesheet reacts to, not parts it looks up.
 */
const MOBILE_BREAKPOINT = 768;
const RESIZE_DEBOUNCE = 150;

/** Counts the filter lists that needed an id of their own, per document. */
let filterSequence = 0;

const CONTAINER = 'data-study-plan';
const FILTER = 'data-study-plan-filter';
const FILTER_COLLAPSIBLE = 'data-study-plan-filter-collapsible';
const FILTER_TEMPLATE = 'data-study-plan-filter-template';
const SEMESTER = 'data-study-plan-semester';
const SEMESTER_HEADER = 'data-study-plan-semester-header';
const MODULE = 'data-study-plan-module';
const DIALOG_TRIGGER = 'data-study-plan-dialog-trigger';
const DIALOG = 'data-study-plan-dialog';

/**
 * The class selectors version 3.0 identified the same parts by. Deprecated,
 * read only when the attribute of that part finds nothing, removed in 4.0.
 */
const LEGACY_CONTAINER = '.academic-study-plan';
const LEGACY_FILTER = '.filter';
const LEGACY_FILTER_TEMPLATE = 'li';
const LEGACY_SEMESTER = '.col';
const LEGACY_SEMESTER_HEADER = '.header';
const LEGACY_MODULE = '.module';
const LEGACY_DIALOG_TRIGGER = '.modal-trigger';
const LEGACY_DIALOG = 'dialog';

interface ModuleCategory {
    uid: string | number;
    label: string;
    colour: string;
}

const isModuleCategory = (value: unknown): value is ModuleCategory =>
    typeof value === 'object' && value !== null && 'uid' in value;

/**
 * Every element of one part, attribute first. The class selector is only read
 * when the attribute is nowhere below this root, which keeps a 3.0 override of
 * one part working next to an upstream one of another.
 */
const findAll = (root: ParentNode, attribute: string, legacy: string): HTMLElement[] => {
    const byAttribute = Array.from(root.querySelectorAll<HTMLElement>(`[${attribute}]`));

    return byAttribute.length > 0 ? byAttribute : Array.from(root.querySelectorAll<HTMLElement>(legacy));
};

const findOne = (root: ParentNode, attribute: string, legacy: string): HTMLElement | null =>
    root.querySelector<HTMLElement>(`[${attribute}]`) ?? root.querySelector<HTMLElement>(legacy);

/**
 * The ancestor of an element that is one part, attribute first — the semester
 * column a module or a header sits in.
 */
const closestOf = (element: Element, attribute: string, legacy: string): HTMLElement | null =>
    element.closest<HTMLElement>(`[${attribute}]`) ?? element.closest<HTMLElement>(legacy);

/**
 * The categories a module carries, read from its "data-categories" attribute.
 *
 * The attribute is written by Fluid and can legitimately be absent or the empty
 * list. Anything else that does not parse is a defect in the record, and it must
 * not take the rest of the plan down with it — hence the swallowed error.
 */
const categoriesOf = (module: HTMLElement): ModuleCategory[] => {
    const raw = module.dataset.categories;
    if (raw === undefined || raw === '' || raw === '[]') {
        return [];
    }

    try {
        const parsed: unknown = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.filter(isModuleCategory) : [];
    } catch {
        // A malformed attribute leaves this module uncategorised, nothing more.
        return [];
    }
};

/**
 * The colour of a category, as it may be written into a "style" attribute.
 *
 * The value comes from a backend record and lands in
 * "style=\"--category-color: …\"", where a ";" would start a declaration of
 * whoever titled the record. Only the shapes a colour field can legitimately
 * produce are let through; anything else leaves the category without a colour,
 * which is what an empty value already meant.
 */
const colourOf = (value: string): string =>
    /^(#[0-9a-fA-F]{3,8}|[a-zA-Z]{1,32}|rgba?\([0-9.,%\s]+\))$/.test(value) ? value : '';

/**
 * Replaces the placeholders of the rendered filter item in the clone that is
 * about to become a real filter button.
 *
 * Walks attribute values and text nodes rather than the markup as a string: a
 * category title is written by an editor, and substituting it into HTML that is
 * then parsed makes the title's angle brackets markup. Setting an attribute or
 * a text node cannot do that, whatever the title contains.
 */
const substitutePlaceholders = (node: Node, apply: (value: string) => string): void => {
    if (node instanceof Element) {
        Array.from(node.attributes).forEach((attribute): void => {
            const replaced = apply(attribute.value);
            if (replaced !== attribute.value) {
                node.setAttribute(attribute.name, replaced);
            }
        });
    }

    if (node.nodeType === Node.TEXT_NODE && node.nodeValue !== null) {
        node.nodeValue = apply(node.nodeValue);
    }

    Array.from(node.childNodes).forEach((child): void => substitutePlaceholders(child, apply));
};

const hexToRgba = (hex: string, alpha: number): string => {
    if (hex === '' || hex.length < 7) {
        return `rgba(0, 0, 0, ${alpha})`;
    }

    const red = parseInt(hex.slice(1, 3), 16);
    const green = parseInt(hex.slice(3, 5), 16);
    const blue = parseInt(hex.slice(5, 7), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

/**
 * Runs the handler on a click and on the two keys that activate a control.
 *
 * The default of the keydown is prevented, which is what keeps a real button
 * from running the handler twice: the browser derives its click from the
 * uncancelled keydown.
 */
const onActivate = (element: HTMLElement, handler: (event: Event) => void): void => {
    element.addEventListener('click', handler);
    element.addEventListener('keydown', (event: KeyboardEvent): void => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        // A key pressed on something inside this element belongs to that
        // something: an override may put the trigger attribute on the whole
        // module, and Enter on a link or an audio control inside it has to do
        // what that control does rather than open the dialog.
        if (event.target !== element) {
            return;
        }

        event.preventDefault();
        handler(event);
    });
};

class StudyPlan {
    private readonly container: HTMLElement;
    private readonly modules: HTMLElement[];
    private readonly headers: HTMLElement[];
    private readonly filterList: HTMLElement | null;

    // Declared and assigned rather than written as a constructor parameter
    // property: node strips types, it does not transform them, so a parameter
    // property cannot be loaded by the "testJs" suite at all.
    public constructor(container: HTMLElement) {
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
    private buildCategoryFilter(): void {
        const filterList = this.filterList;
        if (filterList === null) {
            return;
        }

        const filterItem = findOne(filterList, FILTER_TEMPLATE, LEGACY_FILTER_TEMPLATE);
        if (filterItem === null) {
            return;
        }

        // Taken before the list is emptied below, and cloned once more per
        // category: the item is the only markup this filter has.
        const filterTemplate = filterItem.cloneNode(true);

        // Emptied before the categories are collected, and deliberately so: the
        // one list item Fluid rendered is a template carrying placeholder text,
        // and a plan whose modules have no categories at all must end up with no
        // filter rather than with that placeholder on screen.
        filterList.innerHTML = '';

        const categories = new Map<string, ModuleCategory>();

        this.modules.forEach((module): void => {
            categoriesOf(module).forEach((category): void => {
                const uid = String(category.uid);
                if (uid !== '' && uid !== '0' && !categories.has(uid)) {
                    categories.set(uid, category);
                }
            });
        });

        categories.forEach((category): void => {
            const item = filterTemplate.cloneNode(true);
            if (!(item instanceof HTMLElement)) {
                return;
            }

            substitutePlaceholders(item, (value): string =>
                value
                    .replace(/category-id-placeholder/g, String(category.uid))
                    .replace(/category-color-placeholder/g, colourOf(String(category.colour)))
                    .replace(/category-label-placeholder/g, String(category.label)));

            // The item in the template carries "hidden" so that its placeholder
            // text is not on screen on a page this module never reaches. A clone
            // of it is a real filter button and has to be visible — and it is no
            // longer the template, so it stops saying that it is.
            item.removeAttribute('hidden');
            item.removeAttribute(FILTER_TEMPLATE);
            filterList.appendChild(item);
        });
    }

    private initCategoryFilter(): void {
        this.filterList?.querySelectorAll<HTMLElement>('button').forEach((button): void => {
            onActivate(button, (): void => {
                if (button.classList.contains('highlighted')) {
                    this.clearHighlights();
                    return;
                }

                this.highlightCategory(button.dataset.categoryId ?? '', button.dataset.categoryColor ?? '');
                button.classList.add('highlighted');
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
    private initCollapsibleFilter(): void {
        const filterList = this.filterList;
        if (filterList === null || !filterList.hasAttribute(FILTER_COLLAPSIBLE)) {
            return;
        }

        const parent = filterList.parentElement;
        if (parent === null || filterList.querySelector('button') === null) {
            return;
        }

        // The label of the container is the only text the markup carries for
        // this control. Without it the toggle would be an unnamed button, which
        // is worse than the filter everybody can already see, so the plan keeps
        // its expanded filter instead - the manual says so with the attribute.
        const label = this.container.dataset.filterLabel ?? '';
        if (label === '') {
            return;
        }

        if (filterList.id === '') {
            // Per document, not per plan: two plans of one page can carry the
            // same "data-study-plan" value, or none at all, and two lists with
            // one id would make both toggles point at the first of them.
            filterSequence += 1;
            filterList.id = `study-plan-filter-${filterSequence}`;
        }

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'filter-toggle';
        toggle.textContent = label;
        toggle.setAttribute('aria-controls', filterList.id);
        toggle.setAttribute('aria-expanded', 'false');
        filterList.hidden = true;
        parent.insertBefore(toggle, filterList);

        onActivate(toggle, (): void => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            filterList.hidden = expanded;
        });
    }

    private highlightCategory(categoryId: string, colour: string): void {
        this.clearHighlights();
        this.container.classList.add('highlighted');

        this.modules.forEach((module): void => {
            const uids = categoriesOf(module).map((category): string => String(category.uid));
            if (!uids.includes(categoryId)) {
                return;
            }

            module.classList.add('highlighted');
            closestOf(module, SEMESTER, LEGACY_SEMESTER)?.classList.add('highlighted', 'open');
            this.container.style.setProperty('--highlight-color', hexToRgba(colour, 0.25));
        });
    }

    private clearHighlights(): void {
        this.container.querySelectorAll('.highlighted').forEach((highlighted): void => {
            highlighted.classList.remove('highlighted', 'open');
        });
        this.container.classList.remove('highlighted');
    }

    private initHeaderClicks(): void {
        this.headers.forEach((header): void => {
            onActivate(header, (): void => {
                if (window.innerWidth > MOBILE_BREAKPOINT) {
                    return;
                }

                const column = closestOf(header, SEMESTER, LEGACY_SEMESTER);
                if (column === null) {
                    return;
                }

                column.classList.toggle('open');
                header.setAttribute('aria-expanded', String(column.classList.contains('open')));
            });
        });
    }

    /**
     * On a narrow viewport every semester is an accordion: the header is a
     * button, and neither header nor module carries a fixed height.
     */
    private enableMobile(): void {
        this.headers.forEach((header): void => {
            header.style.height = '';
            header.setAttribute('aria-expanded', 'false');
            header.setAttribute('tabindex', '0');
            header.setAttribute('role', 'button');
            header.setAttribute('aria-hidden', 'false');
            header.removeAttribute('inert');
        });

        this.modules.forEach((module): void => {
            module.style.height = '';
        });
    }

    /**
     * On a wide viewport the semesters are columns side by side, so headers and
     * modules are levelled to the tallest of each and the header stops being
     * interactive.
     */
    private disableMobile(): void {
        let headerHeight = 0;
        let moduleHeight = 0;

        this.headers.forEach((header): void => {
            headerHeight = Math.max(headerHeight, header.offsetHeight);
        });

        this.headers.forEach((header): void => {
            header.style.height = `${headerHeight}px`;
            header.setAttribute('tabindex', '-1');
            header.setAttribute('role', '');
            header.setAttribute('aria-hidden', 'true');
            header.setAttribute('inert', '');
        });

        this.modules.forEach((module): void => {
            moduleHeight = Math.max(moduleHeight, module.offsetHeight);
        });

        this.modules.forEach((module): void => {
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
    private initModuleDialogs(): void {
        // A dialog another module's trigger already reached must not have its close
        // button wired a second time, or one activation would close it twice.
        const wired = new Set<HTMLDialogElement>();

        this.modules.forEach((module): void => {
            const dialogs = findAll(module, DIALOG, LEGACY_DIALOG)
                .filter((element): element is HTMLDialogElement => element instanceof HTMLDialogElement);

            this.triggersOf(module).forEach((trigger): void => {
                const referenced = document.getElementById(trigger.dataset.dialogId ?? '');
                const dialog = referenced instanceof HTMLDialogElement ? referenced : dialogs[0];

                if (dialog === undefined) {
                    return;
                }

                if (!dialogs.includes(dialog)) {
                    dialogs.push(dialog);
                }

                onActivate(trigger, (): void => {
                    // A trigger that is the module element itself sees the clicks of
                    // everything inside it, the open dialog included.
                    if (!dialog.open) {
                        dialog.showModal();
                    }
                });
            });

            dialogs.forEach((dialog): void => {
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
    private triggersOf(module: HTMLElement): HTMLElement[] {
        // The module element counts as one of them, and when it carries the
        // attribute the class fallback is off for this part: the attribute is
        // there, on the root, so a leftover ".modal-trigger" inside must not be
        // wired as a second trigger.
        if (module.hasAttribute(DIALOG_TRIGGER)) {
            return [module, ...module.querySelectorAll<HTMLElement>(`[${DIALOG_TRIGGER}]`)];
        }

        return findAll(module, DIALOG_TRIGGER, LEGACY_DIALOG_TRIGGER);
    }

    private initDialogClose(dialog: HTMLDialogElement): void {
        const button = dialog.querySelector('button');
        if (button === null) {
            return;
        }

        onActivate(button, (event: Event): void => {
            this.closeModal(dialog);
            event.stopPropagation();
        });
    }

    /**
     * Closing the dialog stops whatever it was playing. A paused audio element
     * that keeps its position would resume mid sentence the next time the
     * dialog is opened.
     */
    private closeModal(dialog: HTMLDialogElement): void {
        dialog.querySelectorAll('audio').forEach((audio): void => {
            audio.pause();
            audio.currentTime = 0;
        });

        dialog.close();
    }

    public handleResize(): void {
        if (window.innerWidth > MOBILE_BREAKPOINT) {
            this.disableMobile();
        } else {
            this.enableMobile();
        }
    }
}

/**
 * The running plans, keyed by their container so that a second start finds them
 * again. Nothing prunes it: a container is removed from a page by a script that
 * this module knows nothing about, and the resize handler below has to reach
 * every plan that is still on screen.
 */
const instances = new Map<HTMLElement, StudyPlan>();

/**
 * Starts one instance per study plan of the document, skipping the ones that
 * are already running.
 *
 * Exported so that the "testJs" suite can drive a fixture with it; a browser
 * reaches it through the two statements below.
 */
const init = (): void => {
    // The one lookup that is a union rather than a fallback: two plans of one
    // page can be delivered by different templates, and a legacy one must not
    // disappear because another one carries the attribute.
    document
        .querySelectorAll<HTMLElement>(`[${CONTAINER}], ${LEGACY_CONTAINER}`)
        .forEach((container): void => {
            if (!instances.has(container)) {
                instances.set(container, new StudyPlan(container));
            }
        });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

let resizeTimeout: number | undefined;
window.addEventListener('resize', (): void => {
    window.clearTimeout(resizeTimeout);
    resizeTimeout = window.setTimeout((): void => {
        instances.forEach((instance): void => instance.handleResize());
    }, RESIZE_DEBOUNCE);
});

export { init };
