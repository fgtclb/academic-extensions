import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import { initializeSkipSync } from "../../JavaScript/frontend/profile/sync.js";

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

const createRoot = () => {
  const root = document.createElement("section");
  root.dataset.profileUid = "7";
  root.dataset.skipSyncUrl = "/skip-sync";
  root.dataset.messageSaving = "Saving";
  root.dataset.messageErrorTitle = "Error";
  root.dataset.messageErrorMessage = "Failed";
  root.dataset.messageSuccessTitle = "Success";
  root.dataset.messageSuccessMessage = "Saved";
  root.dataset.messageInfoTitle = "Info";
  root.dataset.messageInfoMessage = "Working";
  root.innerHTML = `
    <form data-ie-sync-form>
      <input class="academic-persons-inline-edit__sync-checkbox" type="checkbox">
    </form>
    <div class="d-none" data-ie-status-toast>
      <span class="status-title"></span><span class="status-message"></span>
    </div>
  `;
  document.body.append(root);
  return {
    root,
    form: root.querySelector("form"),
    checkbox: root.querySelector("input"),
  };
};

const response = (result, ok = true) => ({
  ok,
  json: jest.fn().mockResolvedValue(result),
});

describe("profile/sync", () => {
  beforeEach(() => {
    globalThis.fetch = jest.fn();
  });

  test("ignores incomplete synchronization markup", () => {
    expect(() => initializeSkipSync(document.createElement("section"))).not.toThrow();
    const root = document.createElement("section");
    root.innerHTML = "<form data-ie-sync-form></form>";
    expect(() => initializeSkipSync(root)).not.toThrow();
  });

  test("prevents native form submission", () => {
    const { root, form } = createRoot();
    initializeSkipSync(root);
    const event = new Event("submit", { cancelable: true });
    form.dispatchEvent(event);
    expect(event.defaultPrevented).toBe(true);
  });

  test("persists a changed checkbox and applies the server value", async () => {
    const { root, form, checkbox } = createRoot();
    globalThis.fetch.mockResolvedValue(response({ success: true, skipSync: true }));
    initializeSkipSync(root);

    checkbox.checked = true;
    checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    expect(form.getAttribute("aria-busy")).toBe("true");
    expect(checkbox.disabled).toBe(true);
    await flushPromises();

    expect(globalThis.fetch).toHaveBeenCalledWith("/skip-sync", {
      credentials: "same-origin",
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ profile: 7, data: { skipSync: true } }),
    });
    expect(checkbox.checked).toBe(true);
    expect(checkbox.disabled).toBe(false);
    expect(checkbox.classList.contains("is-invalid")).toBe(false);
    expect(form.hasAttribute("aria-busy")).toBe(false);
  });

  test("restores the persisted value when configuration is incomplete", async () => {
    const { root, checkbox } = createRoot();
    delete root.dataset.skipSyncUrl;
    initializeSkipSync(root);
    checkbox.checked = true;
    checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();

    expect(checkbox.checked).toBe(false);
    expect(globalThis.fetch).not.toHaveBeenCalled();
    expect(root.querySelector("[data-ie-status-toast]").classList.contains("bg-danger"))
      .toBe(true);
  });

  test("restores the last successful value and reports request errors", async () => {
    const { root, checkbox } = createRoot();
    globalThis.fetch
      .mockResolvedValueOnce(response({ success: true, skipSync: true }))
      .mockResolvedValueOnce(response({
        success: false,
        message: "Rejected",
      }, false));
    initializeSkipSync(root);

    checkbox.checked = true;
    checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();
    checkbox.checked = false;
    checkbox.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();

    expect(checkbox.checked).toBe(true);
    expect(checkbox.classList.contains("is-invalid")).toBe(true);
    expect(root.querySelector(".status-message").textContent).toBe("Rejected");
    expect(checkbox.disabled).toBe(false);
  });
});
