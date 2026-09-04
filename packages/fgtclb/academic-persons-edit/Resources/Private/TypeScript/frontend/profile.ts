/**
 * The entry point of the profile editor, loaded by
 * `Templates/Profile/Index.html` through `<f:asset.module>`.
 *
 * It defines the custom elements and does nothing else. Every editor on the page
 * is a `<academic-persons-edit-profile-editing>` element that starts itself when
 * the browser upgrades it, which is what replaced the start-up scan for
 * `[data-academic-persons-profile-editing]` and the `WeakSet` of roots that had
 * already been mounted.
 *
 * `<f:asset.module>` renders `type="module"`, so this file is evaluated after
 * the document has been parsed and the elements are upgraded rather than
 * constructed. Neither order matters to the element: a custom element is
 * upgraded whether it was already in the document when it was defined or is
 * inserted afterwards.
 */
import { registerProfileEditingElement } from "@fgtclb/academic-persons-edit/frontend/profile/elements/root.js";
import { registerProfileImageEditorElement } from "@fgtclb/academic-persons-edit/frontend/profile/elements/image-editor.js";

// The root first, and the order is not cosmetic: it reads the contract and
// mounts the Vue application that still renders the rest of the editor, and
// that mount replaces the markup below it - including the image editor. An
// element defined before the mount would be upgraded on a copy that is about to
// be thrown away. The order stops mattering when the runtime leaves.
registerProfileEditingElement();
registerProfileImageEditorElement();
