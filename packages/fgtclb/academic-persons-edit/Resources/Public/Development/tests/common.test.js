import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import {
  getProfileUid,
  isEditableField,
  requestJson,
  rootSelector,
  showStatus,
} from "../../JavaScript/frontend/profile/common.js";

const createRoot = () => {
  const root = document.createElement("section");
  root.dataset.profileUid = "42";
  root.dataset.messageErrorTitle = "Error";
  root.dataset.messageErrorMessage = "Failed";
  root.dataset.messageSuccessTitle = "Success";
  root.dataset.messageSuccessMessage = "Saved";
  root.dataset.messageInfoTitle = "Info";
  root.dataset.messageInfoMessage = "Working";
  root.dataset.messageWarningTitle = "Warning";
  root.dataset.messageValidation = "Invalid";
  root.innerHTML = `
    <div class="d-none bg-info" data-ie-status-toast>
      <span class="status-title"></span>
      <span class="status-message"></span>
    </div>
  `;
  document.body.append(root);
  return root;
};

describe("profile/common", () => {
  beforeEach(() => {
    globalThis.fetch = jest.fn();
  });

  test("exports the component root selector and recognizes editable controls", () => {
    expect(rootSelector).toBe("[data-academic-persons-inline-edit]");
    expect(isEditableField(document.createElement("input"))).toBe(true);
    expect(isEditableField(document.createElement("select"))).toBe(true);
    expect(isEditableField(document.createElement("textarea"))).toBe(true);
    expect(isEditableField(document.createElement("div"))).toBe(false);
    expect(isEditableField(null)).toBe(false);
  });

  test("returns only positive integer profile identifiers", () => {
    const root = createRoot();
    expect(getProfileUid(root)).toBe(42);
    root.dataset.profileUid = "0";
    expect(getProfileUid(root)).toBeNull();
    root.dataset.profileUid = "invalid";
    expect(getProfileUid(root)).toBeNull();
    delete root.dataset.profileUid;
    expect(getProfileUid(root)).toBeNull();
  });

  test.each([
    ["danger", "Error", "Failed", "bg-danger"],
    ["success", "Success", "Saved", "bg-success"],
    ["info", "Info", "Working", "bg-info"],
    ["warning", "Warning", "Invalid", "bg-warning"],
    ["unknown", "Error", "Failed", "bg-danger"],
  ])("renders the %s status", (type, title, message, className) => {
    const root = createRoot();
    const show = jest.fn();
    const getOrCreateInstance = jest.fn(() => ({ show }));
    globalThis.bootstrap = { Toast: { getOrCreateInstance } };

    showStatus(root, type);

    const toast = root.querySelector("[data-ie-status-toast]");
    expect(toast.classList.contains(className)).toBe(true);
    expect(toast.classList.contains("d-none")).toBe(false);
    expect(toast.querySelector(".status-title").textContent).toBe(title);
    expect(toast.querySelector(".status-message").textContent).toBe(message);
    expect(getOrCreateInstance).toHaveBeenCalledWith(toast);
    expect(show).toHaveBeenCalledTimes(1);
  });

  test("uses an explicit status message and tolerates missing toast markup", () => {
    const root = createRoot();
    showStatus(root, "success", "Explicit");
    expect(root.querySelector(".status-message").textContent).toBe("Explicit");

    root.replaceChildren();
    expect(() => showStatus(root, "danger")).not.toThrow();
  });

  test("requests JSON with same-origin credentials and merged headers", async () => {
    globalThis.fetch.mockResolvedValue({
      ok: true,
      json: jest.fn().mockResolvedValue({ success: true, value: 7 }),
    });

    await expect(
      requestJson("/update", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: "{}",
      }),
    ).resolves.toEqual({ success: true, value: 7 });
    expect(globalThis.fetch).toHaveBeenCalledWith("/update", {
      credentials: "same-origin",
      method: "POST",
      body: "{}",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
    });
  });

  test.each([
    [false, { success: true, message: "HTTP error" }],
    [true, { success: false, message: "Application error" }],
    [true, null],
  ])("rejects failed responses", async (ok, result) => {
    globalThis.fetch.mockResolvedValue({
      ok,
      json: jest.fn().mockImplementation(() =>
        result === null
          ? Promise.reject(new SyntaxError("invalid JSON"))
          : Promise.resolve(result),
      ),
    });

    await expect(requestJson("/update", {})).rejects.toMatchObject({
      message: "The request failed.",
      result,
    });
  });
});
