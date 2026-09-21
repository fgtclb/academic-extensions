/**
 * The interactive study plan: a category filter, per module dialogs, and a
 * mobile layout that collapses each semester into an accordion.
 *
 * One instance per ".academic-study-plan" container, keyed by its
 * "data-study-plan" attribute so a page may carry several.
 */
const MOBILE_BREAKPOINT = 768;
const RESIZE_DEBOUNCE = 150;

interface ModuleCategory {
    uid: string | number;
    label: string;
    colour: string;
}

const isModuleCategory = (value: unknown): value is ModuleCategory =>
    typeof value === 'object' && value !== null && 'uid' in value;

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

class StudyPlan {
    private readonly modules: NodeListOf<HTMLElement>;
    private readonly headers: NodeListOf<HTMLElement>;

    public constructor(private readonly container: HTMLElement) {
        this.modules = this.container.querySelectorAll<HTMLElement>('.module');
        this.headers = this.container.querySelectorAll<HTMLElement>('.header');

        this.buildCategoryFilter();
        this.initCategoryFilter();
        this.initModuleClicks();
        this.initModal();
        this.handleResize();
        this.initHeaderClicks();
    }

    /**
     * Rebuilds the filter from the categories the rendered modules actually
     * carry, using the single list item Fluid rendered as the template. The
     * markup therefore stays in the template rather than in here.
     */
    private buildCategoryFilter(): void {
        const filterList = this.container.querySelector<HTMLElement>('.filter');
        const filterItem = this.container.querySelector<HTMLElement>('.filter li');

        if (filterList === null || filterItem === null) {
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

        this.container.querySelectorAll<HTMLElement>('.module[data-categories]').forEach((module): void => {
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

            filterList.appendChild(item);
        });
    }

    private initCategoryFilter(): void {
        this.container.querySelectorAll<HTMLElement>('.filter button').forEach((button): void => {
            const toggle = (): void => {
                if (button.classList.contains('highlighted')) {
                    this.clearHighlights();
                    return;
                }

                this.highlightCategory(button.dataset.categoryId ?? '', button.dataset.categoryColor ?? '');
                button.classList.add('highlighted');
            };

            button.addEventListener('click', toggle);
            button.addEventListener('keydown', (event: KeyboardEvent): void => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggle();
                }
            });
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
            module.closest('.col')?.classList.add('highlighted', 'open');
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
            const toggle = (): void => {
                if (window.innerWidth > MOBILE_BREAKPOINT) {
                    return;
                }

                const column = header.closest('.col');
                if (column === null) {
                    return;
                }

                column.classList.toggle('open');
                header.setAttribute('aria-expanded', String(column.classList.contains('open')));
            };

            header.addEventListener('click', toggle);
            header.addEventListener('keydown', (event: KeyboardEvent): void => {
                if (event.key === 'Enter' || event.key === ' ') {
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

    private initModuleClicks(): void {
        this.container.querySelectorAll<HTMLElement>('.modal-trigger').forEach((trigger): void => {
            const open = (): void => {
                const dialog = document.getElementById(trigger.dataset.dialogId ?? '');
                if (dialog instanceof HTMLDialogElement) {
                    dialog.showModal();
                }
            };

            trigger.addEventListener('click', open);
            trigger.addEventListener('keydown', (event: KeyboardEvent): void => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open();
                }
            });
        });
    }

    private initModal(): void {
        document.querySelectorAll<HTMLDialogElement>('.module dialog').forEach((dialog): void => {
            const button = dialog.querySelector('button');
            if (button === null) {
                return;
            }

            const close = (event: Event): void => {
                this.closeModal(dialog);
                event.stopPropagation();
            };

            button.addEventListener('click', close);
            button.addEventListener('keydown', (event: KeyboardEvent): void => {
                if (event.key === 'Enter' || event.key === ' ') {
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

const instances = new Map<string, StudyPlan>();

const init = (): void => {
    document.querySelectorAll<HTMLElement>('.academic-study-plan').forEach((container): void => {
        const identifier = container.dataset.studyPlan ?? '';
        if (!instances.has(identifier)) {
            instances.set(identifier, new StudyPlan(container));
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

export {};
