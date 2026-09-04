/**
 * The parts of Vue 3 this extension uses, and the only place the vendored
 * runtime is named.
 *
 * The runtime is a plain JavaScript file below `Resources/Public/JavaScript/
 * vendor/vue/<version>/` with no type declarations, imported relatively rather
 * than through the import map: the path already carries the version, so it needs
 * no cache key, and upgrading Vue is a new directory plus one edit here. The
 * shapes below are what the modules of this extension actually call - a hand
 * written surface rather than the full Vue types, which the package does not
 * ship in a form this build could consume.
 */

export interface App {
  /**
   * The subset of the application configuration this extension writes.
   *
   * `compilerOptions.isCustomElement` tells the runtime template compiler which
   * unknown tags are custom elements rather than components it should resolve
   * and warn about. It exists for as long as Vue still renders a part of the
   * editor that contains one of this extension's elements, and leaves with the
   * runtime.
   */
  readonly config: {
    compilerOptions: {
      isCustomElement?: (tag: string) => boolean;
    };
  };
  mount(container: Element): unknown;
}

export interface Ref<T> {
  value: T;
}

interface VueRuntime {
  createApp: (component: { setup: () => Record<string, unknown> }) => App;
  nextTick: () => Promise<void>;
  onMounted: (callback: () => void) => void;
  reactive: <T extends object>(value: T) => T;
  ref: <T>(value: T) => Ref<T>;
}

// @ts-expect-error -- The versioned runtime exists in the public output tree.
import * as vendoredVue from "../vendor/vue/3.5.42/vue.esm-browser.prod.js";

const vue = vendoredVue as VueRuntime;

export const createApp = vue.createApp;
export const nextTick = vue.nextTick;
export const onMounted = vue.onMounted;
export const reactive = vue.reactive;
export const ref = vue.ref;
