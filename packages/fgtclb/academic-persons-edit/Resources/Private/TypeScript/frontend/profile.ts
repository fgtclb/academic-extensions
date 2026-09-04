/**
 * The entry point of the profile editor, loaded by
 * `Templates/Profile/Index.html` through `<f:asset.module>`.
 *
 * It defines the custom element and does nothing else. Every editor on the page
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

registerProfileEditingElement();
