import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { resetBody, settle } from "../../../../../Build/tests/dom.mjs";
import { profileContractContactsElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  ProfileContractContactsElement,
  registerProfileContractContactsElement,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/contract-contacts.js";
import { profileEditingRoot, select } from "./Fixtures/profile-editing.ts";

/**
 * Both orders in which this element and its properties can meet.
 *
 * It is never in the markup: the document editor's template creates it, and Lit
 * assigns its five properties in the same commit of the same render - so the
 * order that happens in a browser is "defined first, created afterwards". The
 * other one is not hypothetical either. The entry point is a module and the
 * element modules are loaded through the import map, so a slow map leaves a
 * document editor that was created by an earlier script with an element the
 * registry has not seen yet, and every property it was handed sits on it as a
 * plain own property. `ReactiveElement` takes those over on upgrade; an element
 * that did not would render its defaults and never see the data again.
 *
 * A file of its own, because an element cannot be undefined once it is defined
 * and node runs every test file in a process of its own. Nothing before the
 * first test may register it.
 */
describe("upgrading the contract contacts element", () => {
  const sections = [
    {
      identifier: "addresses",
      items: [{ hidden: false, summary: [{ label: "City", value: "London" }], uid: 21 }],
      label: "Addresses",
      singularLabel: "Address",
    },
  ];

  it("adopts the properties it was handed before it was defined", async () => {
    const body = resetBody(profileEditingRoot());
    const root = select(body, "[data-academic-persons-profile-editing]", HTMLElement);
    const element = document.createElement(profileContractContactsElementName);
    // Exactly what the document editor's template commits, and the definition
    // has not run: these are own properties on a plain HTMLElement here.
    Object.assign(element, {
      contract: 5,
      emptyMessage: "No contacts yet.",
      sections,
    });
    root.append(element);
    assert.equal(element instanceof ProfileContractContactsElement, false);

    registerProfileContractContactsElement();
    await settle(20);

    assert.ok(element instanceof ProfileContractContactsElement);
    assert.equal(select(root, "h3", HTMLElement).textContent?.trim(), "Addresses");
    assert.equal(
      select(root, "[data-pe-contract-contact-item]", HTMLElement).getAttribute(
        "data-pe-contract-contact-item",
      ),
      "21",
    );
  });

  it("renders a list that is created after the module loaded", async () => {
    const body = resetBody(profileEditingRoot());
    const root = select(body, "[data-academic-persons-profile-editing]", HTMLElement);

    const element = document.createElement(
      profileContractContactsElementName,
    ) as ProfileContractContactsElement;
    element.contract = 5;
    element.sections = sections;
    root.append(element);
    await element.updateComplete;

    assert.ok(element instanceof ProfileContractContactsElement);
    assert.equal(select(root, "h3", HTMLElement).textContent?.trim(), "Addresses");
  });

  /**
   * The one property whose absence is not visible in the list: the context is
   * resolved from the owner on connection, and an element that was upgraded
   * connected before the definition ran.
   */
  it("resolves its editing context on the connection the upgrade replays", async () => {
    const body = resetBody(profileEditingRoot());
    const root = select(body, "[data-academic-persons-profile-editing]", HTMLElement);
    const element = document.createElement(profileContractContactsElementName);
    Object.assign(element, { sections });
    root.append(element);

    registerProfileContractContactsElement();
    await settle(20);

    // No owner element above it in this fixture, so there is no context to
    // find - and the list renders regardless, with the labels of its controls
    // empty rather than with an exception in "connectedCallback()".
    assert.ok(element instanceof ProfileContractContactsElement);
    assert.equal(element.context, null);
    assert.equal(
      select(root, "[data-pe-contract-contact-view]", HTMLButtonElement).title,
      "",
    );
  });
});
