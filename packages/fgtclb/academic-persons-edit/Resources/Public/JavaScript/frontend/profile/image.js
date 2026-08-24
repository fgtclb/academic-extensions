import { getProfileUid, requestJson, showStatus } from "./common.js";

const imageFormSelector = ".academic-persons-inline-edit__image-form";
const imageModalSelector = "[data-ie-image-modal]";

export const getImagePreviews = (root) =>
  Array.from(
    root.querySelectorAll(
      "[data-ie-image-preview], [data-ie-image-modal-preview]",
    ),
  );

export const getImagePreview = (root, selector) => {
  const preview = root.querySelector(selector);
  return preview instanceof HTMLElement ? preview : null;
};

export const setImagePreviewUrl = (preview, url, alt = "", title = "") => {
  const image = preview.querySelector("img");
  if (!(image instanceof HTMLImageElement)) {
    return;
  }
  preview
    .querySelectorAll("source")
    .forEach((source) => source.removeAttribute("srcset"));
  image.removeAttribute("srcset");
  image.src = url;
  image.alt = alt;
  image.title = title;
};

export const setImageState = (root, hasImage) => {
  root.dataset.hasImage = hasImage ? "1" : "0";
  root
    .querySelector("[data-ie-delete-image]")
    ?.classList.toggle("d-none", !hasImage);
  root
    .querySelector("[data-ie-image-delete-hint]")
    ?.classList.toggle("d-none", !hasImage);
};

