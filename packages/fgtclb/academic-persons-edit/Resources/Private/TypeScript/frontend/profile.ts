import {
  createApp,
  onMounted,
  type App,
} from "@fgtclb/academic-persons-edit/frontend/vue.js";
import {
  initializePopover,
  rootSelector,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  readEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import {
  createDocumentEditing,
  initializeDocumentSections,
} from "@fgtclb/academic-persons-edit/frontend/profile/documents.js";
import { initializeFieldEditing } from "@fgtclb/academic-persons-edit/frontend/profile/fields.js";
import { createImageEditing } from "@fgtclb/academic-persons-edit/frontend/profile/image.js";
import { initializeStickyImageOffset } from "@fgtclb/academic-persons-edit/frontend/profile/sticky-image.js";
import { createSkipSync } from "@fgtclb/academic-persons-edit/frontend/profile/sync.js";

const mountedRoots = new WeakSet<HTMLElement>();

// The root carries the whole configuration of an editor as "data-*"
// attributes. It is read here, once, and handed to every controller: no module
// below this point looks at "root.dataset" again.
const createProfileEditingApp = (context: EditingContext): App =>
  createApp({
    setup(): Record<string, unknown> {
      const documentController = createDocumentEditing(context);
      const imageController = createImageEditing(context);
      const syncController = createSkipSync(context);

      onMounted((): void => {
        initializeStickyImageOffset(context.root);
        initializeFieldEditing(context);
        initializeDocumentSections(context);
        initializePopover(context.root);
      });

      return {
        ...documentController,
        ...imageController,
        ...syncController,
      };
    },
  });

export const initializeProfileEditors = (scope: ParentNode = document): App[] => {
  const applications: App[] = [];
  scope.querySelectorAll(rootSelector).forEach((candidate): void => {
    if (!(candidate instanceof HTMLElement) || mountedRoots.has(candidate)) {
      return;
    }
    mountedRoots.add(candidate);
    const application = createProfileEditingApp(readEditingContext(candidate));
    application.mount(candidate);
    applications.push(application);
  });
  return applications;
};

initializeProfileEditors();
