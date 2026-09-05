const stickyImageSelector = "[data-ie-sticky-image]";
const pageHeaderSelector = "#page-header.navbar-fixed-top";

export const updateStickyImageOffset = (
  stickyImage: HTMLElement,
  pageHeader: HTMLElement,
): void => {
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

export const initializeStickyImageOffset = (root: HTMLElement): void => {
  const stickyImage = root.querySelector<HTMLElement>(stickyImageSelector);
  const pageHeader = document.querySelector<HTMLElement>(pageHeaderSelector);
  if (stickyImage === null) {
    return;
  }
  if (pageHeader === null) {
    stickyImage.style.removeProperty("top");
    return;
  }
  const updateOffset = (): void =>
    updateStickyImageOffset(stickyImage, pageHeader);
  updateOffset();
  if (typeof ResizeObserver === "function") {
    const resizeObserver = new ResizeObserver(updateOffset);
    resizeObserver.observe(pageHeader, { box: "border-box" });
    globalThis.addEventListener("pagehide", (): void => resizeObserver.disconnect(), {
      once: true,
    });
    return;
  }
  globalThis.addEventListener("resize", updateOffset);
  globalThis.addEventListener(
    "pagehide",
    (): void => globalThis.removeEventListener("resize", updateOffset),
    { once: true },
  );
};
