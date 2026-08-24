const stickyImageSelector = "[data-ie-sticky-image]";
const pageHeaderSelector = "#page-header";

export const initializeStickyImageOffset = (root) => {
  const stickyImage = root.querySelector(stickyImageSelector);
  const pageHeader = document.querySelector(pageHeaderSelector);
  if (!(stickyImage instanceof HTMLElement)) {
    return;
  }
  if (!(pageHeader instanceof HTMLElement)) {
    stickyImage.style.removeProperty("top");
    return;
  }

  const updateOffset = () => updateStickyImageOffset(stickyImage, pageHeader);

  updateOffset();
  const HeaderResizeObserver = globalThis.ResizeObserver;
  if (typeof HeaderResizeObserver === "function") {
    const resizeObserver = new HeaderResizeObserver(updateOffset);
    resizeObserver.observe(pageHeader, { box: "border-box" });
    globalThis.addEventListener("pagehide", () => resizeObserver.disconnect(), {
      once: true,
    });
    return;
  }

  globalThis.addEventListener("resize", updateOffset);
  globalThis.addEventListener(
    "pagehide",
    () => globalThis.removeEventListener("resize", updateOffset),
    { once: true },
  );
};

export const updateStickyImageOffset = (stickyImage, pageHeader) => {
  const headerOuterHeight = Math.max(
    0,
    Math.ceil(pageHeader.getBoundingClientRect().height),
  );
  stickyImage.style.setProperty(
    "top",
    `${headerOuterHeight + 10}px`,
    "important",
  );
};
