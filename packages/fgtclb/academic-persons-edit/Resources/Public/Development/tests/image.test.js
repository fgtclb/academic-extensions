import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import {
  createCroppedImageFile,
  getImagePreview,
  getImagePreviews,
  initializeImageEditing,
  parseImageRatio,
  setImagePreviewUrl,
  setImageState,
} from "../../JavaScript/frontend/profile/image.js";

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

const createRoot = () => {
  const root = document.createElement("section");
  root.dataset.profileUid = "9";
  root.dataset.hasImage = "0";
  root.dataset.deleteImageUrl = "/delete-image";
  root.dataset.placeholderImageUrl = "/placeholder.svg";
  root.dataset.placeholderImageAlt = "Placeholder";
  root.dataset.messageValidation = "Choose an image";
  root.dataset.messageImageUploadMissing = "Upload returned no image";
  root.dataset.messageImageUploaded = "Image uploaded";
  root.dataset.messageImageDeleted = "Image deleted";
  root.dataset.messageSaving = "Saving";
  root.dataset.messageErrorTitle = "Error";
  root.dataset.messageErrorMessage = "Failed";
  root.dataset.messageSuccessTitle = "Success";
  root.dataset.messageSuccessMessage = "Saved";
  root.dataset.messageInfoTitle = "Info";
  root.dataset.messageInfoMessage = "Working";
  root.innerHTML = `
    <button type="button" data-ie-open-image-modal>Edit</button>
    <picture data-ie-image-preview>
      <source srcset="/old.webp"><img src="/old.jpg" alt="Old" title="Old title">
    </picture>
    <button type="button" class="d-none" data-ie-delete-image>
      Delete <span class="d-none" data-ie-action-spinner></span>
    </button>
    <p class="d-none" data-ie-image-delete-hint>Delete hint</p>
    <div data-ie-image-modal>
      <form class="academic-persons-inline-edit__image-form" action="/upload-image">
        <input type="file" name="image">
        <button type="submit" data-ie-upload-image>
          Upload <span class="d-none" data-ie-action-spinner></span>
        </button>
        <div class="d-none" data-ie-image-error></div>
      </form>
      <picture data-ie-image-modal-preview>
        <source srcset="/old.webp"><img src="/old.jpg" alt="Old" title="Old title">
      </picture>
      <button type="button" data-ie-close-image-modal>Close</button>
    </div>
    <div class="d-none" data-ie-status-toast>
      <span class="status-title"></span><span class="status-message"></span>
    </div>
  `;
  document.body.append(root);
  const fileInput = root.querySelector('input[type="file"]');
  let selectedFiles = [];
  Object.defineProperty(fileInput, "files", {
    configurable: true,
    get: () => selectedFiles,
  });
  return {
    root,
    form: root.querySelector("form"),
    modal: root.querySelector("[data-ie-image-modal]"),
    openButton: root.querySelector("[data-ie-open-image-modal]"),
    uploadButton: root.querySelector("[data-ie-upload-image]"),
    deleteButton: root.querySelector("[data-ie-delete-image]"),
    closeButton: root.querySelector("[data-ie-close-image-modal]"),
    fileInput,
    pagePreview: root.querySelector("[data-ie-image-preview]"),
    modalPreview: root.querySelector("[data-ie-image-modal-preview]"),
    error: root.querySelector("[data-ie-image-error]"),
    setFiles: (files) => {
      selectedFiles = files;
    },
  };
};

const response = (result, ok = true) => ({
  ok,
  json: jest.fn().mockResolvedValue(result),
});

