import { getProfileUid, requestJson, showStatus } from "./common.js";

const syncFormSelector = "[data-ie-sync-form]";
const syncCheckboxSelector = ".academic-persons-inline-edit__sync-checkbox";

export const initializeSkipSync = (root) => {
  const form = root.querySelector(syncFormSelector);
  const checkbox = form?.querySelector(syncCheckboxSelector);
  if (
    !(form instanceof HTMLFormElement) ||
    !(checkbox instanceof HTMLInputElement)
  ) {
    return;
  }

  let persistedValue = checkbox.checked;
  form.addEventListener("submit", (event) => event.preventDefault());
  checkbox.addEventListener("change", async () => {
    const profileUid = getProfileUid(root);
    const updateUrl = root.dataset.skipSyncUrl;
    if (profileUid === null || !updateUrl) {
      checkbox.checked = persistedValue;
      showStatus(root, "danger");
      return;
    }

    const requestedValue = checkbox.checked;
    form.setAttribute("aria-busy", "true");
    checkbox.disabled = true;
    showStatus(root, "info", root.dataset.messageSaving ?? null);

    try {
      const result = await requestJson(updateUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          profile: profileUid,
          data: { skipSync: requestedValue },
        }),
      });
      persistedValue = Boolean(result.skipSync);
      checkbox.checked = persistedValue;
      checkbox.classList.remove("is-invalid");
      showStatus(root, "success");
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      checkbox.checked = persistedValue;
      checkbox.classList.add("is-invalid");
      showStatus(root, "danger", result?.message ?? null);
    } finally {
      checkbox.disabled = false;
      form.removeAttribute("aria-busy");
    }
  });
};
