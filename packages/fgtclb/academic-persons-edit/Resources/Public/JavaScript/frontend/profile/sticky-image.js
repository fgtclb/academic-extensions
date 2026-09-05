/* Generated from Resources/Private/TypeScript — do not edit. */
const stickyImageSelector = "[data-ie-sticky-image]";
const pageHeaderSelector = "#page-header.navbar-fixed-top";
const updateStickyImageOffset = (stickyImage, pageHeader) => {
  const headerOuterHeight = Math.max(
    0,
    Math.ceil(pageHeader.getBoundingClientRect().height)
  );
  stickyImage.style.setProperty(
    "top",
    `${headerOuterHeight + 10}px`,
    "important"
  );
};
const initializeStickyImageOffset = (root) => {
  const stickyImage = root.querySelector(stickyImageSelector);
  const pageHeader = document.querySelector(pageHeaderSelector);
  if (stickyImage === null) {
    return;
  }
  if (pageHeader === null) {
    stickyImage.style.removeProperty("top");
    return;
  }
  const updateOffset = () => updateStickyImageOffset(stickyImage, pageHeader);
  updateOffset();
  if (typeof ResizeObserver === "function") {
    const resizeObserver = new ResizeObserver(updateOffset);
    resizeObserver.observe(pageHeader, { box: "border-box" });
    globalThis.addEventListener("pagehide", () => resizeObserver.disconnect(), {
      once: true
    });
    return;
  }
  globalThis.addEventListener("resize", updateOffset);
  globalThis.addEventListener(
    "pagehide",
    () => globalThis.removeEventListener("resize", updateOffset),
    { once: true }
  );
};
export {
  initializeStickyImageOffset,
  updateStickyImageOffset
};
