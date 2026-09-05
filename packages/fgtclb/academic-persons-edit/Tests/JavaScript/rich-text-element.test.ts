import assert from "node:assert/strict";
import { beforeEach, describe, it } from "node:test";
import { resetBody, settle } from "../../../../../Build/tests/dom.mjs";
import {
  readEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { profileRichTextElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  ProfileRichTextElement,
  registerProfileRichTextElement,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/rich-text.js";
import { getRichTextEditorValue } from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";
import { profileEditingRoot, select } from "./Fixtures/profile-editing.ts";

/**
 * The element that owns one CKEditor.
 *
 * Everything here is about a lifetime rather than about markup: an editor is
 * created when the element connects and destroyed when it disconnects, and
 * neither may happen twice. A `ClassicEditor` that is dropped without being
 * destroyed keeps its window and document listeners until it is collected, and
 * a second one created on a textarea that already has one leaves two editors
 * fighting over the same field.
 *
 * The editor itself is the stub of `Build/tests/stubs/ckeditor.mjs`, which
 * reports what was asked of it on the textarea: `data-test-ckeditor` is `live`
 * or `destroyed`, and `data-test-ckeditor-destroys` counts the destroys. A stub
 * proves the lifecycle, not CKEditor.
 */
registerProfileRichTextElement();

describe("the rich text field element", () => {
  let root: HTMLElement;
  let context: EditingContext;

  const mount = async (
    properties: Partial<ProfileRichTextElement> = {},
  ): Promise<ProfileRichTextElement> => {
    const element = document.createElement(
      profileRichTextElementName,
    ) as ProfileRichTextElement;
    Object.assign(element, { context, ...properties });
    root.append(element);
    await settle(20);

    return element;
  };

  beforeEach(() => {
    const body = resetBody(profileEditingRoot());
    root = select(body, "[data-academic-persons-profile-editing]", HTMLElement);
    context = readEditingContext(root);
  });

  it("is defined under its published name", () => {
    assert.equal(profileRichTextElementName, "academic-persons-edit-rich-text");
    assert.equal(
      customElements.get(profileRichTextElementName),
      ProfileRichTextElement,
    );
    registerProfileRichTextElement();
  });

  it("creates one textarea carrying the hooks the modules query", async () => {
    const element = await mount({
      configuration: {
        ariaDescribedBy: "profile-editing-document-field-error-0-bodytext",
        characterLimit: 40,
        id: "profile-editing-document-field-0-bodytext",
        name: "bodytext",
        required: true,
      },
      value: "<p>Note</p>",
    });

    const fields = element.querySelectorAll("textarea");
    assert.equal(fields.length, 1);
    const field = select(element, "textarea", HTMLTextAreaElement);
    assert.equal(field.id, "profile-editing-document-field-0-bodytext");
    assert.equal(field.name, "bodytext");
    assert.equal(field.required, true);
    assert.equal(field.value, "<p>Note</p>");
    // The two hooks: one makes it a rich text field, the other is how the
    // controller collects the value and how the counter finds its field.
    assert.equal(field.getAttribute("data-pe-rich-text"), "");
    assert.equal(field.dataset.peDocumentField, "bodytext");
    assert.equal(field.dataset.peCharacterLimit, "40");
    assert.equal(
      field.getAttribute("aria-describedby"),
      "profile-editing-document-field-error-0-bodytext",
    );
  });

  it("rewrites the control when the configuration changes, without replacing it", async () => {
    const element = await mount({ configuration: { name: "bodytext" } });
    const field = select(element, "textarea", HTMLTextAreaElement);

    element.configuration = { name: "bodytext", disabled: true, invalid: true };

    assert.equal(select(element, "textarea", HTMLTextAreaElement), field);
    assert.equal(field.disabled, true);
    assert.equal(field.getAttribute("aria-invalid"), "true");
  });

  it("creates exactly one editor when it connects", async () => {
    const element = await mount({ configuration: { name: "bodytext" } });

    const field = select(element, "textarea", HTMLTextAreaElement);
    assert.equal(field.getAttribute("data-test-ckeditor"), "live");
    assert.equal(getRichTextEditorValue(field), "");
  });

  it("destroys exactly one editor when it is removed", async () => {
    const element = await mount({ configuration: { name: "bodytext" } });
    const field = select(element, "textarea", HTMLTextAreaElement);

    element.remove();
    await settle(20);

    assert.equal(field.getAttribute("data-test-ckeditor"), "destroyed");
    assert.equal(field.getAttribute("data-test-ckeditor-destroys"), "1");
    assert.equal(getRichTextEditorValue(field), null);
  });

  /**
   * A move in the document is a disconnect and a reconnect of the same element
   * with the same textarea, and the destroy that the disconnect starts is a
   * promise. The creation is chained behind it, so the two cannot race for the
   * one node they share - which is the only case where they can, because every
   * other reopen builds a new element with a new textarea.
   */
  it("creates a new editor after a move, and only one", async () => {
    const element = await mount({ configuration: { name: "bodytext" } });
    const field = select(element, "textarea", HTMLTextAreaElement);

    element.remove();
    root.append(element);
    await settle(20);

    assert.equal(select(element, "textarea", HTMLTextAreaElement), field);
    assert.equal(field.getAttribute("data-test-ckeditor"), "live");
    assert.equal(field.getAttribute("data-test-ckeditor-destroys"), "1");
  });

  it("reads its value back through the field the editor owns", async () => {
    const element = await mount({ configuration: { name: "bodytext" }, value: "<p>A</p>" });
    const field = select(element, "textarea", HTMLTextAreaElement);

    field.value = "<p>B</p>";

    assert.equal(element.value, "<p>B</p>");
  });

  it("creates no editor for a field the server locked", async () => {
    const element = await mount({
      configuration: { name: "bodytext", disabled: true },
    });

    assert.equal(
      select(element, "textarea", HTMLTextAreaElement).getAttribute("data-test-ckeditor"),
      null,
    );
  });

  it("takes its editing context from the profile editor it stands in", async () => {
    const owner = document.createElement(
      "academic-persons-edit-profile-editing",
    ) as HTMLElement & { context?: EditingContext };
    owner.context = context;
    root.append(owner);
    const element = document.createElement(
      profileRichTextElementName,
    ) as ProfileRichTextElement;
    element.configuration = { name: "bodytext" };
    owner.append(element);
    await settle(20);

    assert.equal(element.context, context);
    assert.equal(
      select(element, "textarea", HTMLTextAreaElement).getAttribute("data-test-ckeditor"),
      "live",
    );
  });
});
