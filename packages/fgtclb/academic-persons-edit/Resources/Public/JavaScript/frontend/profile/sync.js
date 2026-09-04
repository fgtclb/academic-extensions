/* Generated from Resources/Private/TypeScript — do not edit. */
import {
  getProfileUid,
  requestJson,
  showStatus
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
const syncCheckboxSelector = ".academic-persons-profile-editing__sync-checkbox";
const createSkipSync = (root) => {
  const checkbox = root.querySelector(syncCheckboxSelector);
  const form = (checkbox == null ? void 0 : checkbox.closest("form")) ?? null;
  let persistedValue = (checkbox == null ? void 0 : checkbox.checked) ?? false;
  const updateSkipSync = async (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
      return;
    }
    const profileUid = getProfileUid(root);
    const updateUrl = root.dataset.skipSyncUrl;
    if (profileUid === null || updateUrl === void 0) {
      target.checked = persistedValue;
      showStatus(root, "danger");
      return;
    }
    const requestedValue = target.checked;
    form == null ? void 0 : form.setAttribute("aria-busy", "true");
    target.disabled = true;
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    try {
      const result = await requestJson(updateUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          profile: profileUid,
          data: { skipSync: requestedValue }
        })
      });
      persistedValue = Boolean(result.skipSync);
      target.checked = persistedValue;
      target.classList.remove("is-invalid");
      showStatus(root, "success");
    } catch (error) {
      const result = error.result;
      target.checked = persistedValue;
      target.classList.add("is-invalid");
      showStatus(root, "danger", (result == null ? void 0 : result.message) ?? null);
    } finally {
      target.disabled = false;
      form == null ? void 0 : form.setAttribute("aria-busy", "false");
    }
  };
  return { updateSkipSync };
};
export {
  createSkipSync
};
