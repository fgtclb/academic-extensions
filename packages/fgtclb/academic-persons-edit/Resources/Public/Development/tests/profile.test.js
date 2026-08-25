import { describe, expect, jest, test } from "@jest/globals";
import { initializeInlineProfiles } from "../../JavaScript/frontend/profile.js";

describe("frontend/profile entry module", () => {
  test("initializes every HTML root in the supplied scope and ignores non-HTML roots", () => {
    const header = document.createElement("header");
    header.id = "page-header";
    header.classList.add("navbar-fixed-top");
    jest.spyOn(header, "getBoundingClientRect").mockReturnValue({ height: 20 });
    document.body.append(header);
    const scope = document.createElement("div");
    scope.innerHTML = `
      <svg data-academic-persons-inline-edit></svg>
      <section data-academic-persons-inline-edit>
        <aside data-ie-sticky-image></aside>
      </section>
    `;
    document.body.append(scope);

    initializeInlineProfiles(scope);

    expect(scope.querySelector("[data-ie-sticky-image]").style.top).toBe("30px");
  });

  test("accepts an empty scope", () => {
    expect(() => initializeInlineProfiles(document.createElement("div"))).not.toThrow();
  });
});
