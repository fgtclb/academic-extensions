/**
 * The demonstration of the frontend icon API on the icon overview page of the
 * development seed (ACE-595). Development aid only, never released.
 *
 * Every `[data-icon-demo]` block gets an icon factory with the endpoint the
 * JSON icon map inside it names, if it names one, and every slot in the block
 * the icon it names - or "not available" when the factory rejects. The slot
 * reports the outcome in `data-icon-demo-state`, so a browser test can wait for
 * `rendered` or `failed` instead of for a time.
 */
import { IconFactory, Sizes, endpointFrom } from '@fgtclb/academic-base/frontend/icons.js';

const demonstrate = (): void => {
  for (const demo of document.querySelectorAll('[data-icon-demo]')) {
    const icons = new IconFactory(endpointFrom(demo));
    for (const slot of demo.querySelectorAll<HTMLElement>('[data-icon-demo-identifier]')) {
      const identifier = slot.dataset.iconDemoIdentifier ?? '';
      icons.getIconElement(identifier, Sizes.small).then(
        (icon: Element): void => {
          slot.replaceChildren(icon);
          slot.dataset.iconDemoState = 'rendered';
        },
        (): void => {
          slot.textContent = 'not available';
          slot.dataset.iconDemoState = 'failed';
        },
      );
    }
  }
};

// f:asset.module renders the module "async", so it may run before or after the
// document is parsed.
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', demonstrate, { once: true });
} else {
  demonstrate();
}
