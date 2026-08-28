import Cropper from "../../vendor/cropperjs/2.2.0/cropper.esm.min.js";
import { getProfileUid, requestJson, showStatus } from "./common.js";

const imageFormSelector = ".academic-persons-inline-edit__image-form";
const imageModalSelector = "[data-ie-image-modal]";
const cropperStageSelector = "[data-ie-image-cropper-stage]";
const cropperSourceSelector = "[data-ie-image-cropper-source]";
const cropperFallbackSelector = "[data-ie-image-modal-fallback]";
const maximumCroppedImageWidth = 2400;
const supportedOutputMimeTypes = new Set([
  "image/jpeg",
  "image/png",
  "image/webp",
]);

export const parseImageRatio = (value) => {
  const normalized = String(value ?? "").trim();
  const ratioMatch = /^(\d+(?:\.\d+)?)\s*(?:x|:|\/)\s*(\d+(?:\.\d+)?)$/i.exec(
    normalized,
  );
  if (ratioMatch) {
    const width = Number.parseFloat(ratioMatch[1]);
    const height = Number.parseFloat(ratioMatch[2]);
    const ratio = width / height;
    return Number.isFinite(ratio) && ratio > 0 ? ratio : null;
  }
  const ratio = Number(normalized);
  return Number.isFinite(ratio) && ratio > 0 ? ratio : null;
};

export const configureCropperSelection = (selection, ratio) => {
  selection.aspectRatio = ratio;
  selection.initialAspectRatio = ratio;
  selection.initialCoverage = 0.7;
};

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
  const images = preview.querySelectorAll("img");
  if (images.length === 0) {
    return;
  }
  preview
    .querySelectorAll("source")
    .forEach((source) => source.removeAttribute("srcset"));
  images.forEach((image) => {
    image.removeAttribute("srcset");
    image.src = url;
    image.alt = alt;
    image.title = title;
  });
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

const getCroppedImageWidth = (cropper, selection) => {
  const cropperImage = cropper.getCropperImage();
  const transform =
    typeof cropperImage?.$getTransform === "function"
      ? cropperImage.$getTransform()
      : null;
  const scale = Array.isArray(transform)
    ? Math.hypot(Number(transform[0]), Number(transform[1]))
    : Number.NaN;
  const sourceWidth =
    Number.isFinite(scale) && scale > 0
      ? Math.round(selection.width / scale)
      : Math.round(selection.width);
  return Math.min(maximumCroppedImageWidth, Math.max(1, sourceWidth));
};

const setInitialCropperSelection = (selection, ratio) => {
  const canvas = selection.parentElement;
  const canvasWidth = canvas?.clientWidth ?? 0;
  const canvasHeight = canvas?.clientHeight ?? 0;
  if (canvasWidth <= 0 || canvasHeight <= 0) {
    return false;
  }
  let width = canvasWidth * 0.85;
  let height = width / ratio;
  if (height > canvasHeight * 0.85) {
    height = canvasHeight * 0.85;
    width = height * ratio;
  }
  selection.$change(
    (canvasWidth - width) / 2,
    (canvasHeight - height) / 2,
    width,
    height,
    ratio,
    true,
  );
  return selection.width > 0 && selection.height > 0;
};

const canvasToBlob = (canvas, mimeType) =>
  new Promise((resolve, reject) => {
    canvas.toBlob(
      (blob) => {
        if (blob instanceof Blob) {
          resolve(blob);
          return;
        }
        reject(new Error("The cropped image could not be encoded."));
      },
      mimeType,
      mimeType === "image/png" ? undefined : 0.92,
    );
  });

const getCroppedImageFileName = (file, mimeType) => {
  if (mimeType === file.type) {
    return file.name;
  }
  const extension = mimeType === "image/jpeg" ? "jpg" : mimeType.split("/")[1];
  const basename = file.name.replace(/\.[^.]+$/, "");
  return `${basename || "profile-image"}.${extension}`;
};