describe("profile/image", () => {
  let modalInstance;

  beforeEach(() => {
    modalInstance = { hide: jest.fn() };
    globalThis.bootstrap = {
      Modal: { getOrCreateInstance: jest.fn(() => modalInstance) },
    };
    globalThis.fetch = jest.fn();
    Object.defineProperty(URL, "createObjectURL", {
      configurable: true,
      writable: true,
      value: jest.fn(() => "blob:preview"),
    });
    Object.defineProperty(URL, "revokeObjectURL", {
      configurable: true,
      writable: true,
      value: jest.fn(),
    });
  });

  test("parses configured cropper ratios and rejects unusable values", () => {
    expect(parseImageRatio("1x1")).toBe(1);
    expect(parseImageRatio("9:16")).toBeCloseTo(9 / 16);
    expect(parseImageRatio("3 / 2")).toBe(1.5);
    expect(parseImageRatio("1.25")).toBe(1.25);
    expect(parseImageRatio(2)).toBe(2);
    expect(parseImageRatio("")).toBeNull();
    expect(parseImageRatio("0")).toBeNull();
    expect(parseImageRatio("1x0")).toBeNull();
    expect(parseImageRatio("invalid")).toBeNull();
  });

  test("creates a cropped image file with a safe output format", async () => {
    const canvas = document.createElement("canvas");
    canvas.width = 1600;
    canvas.height = 900;
    canvas.toBlob = jest.fn((callback, mimeType) => {
      callback(new Blob(["cropped"], { type: mimeType }));
    });
    const selection = {
      width: 800,
      height: 450,
      $toCanvas: jest.fn().mockResolvedValue(canvas),
    };
    const cropper = {
      getCropperSelection: jest.fn(() => selection),
      getCropperImage: jest.fn(() => ({ $getTransform: () => [0.5, 0, 0, 0.5, 0, 0] })),
    };
    const source = new File(["image"], "portrait.gif", { type: "image/gif" });

    const cropped = await createCroppedImageFile(cropper, source);

    expect(selection.$toCanvas).toHaveBeenCalledWith({ width: 1600 });
    expect(canvas.toBlob).toHaveBeenCalledWith(expect.any(Function), "image/png", undefined);
    expect(cropped).toBeInstanceOf(File);
    expect(cropped.name).toBe("portrait.png");
    expect(cropped.type).toBe("image/png");
  });

  test("rejects cropping when CropperJS has no usable selection", async () => {
    const source = new File(["image"], "portrait.png", { type: "image/png" });

    await expect(createCroppedImageFile({ getCropperSelection: () => null }, source))
      .rejects.toThrow("The image crop is unavailable.");
  });

  test("finds previews, updates image attributes and toggles image state", () => {
    const { root, pagePreview } = createRoot();
    expect(getImagePreviews(root)).toHaveLength(2);
    expect(getImagePreview(root, "[data-ie-image-preview]")).toBe(pagePreview);
    expect(getImagePreview(root, ".missing")).toBeNull();

    setImagePreviewUrl(pagePreview, "/new.jpg", "New alt", "New title");
    const image = pagePreview.querySelector("img");
    expect(image.src).toBe("https://www.example.test/new.jpg");
    expect(image.alt).toBe("New alt");
    expect(image.title).toBe("New title");
    expect(image.hasAttribute("srcset")).toBe(false);
    expect(pagePreview.querySelector("source").hasAttribute("srcset")).toBe(false);

    expect(() => setImagePreviewUrl(document.createElement("div"), "/ignored"))
      .not.toThrow();
    setImageState(root, true);
    expect(root.dataset.hasImage).toBe("1");
    expect(root.querySelector("[data-ie-delete-image]").classList.contains("d-none"))
      .toBe(false);
    setImageState(root, false);
    expect(root.dataset.hasImage).toBe("0");
  });

  test("ignores incomplete markup or a missing Bootstrap modal", () => {
    expect(() => initializeImageEditing(document.createElement("section"))).not.toThrow();
    const { root } = createRoot();
    delete globalThis.bootstrap;
    expect(() => initializeImageEditing(root)).not.toThrow();
  });

  test("previews, uploads and releases a selected image", async () => {
    const fixture = createRoot();
    const {
      root,
      form,
      modal,
      openButton,
      uploadButton,
      closeButton,
      fileInput,
      pagePreview,
      modalPreview,
      setFiles,
    } = fixture;
    const file = new File(["image"], "portrait.png", { type: "image/png" });
    jest.spyOn(form, "reportValidity").mockReturnValue(true);
    globalThis.fetch.mockResolvedValue(response({ success: true, hasImage: true }));
    initializeImageEditing(root);

    expect(uploadButton.disabled).toBe(true);
    expect(fixture.deleteButton.disabled).toBe(true);
    modal.dispatchEvent(new Event("show.bs.modal"));
    expect(modalPreview.querySelector("img").src).toBe(pagePreview.querySelector("img").src);

    setFiles([file]);
    fileInput.dispatchEvent(new Event("change", { bubbles: true }));
    expect(URL.createObjectURL).toHaveBeenCalledWith(file);
    expect(modalPreview.querySelector("img").src).toBe("blob:preview");
    expect(uploadButton.disabled).toBe(false);

    form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
    expect(modal.getAttribute("aria-busy")).toBe("true");
    expect(fileInput.disabled).toBe(true);
    expect(closeButton.disabled).toBe(true);
    const hideWhilePending = new Event("hide.bs.modal", { cancelable: true });
    modal.dispatchEvent(hideWhilePending);
    expect(hideWhilePending.defaultPrevented).toBe(true);
    await flushPromises();

    expect(globalThis.fetch).toHaveBeenCalledWith(
      "https://www.example.test/upload-image",
      expect.objectContaining({
        credentials: "same-origin",
        method: "POST",
        body: expect.any(FormData),
      }),
    );
    expect(pagePreview.querySelector("img").src).toBe("blob:preview");
    expect(pagePreview.querySelector("img").alt).toBe("portrait.png");
    expect(root.dataset.hasImage).toBe("1");
    expect(modal.hasAttribute("aria-busy")).toBe(false);
    expect(modalInstance.hide).toHaveBeenCalledTimes(1);

    modal.dispatchEvent(new Event("hidden.bs.modal"));
    expect(document.activeElement).toBe(openButton);
    globalThis.dispatchEvent(new Event("pagehide"));
    expect(URL.revokeObjectURL).toHaveBeenCalledWith("blob:preview");
  });

  test("validates uploads and displays upload failures", async () => {
    const { root, form, fileInput, error, setFiles } = createRoot();
    jest.spyOn(form, "reportValidity").mockReturnValue(false);
    initializeImageEditing(root);
    form.dispatchEvent(new Event("submit", { cancelable: true }));
    expect(error.textContent).toBe("Choose an image");
    expect(fileInput.classList.contains("is-invalid")).toBe(true);

    form.reportValidity.mockReturnValue(true);
    setFiles([]);
    form.dispatchEvent(new Event("submit", { cancelable: true }));
    expect(error.textContent).toBe("Choose an image");

    const file = new File(["image"], "portrait.png", { type: "image/png" });
    setFiles([file]);
    fileInput.dispatchEvent(new Event("change"));
    globalThis.fetch.mockResolvedValueOnce(response({ success: true, hasImage: false }));
    form.dispatchEvent(new Event("submit", { cancelable: true }));
    await flushPromises();
    expect(error.textContent).toBe("Upload returned no image");
    expect(modalInstance.hide).not.toHaveBeenCalled();

    globalThis.fetch.mockResolvedValueOnce(response({
      success: false,
      error: "image_upload_missing",
      message: "Server detail",
    }, false));
    form.dispatchEvent(new Event("submit", { cancelable: true }));
    await flushPromises();
    expect(error.textContent).toBe("Upload returned no image");
  });

  test("deletes an image, applies the placeholder and reports delete errors", async () => {
    const { root, deleteButton, pagePreview, error } = createRoot();
    root.dataset.hasImage = "1";
    globalThis.fetch
      .mockResolvedValueOnce(response({ success: true }))
      .mockResolvedValueOnce(response({ success: false, message: "Cannot delete" }, false));
    initializeImageEditing(root);

    deleteButton.click();
    await flushPromises();
    expect(globalThis.fetch).toHaveBeenNthCalledWith(1, "/delete-image", {
      credentials: "same-origin",
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ profile: 9, data: {} }),
    });
    expect(root.dataset.hasImage).toBe("0");
    expect(pagePreview.querySelector("img").src)
      .toBe("https://www.example.test/placeholder.svg");
    expect(pagePreview.querySelector("img").alt).toBe("Placeholder");
    expect(modalInstance.hide).toHaveBeenCalledTimes(1);

    root.dataset.hasImage = "1";
    deleteButton.disabled = false;
    deleteButton.click();
    await flushPromises();
    expect(error.textContent).toBe("Cannot delete");
    expect(modalInstance.hide).toHaveBeenCalledTimes(1);

    delete root.dataset.deleteImageUrl;
    deleteButton.click();
    await flushPromises();
    expect(globalThis.fetch).toHaveBeenCalledTimes(2);
  });
});
