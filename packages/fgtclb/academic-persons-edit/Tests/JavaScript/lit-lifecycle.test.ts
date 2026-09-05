import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { html, type PropertyValues, type TemplateResult } from "lit";
import { resetBody, settle } from "../../../../../Build/tests/dom.mjs";
import { ProfileEditingElement } from "@fgtclb/academic-persons-edit/frontend/profile/elements/base.js";

/**
 * The base class of every element of the editor, and the one thing it does.
 *
 * Every element here controls markup Fluid rendered and produces none, so
 * lit-html must never reach the children below one. `createRenderRoot()`
 * returns a detached `<div>`, which makes that structural rather than a matter
 * of care - and the three cases that go red when it returns `this` instead are
 * the whole justification for the file `elements/base.ts`.
 *
 * The last case is the standing justification for `LitElement` itself:
 * decision 1 of the design keeps it for the reactive plumbing, and if that
 * plumbing ever has nothing to assert, the base class has nothing to do.
 */
const elementName = "academic-persons-edit-test-lifecycle";

class LifecycleProbe extends ProfileEditingElement {
  static override properties = {
    heading: { attribute: false },
  };

  declare heading: string;

  changes: string[][] = [];
  firstUpdates = 0;
  updates = 0;

  constructor() {
    super();
    this.heading = "";
  }

  override firstUpdated(): void {
    this.firstUpdates += 1;
  }

  override updated(changed: PropertyValues<LifecycleProbe>): void {
    this.updates += 1;
    this.changes.push(Array.from(changed.keys(), String));
  }
}

customElements.define(elementName, LifecycleProbe);

const renderingName = "academic-persons-edit-test-rendering";

/**
 * A subclass that does what nobody is supposed to do: it returns a template
 * from `render()`. The children below it have to survive that, and they only
 * do because the render root is somewhere else entirely.
 */
class RenderingProbe extends ProfileEditingElement {
  override render(): TemplateResult {
    return html`<p>rendered by lit-html</p>`;
  }
}

customElements.define(renderingName, RenderingProbe);

const mount = async (name: string): Promise<HTMLElement> => {
  const body = resetBody(
    `<${name}><span id="fluid">from Fluid</span></${name}>`,
  );
  const element = body.querySelector(name);
  if (!(element instanceof HTMLElement)) {
    throw new Error(`The test markup has no "${name}".`);
  }
  await settle(0);

  return element;
};

describe("the profile editing element base", () => {
  it("leaves the children it was handed exactly where they were", async () => {
    const element = (await mount(elementName)) as LifecycleProbe;
    const before = Array.from(element.childNodes);

    element.heading = "one";
    await element.updateComplete;
    element.heading = "two";
    await element.updateComplete;

    assert.deepEqual(Array.from(element.childNodes), before);
    // And nothing else is there either: a render root that were this element
    // would have put a marker comment in front of them on the first update.
    assert.deepEqual(
      Array.from(element.childNodes, (node): string => node.nodeName),
      ["SPAN"],
    );
    assert.equal(element.querySelector("#fluid")?.textContent, "from Fluid");
  });

  /**
   * The two traces lit-html leaves in a container it renders into. Neither is
   * here, which is what "it cannot reach the children" means concretely - not
   * "it happens to leave them alone today".
   */
  it("carries no lit-html marker and no part of its own", async () => {
    const element = await mount(elementName);

    assert.equal("_$litPart$" in element, false);
    assert.equal(
      Array.from(element.childNodes).some(
        (node): boolean => node.nodeType === Node.COMMENT_NODE,
      ),
      false,
    );
    assert.notEqual((element as LifecycleProbe & { renderRoot: Node }).renderRoot, element);
  });

  it("ignores a template a subclass returns, rather than committing it", async () => {
    const element = await mount(renderingName);

    assert.equal(element.querySelector("p"), null);
    assert.equal(element.querySelector("#fluid")?.textContent, "from Fluid");
    assert.equal(element.children.length, 1);
  });

  it("opens no shadow root", async () => {
    const element = await mount(elementName);

    assert.equal(element.shadowRoot, null);
  });

  /**
   * What `LitElement` is kept for. The document editor is the element that
   * needs all of it: several property writes of one `openDocument()` have to
   * become one DOM pass, and `changedProperties` is what separates a
   * structural change from a value change.
   */
  it("keeps the reactive plumbing that is the reason for the base class", async () => {
    const element = (await mount(elementName)) as LifecycleProbe;
    const updatesAfterMount = element.updates;

    element.heading = "one";
    element.heading = "two";
    await element.updateComplete;

    assert.equal(element.firstUpdates, 1);
    assert.equal(element.updates, updatesAfterMount + 1);
    assert.deepEqual(element.changes.at(-1), ["heading"]);
    assert.equal(element.heading, "two");
    assert.equal(element.hasUpdated, true);
  });
});