export const initializeImageEditing = (root) => {
  const form = root.querySelector(imageFormSelector);
  const modal = root.querySelector(imageModalSelector);
  const openButton = root.querySelector("[data-ie-open-image-modal]");
  const fileInput = form?.querySelector('input[type="file"]');
  const uploadButton = form?.querySelector("[data-ie-upload-image]");
  const deleteButton = root.querySelector("[data-ie-delete-image]");
  const pagePreview = getImagePreview(root, "[data-ie-image-preview]");
  const modalPreview = getImagePreview(root, "[data-ie-image-modal-preview]");
  const Modal = globalThis.bootstrap?.Modal;
  if (
    !(form instanceof HTMLFormElement) ||
    !(modal instanceof HTMLElement) ||
    !(openButton instanceof HTMLButtonElement) ||
    !(fileInput instanceof HTMLInputElement) ||
    !(uploadButton instanceof HTMLButtonElement) ||
    !(pagePreview instanceof HTMLElement) ||
    !(modalPreview instanceof HTMLElement) ||
    !Modal
  ) {
    return;
  }

  const modalInstance = Modal.getOrCreateInstance(modal);
  let requestPending = false;
  let selectedPreviewUrl = null;
  let persistedPreviewUrl = null;

  const releaseSelectedPreviewUrl = () => {
    if (selectedPreviewUrl) {
      URL.revokeObjectURL(selectedPreviewUrl);
      selectedPreviewUrl = null;
    }
  };
  const releasePersistedPreviewUrl = () => {
    if (persistedPreviewUrl) {
      URL.revokeObjectURL(persistedPreviewUrl);
      persistedPreviewUrl = null;
    }
  };
  const copyPagePreviewToModal = () => {
    const image = pagePreview.querySelector("img");
    if (image instanceof HTMLImageElement) {
      setImagePreviewUrl(modalPreview, image.src, image.alt, image.title);
    }
  };
  const previewSelectedFile = (file) => {
    releaseSelectedPreviewUrl();
    selectedPreviewUrl = URL.createObjectURL(file);
    setImagePreviewUrl(modalPreview, selectedPreviewUrl, file.name, file.name);
  };
  const commitSelectedPreview = (file) => {
    if (!selectedPreviewUrl) {
      return;
    }
    releasePersistedPreviewUrl();
    getImagePreviews(root).forEach((preview) => {
      setImagePreviewUrl(preview, selectedPreviewUrl, file.name, file.name);
    });
    persistedPreviewUrl = selectedPreviewUrl;
    selectedPreviewUrl = null;
  };
  const clearImageError = () => {
    fileInput.classList.remove("is-invalid");
    const feedback = form.querySelector("[data-ie-image-error]");
    if (feedback) {
      feedback.textContent = "";
      feedback.classList.add("d-none");
    }
  };
  const showImageError = (message) => {
    fileInput.classList.add("is-invalid");
    const feedback = form.querySelector("[data-ie-image-error]");
    if (feedback) {
      feedback.textContent = message;
      feedback.classList.remove("d-none");
    }
  };
  const updateActionAvailability = () => {
    const hasSelectedFile = fileInput.files?.length === 1;
    fileInput.disabled = requestPending;
    uploadButton.disabled = requestPending || !hasSelectedFile;
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.disabled = requestPending || root.dataset.hasImage !== "1";
    }
    modal.querySelectorAll("[data-ie-close-image-modal]").forEach((button) => {
      if (button instanceof HTMLButtonElement) {
        button.disabled = requestPending;
      }
    });
  };
  const setRequestPending = (pending, activeButton = null) => {
    requestPending = pending;
    if (pending) {
      modal.setAttribute("aria-busy", "true");
    } else {
      modal.removeAttribute("aria-busy");
    }
    modal.querySelectorAll("[data-ie-action-spinner]").forEach((spinner) => {
      spinner.classList.toggle(
        "d-none",
        !pending || spinner.closest("button") !== activeButton,
      );
    });
    updateActionAvailability();
  };

  modal.addEventListener("show.bs.modal", () => {
    clearImageError();
    copyPagePreviewToModal();
    updateActionAvailability();
  });
  modal.addEventListener("hide.bs.modal", (event) => {
    if (requestPending) {
      event.preventDefault();
    }
  });
  modal.addEventListener("hidden.bs.modal", () => {
    form.reset();
    releaseSelectedPreviewUrl();
    copyPagePreviewToModal();
    clearImageError();
    updateActionAvailability();
    openButton.focus();
  });
  fileInput.addEventListener("change", () => {
    clearImageError();
    const file = fileInput.files?.[0];
    if (file instanceof File) {
      previewSelectedFile(file);
    } else {
      releaseSelectedPreviewUrl();
      copyPagePreviewToModal();
    }
    updateActionAvailability();
  });

  setImageState(root, root.dataset.hasImage === "1");
  updateActionAvailability();

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (requestPending) {
      return;
    }
    if (!form.reportValidity()) {
      showImageError(root.dataset.messageValidation ?? "");
      return;
    }
    const file = fileInput.files?.[0];
    if (!(file instanceof File)) {
      showImageError(root.dataset.messageValidation ?? "");
      return;
    }

    // FormData must be built before the file input is disabled.
    const formData = new FormData(form);
    clearImageError();
    setRequestPending(true, uploadButton);
    let uploadSucceeded = false;
    try {
      const result = await requestJson(form.action, {
        method: "POST",
        body: formData,
      });
      if (result.hasImage !== true) {
        const error = new Error("The upload returned no profile image.");
        error.result = {
          message: root.dataset.messageImageUploadMissing ?? "",
        };
        throw error;
      }
      commitSelectedPreview(file);
      setImageState(root, true);
      uploadSucceeded = true;
      showStatus(root, "success", root.dataset.messageImageUploaded ?? null);
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      const message =
        result?.error === "image_upload_missing"
          ? (root.dataset.messageImageUploadMissing ?? "")
          : (result?.message ?? root.dataset.messageErrorMessage ?? "");
      showImageError(message);
    } finally {
      setRequestPending(false);
      if (uploadSucceeded) {
        modalInstance.hide();
      }
    }
  });

  deleteButton?.addEventListener("click", async () => {
    if (requestPending || root.dataset.hasImage !== "1") {
      return;
    }
    const profileUid = getProfileUid(root);
    const deleteUrl = root.dataset.deleteImageUrl;
    if (profileUid === null || !deleteUrl) {
      showStatus(root, "danger");
      return;
    }

    setRequestPending(
      true,
      deleteButton instanceof HTMLButtonElement ? deleteButton : null,
    );
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    let deletionSucceeded = false;
    try {
      await requestJson(deleteUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile: profileUid, data: {} }),
      });
      const placeholderUrl = root.dataset.placeholderImageUrl;
      if (placeholderUrl) {
        releaseSelectedPreviewUrl();
        releasePersistedPreviewUrl();
        getImagePreviews(root).forEach((preview) => {
          setImagePreviewUrl(
            preview,
            placeholderUrl,
            root.dataset.placeholderImageAlt ?? "",
          );
        });
      }
      setImageState(root, false);
      deletionSucceeded = true;
      showStatus(root, "success", root.dataset.messageImageDeleted ?? null);
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      showImageError(result?.message ?? root.dataset.messageErrorMessage ?? "");
    } finally {
      setRequestPending(false);
      if (deletionSucceeded) {
        modalInstance.hide();
      }
    }
  });

  globalThis.addEventListener("pagehide", () => {
    releaseSelectedPreviewUrl();
    releasePersistedPreviewUrl();
  });
};
