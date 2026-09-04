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
  createDocumentEditing,
  initializeDocumentSections,
} from "@fgtclb/academic-persons-edit/frontend/profile/documents.js";
import { initializeFieldEditing } from "@fgtclb/academic-persons-edit/frontend/profile/fields.js";
import { createImageEditing } from "@fgtclb/academic-persons-edit/frontend/profile/image.js";
import { initializeStickyImageOffset } from "@fgtclb/academic-persons-edit/frontend/profile/sticky-image.js";
import { createSkipSync } from "@fgtclb/academic-persons-edit/frontend/profile/sync.js";

const mountedRoots = new WeakSet<HTMLElement>();

const createProfileEditingApp = (root: HTMLElement): App =>
  createApp({
    setup(): Record<string, unknown> {
      const documentController = createDocumentEditing(root);
      const imageController = createImageEditing(root);
      const syncController = createSkipSync(root);

      onMounted((): void => {
        initializeStickyImageOffset(root);
        initializeFieldEditing(root);
        initializeDocumentSections(root);
        initializePopover(root);
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
    const application = createProfileEditingApp(candidate);
    application.mount(candidate);
    applications.push(application);
  });
  return applications;
};

initializeProfileEditors();
