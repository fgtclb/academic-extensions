/* Generated from Resources/Private/TypeScript — do not edit. */
import { IconFactory, Sizes, endpointFrom } from "@fgtclb/academic-base/frontend/icons.js";
const demonstrate = () => {
  for (const demo of document.querySelectorAll("[data-icon-demo]")) {
    const icons = new IconFactory(endpointFrom(demo));
    for (const slot of demo.querySelectorAll("[data-icon-demo-identifier]")) {
      const identifier = slot.dataset.iconDemoIdentifier ?? "";
      icons.getIconElement(identifier, Sizes.small).then(
        (icon) => {
          slot.replaceChildren(icon);
          slot.dataset.iconDemoState = "rendered";
        },
        () => {
          slot.textContent = "not available";
          slot.dataset.iconDemoState = "failed";
        }
      );
    }
  }
};
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", demonstrate, { once: true });
} else {
  demonstrate();
}
