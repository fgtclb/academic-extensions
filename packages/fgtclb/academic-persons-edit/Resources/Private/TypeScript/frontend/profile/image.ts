import Cropper, {
  type CropperSelectionElement,
} from "@fgtclb/academic-persons-edit/cropper";
import {
  nextTick,
  reactive,
  ref,
  type Ref,
} from "@fgtclb/academic-persons-edit/frontend/vue.js";
import {
  requestJson,
  showStatus,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  toEditingContext,
  type EditingTarget,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
interface ImageState {
  closing: boolean;
  confirmingDelete: boolean;
  cropperEnabled: boolean;
  cropperReady: boolean;
  cropperRequested: boolean;
  editing: boolean;
  error: string;
  hasImage: boolean;
  hasSelection: boolean;
  pending: boolean;
  previewUrl: string;
  selectedName: string;
}

interface RequestError extends Error {
  result?: {
    error?: string;
    message?: string;
  };
}

export interface ImageEditingController {
  cropperSource: Ref<HTMLImageElement | null>;
  cropperStage: Ref<HTMLElement | null>;
  fileInput: Ref<HTMLInputElement | null>;
  image: ImageState;
  openImage(): Promise<void>;
  closeImage(): void;
  requestDeleteImage(): void;
  cancelDeleteImage(): void;
  finishImageClose(): void;
  selectImage(event: Event): Promise<void>;
  submitImage(event: Event): Promise<void>;
  deleteImage(): Promise<void>;
}

const maximumCroppedImageWidth = 2400;
const imageClosingClass = "is-image-closing";
const imageEditorTargetSelector = "[data-pe-image-editor-target]";
const imagePreviewColumnSelector = "[data-pe-image-preview-column]";
const profileFieldsColumnSelector =
  ".academic-persons-profile-editing__profile-fields-column";
const supportedOutputMimeTypes = new Set([
  "image/jpeg",
  "image/png",
  "image/webp",
]);

const parseImageRatio = (value: unknown): number | null => {
  const normalized = String(value ?? "").trim();
  const ratioMatch = /^(\d+(?:\.\d+)?)\s*(?:x|:|\/)\s*(\d+(?:\.\d+)?)$/i.exec(
    normalized,
  );
  if (ratioMatch !== null) {
    const width = Number.parseFloat(ratioMatch[1] ?? "");
    const height = Number.parseFloat(ratioMatch[2] ?? "");
    const ratio = width / height;
    return Number.isFinite(ratio) && ratio > 0 ? ratio : null;
  }
  const ratio = Number(normalized);
  return Number.isFinite(ratio) && ratio > 0 ? ratio : null;
};

const configureCropperSelection = (
  selection: CropperSelectionElement,
  ratio: number,
): void => {
  selection.aspectRatio = ratio;
  selection.initialAspectRatio = ratio;
  selection.initialCoverage = 0.7;
};

const getImagePreviews = (root: HTMLElement): HTMLElement[] =>
  Array.from(
    root.querySelectorAll<HTMLElement>(
      "[data-pe-image-preview], [data-pe-image-view-preview]",
    ),
  );

const getImagePreview = (
  root: HTMLElement,
  selector: string,
): HTMLElement | null => root.querySelector<HTMLElement>(selector);

const setImagePreviewUrl = (
  preview: HTMLElement,
  url: string,
  alt = "",
  title = "",
): void => {
  const images = preview.querySelectorAll<HTMLImageElement>("img");
  if (images.length === 0) {
    return;
  }
  preview
    .querySelectorAll<HTMLSourceElement>("source")
    .forEach((source): void => source.removeAttribute("srcset"));
  images.forEach((image): void => {
    image.removeAttribute("srcset");
    image.src = url;
    image.alt = alt;
    image.title = title;
  });
};

const setImageState = (root: HTMLElement, hasImage: boolean): void => {
  root.dataset.hasImage = hasImage ? "1" : "0";
  const input = root.querySelector<HTMLInputElement>('input[type="file"]');
  if (input !== null) {
    input.required = !hasImage;
  }
};

const getCroppedImageWidth = (
  cropper: Cropper,
  selection: CropperSelectionElement,
): number => {
  const transform = cropper.getCropperImage()?.$getTransform?.() ?? null;
  const scale = Array.isArray(transform)
    ? Math.hypot(Number(transform[0]), Number(transform[1]))
    : Number.NaN;
  const sourceWidth =
    Number.isFinite(scale) && scale > 0
      ? Math.round(selection.width / scale)
      : Math.round(selection.width);
  return Math.min(maximumCroppedImageWidth, Math.max(1, sourceWidth));
};

const setInitialCropperSelection = (
  selection: CropperSelectionElement,
  ratio: number,
): boolean => {
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

const canvasToBlob = (
  canvas: HTMLCanvasElement,
  mimeType: string,
): Promise<Blob> =>
  new Promise((resolve, reject): void => {
    canvas.toBlob(
      (blob): void => {
        if (blob !== null) {
          resolve(blob);
          return;
        }
        reject(new Error("The cropped image could not be encoded."));
      },
      mimeType,
      mimeType === "image/png" ? undefined : 0.92,
    );
  });

const getCroppedImageFileName = (file: File, mimeType: string): string => {
  if (mimeType === file.type) {
    return file.name;
  }
  const extension = mimeType === "image/jpeg" ? "jpg" : mimeType.split("/")[1];
  const basename = file.name.replace(/\.[^.]+$/, "");
  return `${basename || "profile-image"}.${extension || "png"}`;
};

const createCroppedImageFile = async (
  cropper: Cropper | null,
  file: File,
): Promise<File> => {
  const selection = cropper?.getCropperSelection();
  if (
    cropper === null ||
    selection === null ||
    selection === undefined ||
    selection.width <= 0 ||
    selection.height <= 0
  ) {
    throw new Error("The image crop is unavailable.");
  }
  const canvas = await selection.$toCanvas({
    width: getCroppedImageWidth(cropper, selection),
  });
  if (canvas.width <= 0 || canvas.height <= 0) {
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

export const createImageEditing = (
  editingTarget: EditingTarget,
): ImageEditingController => {
  const context = toEditingContext(editingTarget);
  const root = context.root;
  const cropperSource = ref<HTMLImageElement | null>(null);
  const cropperStage = ref<HTMLElement | null>(null);
  const fileInput = ref<HTMLInputElement | null>(null);
  const cropperRequested = context.image.renderType === "cropper";
  const cropperRatio = parseImageRatio(context.image.cropperRatio);
  const image = reactive<ImageState>({
    closing: false,
    confirmingDelete: false,
    cropperEnabled: cropperRequested && cropperRatio !== null,
    cropperReady: false,
    cropperRequested,
    editing: false,
    error: "",
    hasImage: context.image.hasImage,
    hasSelection: false,
    pending: false,
    previewUrl: "",
    selectedName: "",
  });
  let cropper: Cropper | null = null;
  let selectedFile: File | null = null;
  let selectedPreviewUrl: string | null = null;
  let persistedPreviewUrl: string | null = null;
  let persistedAlternative = "";
  let persistedTitle = "";

  const getFileInput = (): HTMLInputElement | null =>
    fileInput.value ??
    root.querySelector<HTMLInputElement>(
      '[data-pe-image-view-container] input[type="file"]',
    );

  const releaseUrl = (url: string | null): void => {
    if (url !== null && url.startsWith("blob:")) {
      URL.revokeObjectURL(url);
    }
  };

  const destroyCropper = (): void => {
    cropper?.destroy();
    cropper = null;
    image.cropperReady = false;
  };

  const initializeCropper = async (): Promise<void> => {
    destroyCropper();
    if (
      !image.cropperRequested ||
      !image.hasSelection ||
      image.previewUrl === ""
    ) {
      return;
    }
    if (
      !image.cropperEnabled ||
      cropperRatio === null ||
      cropperSource.value === null ||
      cropperStage.value === null
    ) {
      image.error = context.messages.errorMessage ?? "";
      return;
    }
    try {
      cropper = new Cropper(cropperSource.value, {
        container: cropperStage.value,
      });
      const selection = cropper.getCropperSelection();
      const cropperImage = cropper.getCropperImage();
      if (
        selection === null ||
        cropperImage === null ||
        cropperImage.$ready === undefined
      ) {
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
        image.error = context.messages.errorMessage ?? "";
      }
    } catch {
      destroyCropper();
      image.error = context.messages.errorMessage ?? "";
    }
  };

  const readPersistedPreview = (): void => {
    const preview = getImagePreview(root, "[data-pe-image-preview]");
    const previewImage = preview?.querySelector<HTMLImageElement>("img");
    persistedPreviewUrl = previewImage?.currentSrc || previewImage?.src || null;
    persistedAlternative = previewImage?.alt ?? "";
    persistedTitle = previewImage?.title ?? "";
    image.previewUrl = persistedPreviewUrl ?? "";
  };

  const resetSelection = (): void => {
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

  const getImageCloseScrollTop = (): number | null => {
    const target = root.querySelector<HTMLElement>(imageEditorTargetSelector);
    const preview = root.querySelector<HTMLElement>(imagePreviewColumnSelector);
    const fields = root.querySelector<HTMLElement>(profileFieldsColumnSelector);
    if (target === null || preview === null || fields === null) {
      return null;
    }
    const scrollMarginTop = Number.parseFloat(
      globalThis.getComputedStyle(preview).scrollMarginTop,
    );
    return Math.max(
      0,
      globalThis.scrollY +
        fields.getBoundingClientRect().top -
        target.getBoundingClientRect().height -
        (Number.isFinite(scrollMarginTop) ? scrollMarginTop : 0),
    );
  };

  const openImage = async (): Promise<void> => {
    image.error = "";
    readPersistedPreview();
    root.classList.remove(imageClosingClass);
    image.closing = false;
    image.editing = true;
    await nextTick();
    root.querySelector<HTMLElement>(imageEditorTargetSelector)?.scrollIntoView({
      behavior: globalThis.matchMedia("(prefers-reduced-motion: reduce)").matches
        ? "auto"
        : "smooth",
      block: "start",
    });
    const input = getFileInput();
    if (input !== null) {
      input.required = !image.hasImage;
    }
    await initializeCropper();
    getFileInput()?.focus({ preventScroll: true });
  };

  const closeImage = (): void => {
    image.confirmingDelete = false;
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
        behavior: globalThis.matchMedia("(prefers-reduced-motion: reduce)")
          .matches
          ? "auto"
          : "smooth",
      });
    }
  };

  const finishImageClose = (): void => {
    if (image.editing) {
      return;
    }
    image.closing = false;
    void nextTick().then((): void => {
      requestAnimationFrame((): void => {
        requestAnimationFrame((): void => {
          root.classList.remove(imageClosingClass);
          if (image.editing) {
            return;
          }
          root
            .querySelector<HTMLButtonElement>("[data-pe-open-image-view]")
            ?.focus({ preventScroll: true });
        });
      });
    });
  };

  const selectImage = async (event: Event): Promise<void> => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement)) {
      return;
    }
    image.error = "";
    destroyCropper();
    releaseUrl(selectedPreviewUrl);
    selectedPreviewUrl = null;
    selectedFile = input.files?.[0] ?? null;
    image.hasSelection = selectedFile !== null;
    image.selectedName = selectedFile?.name ?? "";
    if (selectedFile === null) {
      image.previewUrl = persistedPreviewUrl ?? "";
    } else {
      selectedPreviewUrl = URL.createObjectURL(selectedFile);
      image.previewUrl = selectedPreviewUrl;
    }
    await nextTick();
    await initializeCropper();
  };

  const commitUploadedPreview = (
    file: File,
    previewUrl: string,
    alternative: unknown,
    title: unknown,
  ): void => {
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
    getImagePreviews(root).forEach((preview): void => {
      setImagePreviewUrl(
        preview,
        previewUrl,
        persistedAlternative,
        persistedTitle,
      );
    });
  };

  const submitImage = async (event: Event): Promise<void> => {
    const form = event.currentTarget instanceof HTMLFormElement
      ? event.currentTarget
      : event.target instanceof HTMLFormElement
        ? event.target
        : null;
    if (form === null || image.pending) {
      return;
    }
    const file = selectedFile;
    if (!image.hasSelection || file === null) {
      image.error = context.messages.validation ?? "";
      return;
    }
    if (!form.reportValidity()) {
      image.error = context.messages.validation ?? "";
      return;
    }
    image.pending = true;
    image.error = "";
    showStatus(context, "info", context.messages.saving ?? null);
    let uploadPreviewUrl: string | null = selectedPreviewUrl;
    try {
      const uploadFile = image.cropperRequested
        ? await createCroppedImageFile(image.cropperEnabled ? cropper : null, file)
        : file;
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
        body: formData,
      });
      if (result.hasImage !== true || uploadPreviewUrl === null) {
        const error = new Error("The upload returned no profile image.") as RequestError;
        error.result = { message: context.messages.imageUploadMissing ?? "" };
        throw error;
      }
      commitUploadedPreview(
        uploadFile,
        uploadPreviewUrl,
        result.imageAlternative,
        result.imageTitle,
      );
      uploadPreviewUrl = null;
      image.hasImage = true;
      setImageState(root, true);
      image.pending = false;
      closeImage();
      showStatus(context, "success", context.messages.imageUploaded ?? null);
    } catch (error) {
      if (uploadPreviewUrl !== null && uploadPreviewUrl !== selectedPreviewUrl) {
        releaseUrl(uploadPreviewUrl);
      }
      const result = (error as RequestError).result;
      image.error =
        result?.error === "image_upload_missing"
          ? (context.messages.imageUploadMissing ?? "")
          : (result?.message ?? context.messages.errorMessage ?? "");
    } finally {
      image.pending = false;
    }
  };

  // Deleting the image drops the FAL relation and, with the last reference, the
  // file. The documents and contacts ask before they delete; this asks too.
  const requestDeleteImage = (): void => {
    if (image.pending || !image.hasImage) {
      return;
    }
    image.error = "";
    image.confirmingDelete = true;
  };

  const cancelDeleteImage = (): void => {
    image.confirmingDelete = false;
  };

  const deleteImage = async (): Promise<void> => {
    const profile = context.profileUid;
    const endpoint = context.urls.deleteImage;
    if (
      image.pending
      || !image.hasImage
      || !image.confirmingDelete
      || profile === null
      || endpoint === undefined
    ) {
      return;
    }
    image.confirmingDelete = false;
    image.pending = true;
    image.error = "";
    showStatus(context, "info", context.messages.saving ?? null);
    try {
      await requestJson(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile, data: {} }),
      });
      const placeholderUrl = context.image.placeholderUrl;
      if (placeholderUrl !== undefined) {
        releaseUrl(selectedPreviewUrl);
        releaseUrl(persistedPreviewUrl);
        selectedPreviewUrl = null;
        persistedPreviewUrl = placeholderUrl;
        image.previewUrl = placeholderUrl;
        getImagePreviews(root).forEach((preview): void => {
          setImagePreviewUrl(
            preview,
            placeholderUrl,
            context.image.placeholderAlt ?? "",
          );
        });
      }
      image.hasImage = false;
      setImageState(root, false);
      image.pending = false;
      closeImage();
      showStatus(context, "success", context.messages.imageDeleted ?? null);
    } catch (error) {
      image.error =
        (error as RequestError).result?.message ??
        context.messages.errorMessage ??
        "";
    } finally {
      image.pending = false;
    }
  };

  globalThis.addEventListener(
    "pagehide",
    (): void => {
      destroyCropper();
      releaseUrl(selectedPreviewUrl);
      releaseUrl(persistedPreviewUrl);
    },
    { once: true },
  );

  return {
    cropperSource,
    cropperStage,
    fileInput,
    image,
    openImage,
    closeImage,
    requestDeleteImage,
    cancelDeleteImage,
    finishImageClose,
    selectImage,
    submitImage,
    deleteImage,
  };
};