export const createCroppedImageFile = async (cropper, file) => {
  const selection = cropper?.getCropperSelection();
  if (
    !selection ||
    typeof selection.$toCanvas !== "function" ||
    selection.width <= 0 ||
    selection.height <= 0
  ) {
    throw new Error("The image crop is unavailable.");
  }
  const canvas = await selection.$toCanvas({
    width: getCroppedImageWidth(cropper, selection),
  });
  if (
    !(canvas instanceof HTMLCanvasElement) ||
    canvas.width <= 0 ||
    canvas.height <= 0
  ) {
    throw new Error("The image crop is empty.");
  }
  const requestedMimeType = supportedOutputMimeTypes.has(file.type)
    ? file.type
    : "image/png";
  const blob = await canvasToBlob(canvas, requestedMimeType);
  return new File(
    [blob],
    getCroppedImageFileName(file, blob.type || requestedMimeType),
    {
      type: blob.type || requestedMimeType,
      lastModified: Date.now(),
    },
  );
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
  const cropperStage = modal.querySelector(cropperStageSelector);
  const cropperSource = modal.querySelector(cropperSourceSelector);
  const cropperFallback = modal.querySelector(cropperFallbackSelector);
  const cropperRequested =
    (modal.dataset.ieImageRenderType ?? "").toLowerCase() === "cropper";
  const cropperRatio = parseImageRatio(modal.dataset.ieImageCropperRatio);
  const cropperEnabled = cropperRequested && cropperRatio !== null;
  let cropper = null;
  let cropperReady = false;
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
  const setCropperVisibility = (visible) => {
    cropperStage?.classList.toggle("d-none", !visible);
    cropperFallback?.classList.toggle("d-none", visible);
  };
  const destroyCropper = () => {
    cropper?.destroy();
    cropper = null;
    cropperReady = false;
  };
  const hasCroppableSource = () =>
    fileInput.files?.[0] instanceof File || root.dataset.hasImage === "1";
  const initializeCropper = () => {
    destroyCropper();
    const sourceAvailable = hasCroppableSource();
    setCropperVisibility(cropperEnabled && sourceAvailable);
    if (!sourceAvailable) {
      return;
    }
    if (!cropperEnabled) {
      if (cropperRequested) {
        showImageError(root.dataset.messageErrorMessage ?? "");
      }
      return;
    }
    if (
      !(cropperStage instanceof HTMLElement) ||
      !(cropperSource instanceof HTMLImageElement)
    ) {
      setCropperVisibility(false);
      showImageError(root.dataset.messageErrorMessage ?? "");
      return;
    }
    try {
      cropper = new Cropper(cropperSource, { container: cropperStage });
      const selection = cropper.getCropperSelection();
      const cropperImage = cropper.getCropperImage();
      if (!selection || typeof cropperImage?.$ready !== "function") {
        throw new Error("The CropperJS image or selection is unavailable.");
      }
      configureCropperSelection(selection, cropperRatio);
      const activeCropper = cropper;
      cropperImage
        .$ready()
        .then(() => {
          if (cropper !== activeCropper) {
            return;
          }
          cropperReady = setInitialCropperSelection(selection, cropperRatio);
          if (!cropperReady) {
            showImageError(root.dataset.messageErrorMessage ?? "");
          }
          updateActionAvailability();
        })
        .catch(() => {
          if (cropper !== activeCropper) {
            return;
          }
          destroyCropper();
          setCropperVisibility(false);
          showImageError(root.dataset.messageErrorMessage ?? "");
          updateActionAvailability();
        });
    } catch (error) {
      destroyCropper();
      setCropperVisibility(false);
      showImageError(root.dataset.messageErrorMessage ?? "");
    }
  };
  const copyPagePreviewToModal = (activateCropper = true) => {
    destroyCropper();
    const image = pagePreview.querySelector("img");
    if (image instanceof HTMLImageElement) {
      setImagePreviewUrl(modalPreview, image.src, image.alt, image.title);
    }
    if (activateCropper) {
      initializeCropper();
    } else {
      setCropperVisibility(false);
    }
  };
  const previewSelectedFile = (file) => {
    destroyCropper();
    releaseSelectedPreviewUrl();
    selectedPreviewUrl = URL.createObjectURL(file);
    setImagePreviewUrl(modalPreview, selectedPreviewUrl, file.name, file.name);
    initializeCropper();
  };
  const commitUploadedPreview = (file, previewUrl, alternative, title) => {
    if (!previewUrl) {
      return;
    }
    releasePersistedPreviewUrl();
    if (selectedPreviewUrl === previewUrl) {
      selectedPreviewUrl = null;
    } else {
      releaseSelectedPreviewUrl();
    }
    getImagePreviews(root).forEach((preview) => {
      setImagePreviewUrl(
        preview,
        previewUrl,
        alternative ?? file.name,
        title ?? file.name,
      );
    });
    persistedPreviewUrl = previewUrl;
  };
  const updateActionAvailability = () => {
    const hasSelectedFile = fileInput.files?.length === 1;
    const imageReady = !cropperRequested || (cropperEnabled && cropperReady);
    fileInput.disabled = requestPending;
    uploadButton.disabled = requestPending || !hasSelectedFile || !imageReady;
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
    copyPagePreviewToModal(false);
    updateActionAvailability();
  });
  modal.addEventListener("shown.bs.modal", () => {
    initializeCropper();
    updateActionAvailability();
  });
  modal.addEventListener("hide.bs.modal", (event) => {
    if (requestPending) {
      event.preventDefault();
    }
  });
  modal.addEventListener("hidden.bs.modal", () => {
    form.reset();
    destroyCropper();
    releaseSelectedPreviewUrl();
    copyPagePreviewToModal(false);
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
  setCropperVisibility(false);
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
    const formData = new FormData(form);
    clearImageError();
    setRequestPending(true, uploadButton);
    let uploadSucceeded = false;
    let uploadPreviewUrl = selectedPreviewUrl;
    try {
      const uploadFile = cropperRequested
        ? await createCroppedImageFile(cropperEnabled ? cropper : null, file)
        : file;
      if (cropperRequested) {
        uploadPreviewUrl = URL.createObjectURL(uploadFile);
      }
      if (!fileInput.name) {
        throw new Error("The image upload field has no name.");
      }
      formData.set(fileInput.name, uploadFile, uploadFile.name);
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
      commitUploadedPreview(
        uploadFile,
        uploadPreviewUrl,
        result.imageAlternative,
        result.imageTitle,
      );
      uploadPreviewUrl = null;
      setImageState(root, true);
      uploadSucceeded = true;
      showStatus(root, "success", root.dataset.messageImageUploaded ?? null);
    } catch (error) {
      if (uploadPreviewUrl && uploadPreviewUrl !== selectedPreviewUrl) {
        URL.revokeObjectURL(uploadPreviewUrl);
      }
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
        destroyCropper();
        releaseSelectedPreviewUrl();
        releasePersistedPreviewUrl();
        getImagePreviews(root).forEach((preview) => {
          setImagePreviewUrl(
            preview,
            placeholderUrl,
            root.dataset.placeholderImageAlt ?? "",
          );
        });
        setCropperVisibility(false);
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
    destroyCropper();
    releaseSelectedPreviewUrl();
    releasePersistedPreviewUrl();
  });
};
