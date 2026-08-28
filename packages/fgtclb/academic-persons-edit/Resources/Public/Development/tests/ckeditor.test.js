import {
  afterEach,
  beforeEach,
  describe,
  expect,
  jest,
  test,
} from "@jest/globals";

const importModule = async () => {
  jest.resetModules();
  return import("../../JavaScript/frontend/rich-text.js");
};

describe("frontend/rich-text", () => {
  beforeEach(() => {
    jest.useFakeTimers();
    delete window.CKEDITOR;
  });

  afterEach(() => {
    jest.useRealTimers();
  });

  test("exposes the editor configuration and resolves the global editor", async () => {
    const module = await importModule();
    expect(module.editorConfig).toMatchObject({
      language: "en",
      height: 200,
      versionCheck: false,
      format_tags: "p",
    });
    expect(module.getEditor()).toBeUndefined();
    const editor = { replace: jest.fn() };
    window.CKEDITOR = editor;
    expect(module.getEditor()).toBe(editor);
  });

  test("initializes every rich-text textarea that has an id", async () => {
    const module = await importModule();
    document.body.innerHTML = `
      <textarea id="first" class="rich-text"></textarea>
      <textarea class="rich-text"></textarea>
      <textarea id="plain"></textarea>
    `;
    const editor = { replace: jest.fn() };
    expect(module.initializeEditors(document, undefined)).toBe(false);
    expect(module.initializeEditors(document, editor)).toBe(true);
    expect(editor.replace).toHaveBeenCalledTimes(1);
    expect(editor.replace).toHaveBeenCalledWith("first", module.editorConfig);
  });

  test("polls until CKEditor exists and then clears its interval", async () => {
    document.body.innerHTML =
      '<textarea id="biography" class="rich-text"></textarea>';
    const module = await importModule();
    expect(jest.getTimerCount()).toBe(1);
    jest.advanceTimersByTime(100);
    expect(jest.getTimerCount()).toBe(1);

    const editor = { replace: jest.fn() };
    window.CKEDITOR = editor;
    jest.advanceTimersByTime(100);
    expect(editor.replace).toHaveBeenCalledWith(
      "biography",
      module.editorConfig,
    );
    expect(jest.getTimerCount()).toBe(0);
  });

  test("allows the poll operation to be invoked directly", async () => {
    document.body.innerHTML =
      '<textarea id="direct" class="rich-text"></textarea>';
    const module = await importModule();
    const editor = { replace: jest.fn() };
    window.CKEDITOR = editor;
    module.pollForEditor();
    expect(editor.replace).toHaveBeenCalledWith("direct", module.editorConfig);
    expect(jest.getTimerCount()).toBe(0);
  });
});
