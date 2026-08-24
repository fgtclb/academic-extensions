import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import {
  initializeStickyImageOffset,
  updateStickyImageOffset,
} from "../../JavaScript/frontend/profile/sticky-image.js";

const createElements = () => {
  const header = document.createElement("header");
  header.id = "page-header";
  const root = document.createElement("section");
  root.innerHTML = '<aside data-ie-sticky-image></aside>';
  document.body.append(header, root);
  return { header, root, sticky: root.querySelector("aside") };
};

describe("profile/sticky-image", () => {
  beforeEach(() => {
    delete globalThis.ResizeObserver;
  });

  test("computes a non-negative rounded header offset plus the visual gap", () => {
    const { header, sticky } = createElements();
    jest.spyOn(header, "getBoundingClientRect").mockReturnValue({ height: 24.2 });
    updateStickyImageOffset(sticky, header);
    expect(sticky.style.getPropertyValue("top")).toBe("35px");
    expect(sticky.style.getPropertyPriority("top")).toBe("important");

    header.getBoundingClientRect.mockReturnValue({ height: -10 });
    updateStickyImageOffset(sticky, header);
    expect(sticky.style.getPropertyValue("top")).toBe("10px");
  });

  test("does nothing without a sticky image and clears stale offsets without a header", () => {
    const root = document.createElement("section");
    expect(() => initializeStickyImageOffset(root)).not.toThrow();

    root.innerHTML = '<aside data-ie-sticky-image style="top: 99px"></aside>';
    initializeStickyImageOffset(root);
    expect(root.querySelector("aside").style.getPropertyValue("top")).toBe("");
  });

  test("tracks header size through ResizeObserver and disconnects on pagehide", () => {
    const { header, root, sticky } = createElements();
    jest.spyOn(header, "getBoundingClientRect").mockReturnValue({ height: 30 });
    let callback;
    const observe = jest.fn();
    const disconnect = jest.fn();
    globalThis.ResizeObserver = jest.fn((listener) => {
      callback = listener;
      return { observe, disconnect };
    });

    initializeStickyImageOffset(root);
    expect(sticky.style.top).toBe("40px");
    expect(observe).toHaveBeenCalledWith(header, { box: "border-box" });

    header.getBoundingClientRect.mockReturnValue({ height: 40.1 });
    callback();
    expect(sticky.style.top).toBe("51px");
    globalThis.dispatchEvent(new Event("pagehide"));
    expect(disconnect).toHaveBeenCalledTimes(1);
  });

  test("falls back to resize events and removes the listener on pagehide", () => {
    const { header, root, sticky } = createElements();
    jest.spyOn(header, "getBoundingClientRect").mockReturnValue({ height: 12 });
    initializeStickyImageOffset(root);
    expect(sticky.style.top).toBe("22px");

    header.getBoundingClientRect.mockReturnValue({ height: 18 });
    globalThis.dispatchEvent(new Event("resize"));
    expect(sticky.style.top).toBe("28px");
    globalThis.dispatchEvent(new Event("pagehide"));
    header.getBoundingClientRect.mockReturnValue({ height: 50 });
    globalThis.dispatchEvent(new Event("resize"));
    expect(sticky.style.top).toBe("28px");
  });
});
