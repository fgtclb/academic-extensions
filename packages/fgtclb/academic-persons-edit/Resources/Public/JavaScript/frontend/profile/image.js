/* Generated from Resources/Private/TypeScript — do not edit. */
import Cropper, {
} from "@fgtclb/academic-persons-edit/cropper";
import {
  nextTick,
  reactive,
  ref
} from "@fgtclb/academic-persons-edit/frontend/vue.js";
import {
  getProfileUid,
  requestJson,
  showStatus
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
const maximumCroppedImageWidth = 2400;
const imageClosingClass = "is-image-closing";
const imageEditorTargetSelector = "[data-ie-image-editor-target]";
const imagePreviewColumnSelector = "[data-ie-image-preview-column]";
const profileFieldsColumnSelector = ".academic-persons-inline-edit__profile-fields-column";
const supportedOutputMimeTypes = /* @__PURE__ */ new Set([
  "image/jpeg",
  "image/png",
  "image/webp"
]);
const parseImageRatio = (value) => {
  const normalized = String(value ?? "").trim();
  const ratioMatch = /^(\d+(?:\.\d+)?)\s*(?:x|:|\/)\s*(\d+(?:\.\d+)?)$/i.exec(
    normalized
  );
  if (ratioMatch !== null) {
    const width = Number.parseFloat(ratioMatch[1] ?? "");
    const height = Number.parseFloat(ratioMatch[2] ?? "");
    const ratio2 = width / height;
    return Number.isFinite(ratio2) && ratio2 > 0 ? ratio2 : null;
  }
  const ratio = Number(normalized);
  return Number.isFinite(ratio) && ratio > 0 ? ratio : null;
};
const configureCropperSelection = (selection, ratio) => {
  selection.aspectRatio = ratio;
  selection.initialAspectRatio = ratio;
  selection.initialCoverage = 0.7;
};
const getImagePreviews = (root) => Array.from(
  root.querySelectorAll(
    "[data-ie-image-preview], [data-ie-image-view-preview]"
  )
);
const getImagePreview = (root, selector) => root.querySelector(selector);
const setImagePreviewUrl = (preview, url, alt = "", title = "") => {
  const images = preview.querySelectorAll("img");
  if (images.length === 0) {
    return;
  }
  preview.querySelectorAll("source").forEach((source) => source.removeAttribute("srcset"));
  images.forEach((image) => {
    image.removeAttribute("srcset");
    image.src = url;
    image.alt = alt;
    image.title = title;
  });
};
const setImageState = (root, hasImage) => {
  root.dataset.hasImage = hasImage ? "1" : "0";
  const input = root.querySelector('input[type="file"]');
  if (input !== null) {
    input.required = !hasImage;
  }
};
const getCroppedImageWidth = (cropper, selection) => {
  var _a, _b;
  const transform = ((_b = (_a = cropper.getCropperImage()) == null ? void 0 : _a.$getTransform) == null ? void 0 : _b.call(_a)) ?? null;
  const scale = Array.isArray(transform) ? Math.hypot(Number(transform[0]), Number(transform[1])) : Number.NaN;
  const sourceWidth = Number.isFinite(scale) && scale > 0 ? Math.round(selection.width / scale) : Math.round(selection.width);
  return Math.min(maximumCroppedImageWidth, Math.max(1, sourceWidth));
};
const setInitialCropperSelection = (selection, ratio) => {
  const canvas = selection.parentElement;
  const canvasWidth = (canvas == null ? void 0 : canvas.clientWidth) ?? 0;
  const canvasHeight = (canvas == null ? void 0 : canvas.clientHeight) ?? 0;
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
    true
  );
  return selection.width > 0 && selection.height > 0;
};
const canvasToBlob = (canvas, mimeType) => new Promise((resolve, reject) => {
  canvas.toBlob(
    (blob) => {
      if (blob !== null) {
        resolve(blob);
        return;
      }
      reject(new Error("The cropped image could not be encoded."));
    },
    mimeType,
    mimeType === "image/png" ? void 0 : 0.92
  );
});
const getCroppedImageFileName = (file, mimeType) => {
  if (mimeType === file.type) {
    return file.name;
  }
  const extension = mimeType === "image/jpeg" ? "jpg" : mimeType.split("/")[1];
  const basename = file.name.replace(/\.[^.]+$/, "");
  return `${basename || "profile-image"}.${extension || "png"}`;
};
const createCroppedImageFile = async (cropper, file) => {
  const selection = cropper == null ? void 0 : cropper.getCropperSelection();
  if (cropper === null || selection === null || selection === void 0 || selection.width <= 0 || selection.height <= 0) {
    throw new Error("The image crop is unavailable.");
  }
  const canvas = await selection.$toCanvas({
    width: getCroppedImageWidth(cropper, selection)
  });
  if (canvas.width <= 0 || canvas.height <= 0) {
    throw new Error("The image crop is empty.");
  }
  const requestedMimeType = supportedOutputMimeTypes.has(file.type) ? file.type : "image/png";
  const blob = await canvasToBlob(canvas, requestedMimeType);
  return new File(
    [blob],
    getCroppedImageFileName(file, blob.type || requestedMimeType),
    {
      type: blob.type || requestedMimeType,
      lastModified: Date.now()
    }
  );
};
const createImageEditing = (root) => {
  const cropperSource = ref(null);
  const cropperStage = ref(null);
  const fileInput = ref(null);
  const cropperRequested = (root.dataset.imageRenderType ?? "").toLowerCase() === "cropper";
  const cropperRatio = parseImageRatio(root.dataset.imageCropperRatio);
  const image = reactive({
    closing: false,
    cropperEnabled: cropperRequested && cropperRatio !== null,
    cropperReady: false,
    cropperRequested,
    editing: false,
    error: "",
    hasImage: root.dataset.hasImage === "1",
    hasSelection: false,
    pending: false,
    previewUrl: "",
    selectedName: ""
  });
  let cropper = null;
  let selectedFile = null;
  let selectedPreviewUrl = null;
  let persistedPreviewUrl = null;
  let persistedAlternative = "";
  let persistedTitle = "";
  const getFileInput = () => fileInput.value ?? root.querySelector(
    '[data-ie-image-view-container] input[type="file"]'
  );
  const releaseUrl = (url) => {
    if (url !== null && url.startsWith("blob:")) {
      URL.revokeObjectURL(url);
    }
  };
  const destroyCropper = () => {
    cropper == null ? void 0 : cropper.destroy();
    cropper = null;
    image.cropperReady = false;
  };
  const initializeCropper = async () => {
    destroyCropper();
    if (!image.cropperRequested || !image.hasSelection || image.previewUrl === "") {
      return;
    }
    if (!image.cropperEnabled || cropperRatio === null || cropperSource.value === null || cropperStage.value === null) {
      image.error = root.dataset.messageErrorMessage ?? "";
      return;
    }
    try {
      cropper = new Cropper(cropperSource.value, {
        container: cropperStage.value
      });
      const selection = cropper.getCropperSelection();
      const cropperImage = cropper.getCropperImage();
      if (selection === null || cropperImage === null || cropperImage.$ready === void 0) {
        throw new Error("The CropperJS image or selection is unavailable.");
      }
      configureCropperSelection(selection, cropperRatio);
      const activeCropper = cropper;
      await cropperImage.$ready();
      if (cropper !== activeCropper) {
        return;
      }
      image.cropperReady = setInitialCropperSelection(selection, cropperRatio);
      if (!image.cropperReady) {
        image.error = root.dataset.messageErrorMessage ?? "";
      }
    } catch {
      destroyCropper();
      image.error = root.dataset.messageErrorMessage ?? "";
    }
  };
  const readPersistedPreview = () => {
    const preview = getImagePreview(root, "[data-ie-image-preview]");
    const previewImage = preview == null ? void 0 : preview.querySelector("img");
    persistedPreviewUrl = (previewImage == null ? void 0 : previewImage.currentSrc) || (previewImage == null ? void 0 : previewImage.src) || null;
    persistedAlternative = (previewImage == null ? void 0 : previewImage.alt) ?? "";
    persistedTitle = (previewImage == null ? void 0 : previewImage.title) ?? "";
    image.previewUrl = persistedPreviewUrl ?? "";
  };
  const resetSelection = () => {
    releaseUrl(selectedPreviewUrl);
    selectedPreviewUrl = null;
    selectedFile = null;
    image.hasSelection = false;
    image.selectedName = "";
    image.previewUrl = persistedPreviewUrl ?? "";
    const input = getFileInput();
    if (input !== null) {
      input.value = "";
    }
  };
  const getImageCloseScrollTop = () => {
    const target = root.querySelector(imageEditorTargetSelector);
    const preview = root.querySelector(imagePreviewColumnSelector);
    const fields = root.querySelector(profileFieldsColumnSelector);
    if (target === null || preview === null || fields === null) {
      return null;
    }
    const scrollMarginTop = Number.parseFloat(
      globalThis.getComputedStyle(preview).scrollMarginTop
    );
    return Math.max(
      0,
      globalThis.scrollY + fields.getBoundingClientRect().top - target.getBoundingClientRect().height - (Number.isFinite(scrollMarginTop) ? scrollMarginTop : 0)
    );
  };
  const openImage = async () => {
    var _a, _b;
    image.error = "";
    readPersistedPreview();
    root.classList.remove(imageClosingClass);
    image.closing = false;
    image.editing = true;
    await nextTick();
    (_a = root.querySelector(imageEditorTargetSelector)) == null ? void 0 : _a.scrollIntoView({
      behavior: globalThis.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth",
      block: "start"
    });
    const input = getFileInput();
    if (input !== null) {
      input.required = !image.hasImage;
    }
    await initializeCropper();
    (_b = getFileInput()) == null ? void 0 : _b.focus({ preventScroll: true });
  };
  const closeImage = () => {
    if (image.pending || image.closing) {
      return;
    }
    const scrollTop = getImageCloseScrollTop();
    destroyCropper();
    resetSelection();
    root.classList.add(imageClosingClass);
    image.closing = true;
    image.editing = false;
    if (scrollTop !== null) {
      globalThis.scrollTo({
        top: scrollTop,
        behavior: globalThis.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth"
      });
    }
  };
  const finishImageClose = () => {
    if (image.editing) {
      return;
    }
    image.closing = false;
    void nextTick().then(() => {
      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          var _a;
          root.classList.remove(imageClosingClass);
          if (image.editing) {
            return;
          }
          (_a = root.querySelector("[data-ie-open-image-view]")) == null ? void 0 : _a.focus({ preventScroll: true });
        });
      });
    });
  };
  const selectImage = async (event) => {
    var _a;
    const input = event.target;
    if (!(input instanceof HTMLInputElement)) {
      return;
    }
    image.error = "";
    destroyCropper();
    releaseUrl(selectedPreviewUrl);
    selectedPreviewUrl = null;
    selectedFile = ((_a = input.files) == null ? void 0 : _a[0]) ?? null;
    image.hasSelection = selectedFile !== null;
    image.selectedName = (selectedFile == null ? void 0 : selectedFile.name) ?? "";
    if (selectedFile === null) {
      image.previewUrl = persistedPreviewUrl ?? "";
    } else {
      selectedPreviewUrl = URL.createObjectURL(selectedFile);
      image.previewUrl = selectedPreviewUrl;
    }
    await nextTick();
    await initializeCropper();
  };
  const commitUploadedPreview = (file, previewUrl, alternative, title) => {
    if (persistedPreviewUrl !== previewUrl) {
      releaseUrl(persistedPreviewUrl);
    }
    if (selectedPreviewUrl === previewUrl) {
      selectedPreviewUrl = null;
    } else {
      releaseUrl(selectedPreviewUrl);
      selectedPreviewUrl = null;
    }
    persistedPreviewUrl = previewUrl;
    persistedAlternative = String(alternative ?? file.name);
    persistedTitle = String(title ?? file.name);
    image.previewUrl = previewUrl;
    getImagePreviews(root).forEach((preview) => {
      setImagePreviewUrl(
        preview,
        previewUrl,
        persistedAlternative,
        persistedTitle
      );
    });
  };
  const submitImage = async (event) => {
    const form = event.currentTarget instanceof HTMLFormElement ? event.currentTarget : event.target instanceof HTMLFormElement ? event.target : null;
    if (form === null || image.pending) {
      return;
    }
    const file = selectedFile;
    if (!image.hasSelection || file === null) {
      image.error = root.dataset.messageValidation ?? "";
      return;
    }
    if (!form.reportValidity()) {
      image.error = root.dataset.messageValidation ?? "";
      return;
    }
    image.pending = true;
    image.error = "";
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    let uploadPreviewUrl = selectedPreviewUrl;
    try {
      const uploadFile = image.cropperRequested ? await createCroppedImageFile(image.cropperEnabled ? cropper : null, file) : file;
      if (image.cropperRequested) {
        uploadPreviewUrl = URL.createObjectURL(uploadFile);
      }
      const input = getFileInput();
      if (input === null || input.name === "") {
        throw new Error("The image upload field has no name.");
      }
      const formData = new FormData(form);
      formData.set(input.name, uploadFile, uploadFile.name);
      const result = await requestJson(form.action, {
        method: "POST",
        body: formData
      });
      if (result.hasImage !== true || uploadPreviewUrl === null) {
        const error = new Error("The upload returned no profile image.");
        error.result = { message: root.dataset.messageImageUploadMissing ?? "" };
        throw error;
      }
      commitUploadedPreview(
        uploadFile,
        uploadPreviewUrl,
        result.imageAlternative,
        result.imageTitle
      );
      uploadPreviewUrl = null;
      image.hasImage = true;
      setImageState(root, true);
      image.pending = false;
      closeImage();
      showStatus(root, "success", root.dataset.messageImageUploaded ?? null);
    } catch (error) {
      if (uploadPreviewUrl !== null && uploadPreviewUrl !== selectedPreviewUrl) {
        releaseUrl(uploadPreviewUrl);
      }
      const result = error.result;
      image.error = (result == null ? void 0 : result.error) === "image_upload_missing" ? root.dataset.messageImageUploadMissing ?? "" : (result == null ? void 0 : result.message) ?? root.dataset.messageErrorMessage ?? "";
    } finally {
      image.pending = false;
    }
  };
  const deleteImage = async () => {
    var _a;
    const profile = getProfileUid(root);
    const endpoint = root.dataset.deleteImageUrl;
    if (image.pending || !image.hasImage || profile === null || endpoint === void 0) {
      return;
    }
    image.pending = true;
    image.error = "";
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    try {
      await requestJson(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile, data: {} })
      });
      const placeholderUrl = root.dataset.placeholderImageUrl;
      if (placeholderUrl !== void 0) {
        releaseUrl(selectedPreviewUrl);
        releaseUrl(persistedPreviewUrl);
        selectedPreviewUrl = null;
        persistedPreviewUrl = placeholderUrl;
        image.previewUrl = placeholderUrl;
        getImagePreviews(root).forEach((preview) => {
          setImagePreviewUrl(
            preview,
            placeholderUrl,
            root.dataset.placeholderImageAlt ?? ""
          );
        });
      }
      image.hasImage = false;
      setImageState(root, false);
      image.pending = false;
      closeImage();
      showStatus(root, "success", root.dataset.messageImageDeleted ?? null);
    } catch (error) {
      image.error = ((_a = error.result) == null ? void 0 : _a.message) ?? root.dataset.messageErrorMessage ?? "";
    } finally {
      image.pending = false;
    }
  };
  globalThis.addEventListener(
    "pagehide",
    () => {
      destroyCropper();
      releaseUrl(selectedPreviewUrl);
      releaseUrl(persistedPreviewUrl);
    },
    { once: true }
  );
  return {
    cropperSource,
    cropperStage,
    fileInput,
    image,
    openImage,
    closeImage,
    finishImageClose,
    selectImage,
    submitImage,
    deleteImage
  };
};
export {
  configureCropperSelection,
  createCroppedImageFile,
  createImageEditing,
  getImagePreview,
  getImagePreviews,
  parseImageRatio,
  setImagePreviewUrl,
  setImageState
};
