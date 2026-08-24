import { rootSelector } from "./profile/common.js";
import { initializeFieldEditing } from "./profile/fields.js";
import { initializeImageEditing } from "./profile/image.js";
import { initializeStickyImageOffset } from "./profile/sticky-image.js";
import { initializeSkipSync } from "./profile/sync.js";

export const initializeInlineProfiles = (scope = document) => {
  scope.querySelectorAll(rootSelector).forEach((root) => {
    if (!(root instanceof HTMLElement)) {
      return;
    }
    initializeStickyImageOffset(root);
    initializeFieldEditing(root);
    initializeSkipSync(root);
    initializeImageEditing(root);
  });
};

initializeInlineProfiles();
