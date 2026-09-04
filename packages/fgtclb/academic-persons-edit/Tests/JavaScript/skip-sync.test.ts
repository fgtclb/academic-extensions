import assert from "node:assert/strict";
import { beforeEach, describe, it } from "node:test";
import { resetBody } from "../../../../../Build/tests/dom.mjs";
import { installFetch, type FetchDouble } from "../../../../../Build/tests/fetch.mjs";
import { createSkipSync } from "@fgtclb/academic-persons-edit/frontend/profile/sync.js";
import {
  endpoints,
  messages,
  profileEditingRoot,
  profileHeader,
  select,
} from "./Fixtures/profile-editing.ts";

/**
 * The synchronisation switch of `Partials/Profile/Header.html` is the smallest
 * of the editor's writing controls and the only one with no save button: it
 * saves on change. That is what makes its failure path worth pinning - a
 * checkbox that stays where the visitor put it while the database says
 * something else shows a state that does not exist, and nothing on screen says
 * so.
 */
describe("the synchronisation switch", () => {
  let fetch: FetchDouble;
  let root: HTMLElement;
  let checkbox: HTMLInputElement;
  let form: HTMLFormElement;
  let updateSkipSync: (event: Event) => Promise<void>;

  beforeEach(() => {
    fetch = installFetch();
    const body = resetBody(
      profileEditingRoot({ content: profileHeader({ skipSync: false }) }),
    );
    root = select(body, "[data-academic-persons-profile-editing]", HTMLElement);
    checkbox = select(
      root,
      ".academic-persons-profile-editing__sync-checkbox",
      HTMLInputElement,
    );
    form = select(root, "[data-pe-sync-form]", HTMLFormElement);
    updateSkipSync = createSkipSync(root).updateSkipSync;
  });

  /**
   * The event carries the control, so the handler is driven the way the DOM
   * drives it - through `event.target` - rather than by handing it the element.
   */
  const dispatch = (checked: boolean): Promise<void> => {
    checkbox.checked = checked;
    const event = new CustomEvent("change", { bubbles: true });
    Object.defineProperty(event, "target", { value: checkbox });

    return updateSkipSync(event);
  };

  it("posts the switched value for this profile", async () => {
    fetch.respond({ success: true, skipSync: true });

    await dispatch(true);

    const call = fetch.calls[0];
    assert.equal(call?.url, endpoints.skipSync);
    assert.equal(call?.method, "POST");
    assert.equal(call?.headers["X-Requested-With"], "XMLHttpRequest");
    assert.deepEqual(call?.body, { profile: 1, data: { skipSync: true } });
  });

  it("marks the form busy and the control unavailable while it saves", async () => {
    const slow = fetch.respondLater();

    const pending = dispatch(true);
    assert.equal(form.getAttribute("aria-busy"), "true");
    assert.equal(checkbox.disabled, true);

    slow.settle({ success: true, skipSync: true });
    await pending;
    assert.equal(form.getAttribute("aria-busy"), "false");
    assert.equal(checkbox.disabled, false);
  });

  /**
   * The server has the last word: it answers with the value it stored, and the
   * control is set from that rather than from what was clicked.
   */
  it("takes the stored value from the response", async () => {
    fetch.respond({ success: true, skipSync: false });

    await dispatch(true);

    assert.equal(checkbox.checked, false);
    assert.equal(checkbox.classList.contains("is-invalid"), false);
    assert.equal(
      select(root, '[data-pe-status-toast="status"] .status-title', HTMLElement)
        .textContent,
      "Saved",
    );
  });

  it("puts the control back and marks it invalid when the request fails", async () => {
    fetch.respondWithError({ success: false, message: "Locked by an editor." }, 403);

    await dispatch(true);

    assert.equal(checkbox.checked, false);
    assert.ok(checkbox.classList.contains("is-invalid"));
    assert.equal(
      select(root, '[data-pe-status-toast="alert"] .status-message', HTMLElement)
        .textContent,
      "Locked by an editor.",
    );
  });

  /**
   * The revert is to the last *stored* value, not to the opposite of what was
   * clicked: a failed second change must not undo a successful first one.
   */
  it("reverts to the last stored value, not to the previous click", async () => {
    fetch.respond({ success: true, skipSync: true });
    await dispatch(true);

    fetch.respondWithError({ success: false }, 500);
    await dispatch(false);

    assert.equal(checkbox.checked, true);
  });

  it("refuses to send anything when the endpoint is not configured", async () => {
    root.removeAttribute("data-skip-sync-url");

    await dispatch(true);

    assert.equal(fetch.calls.length, 0);
    assert.equal(checkbox.checked, false);
    assert.equal(
      select(root, '[data-pe-status-toast="alert"] .status-message', HTMLElement)
        .textContent,
      messages.errorMessage,
    );
  });
});
