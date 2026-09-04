import {
  requestJson,
  showStatus,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  toEditingContext,
  type EditingTarget,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";

interface ErrorResult {
  message?: string;
}

interface RequestError extends Error {
  result?: ErrorResult;
}

interface SkipSyncController {
  updateSkipSync(event: Event): Promise<void>;
}

const syncCheckboxSelector = ".academic-persons-profile-editing__sync-checkbox";

export const createSkipSync = (
  editingTarget: EditingTarget,
): SkipSyncController => {
  const context = toEditingContext(editingTarget);
  const root = context.root;
  const checkbox = root.querySelector<HTMLInputElement>(syncCheckboxSelector);
  const form = checkbox?.closest<HTMLFormElement>("form") ?? null;
  let persistedValue = checkbox?.checked ?? false;

  const updateSkipSync = async (event: Event): Promise<void> => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
      return;
    }
    const profileUid = context.profileUid;
    const updateUrl = context.urls.skipSync;
    if (profileUid === null || updateUrl === undefined) {
      target.checked = persistedValue;
      showStatus(context, "danger");
      return;
    }
    const requestedValue = target.checked;
    form?.setAttribute("aria-busy", "true");
    target.disabled = true;
    showStatus(context, "info", context.messages.saving ?? null);
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
      target.checked = persistedValue;
      target.classList.remove("is-invalid");
      showStatus(context, "success");
    } catch (error) {
      const result = (error as RequestError).result;
      target.checked = persistedValue;
      target.classList.add("is-invalid");
      showStatus(context, "danger", result?.message ?? null);
    } finally {
      target.disabled = false;
      form?.setAttribute("aria-busy", "false");
    }
  };

  return { updateSkipSync };
};
