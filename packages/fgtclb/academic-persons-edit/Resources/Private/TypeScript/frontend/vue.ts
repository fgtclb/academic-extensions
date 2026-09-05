export interface App {
  mount(container: Element): unknown;
}

export interface Ref<T> {
  value: T;
}

// @ts-expect-error -- The versioned runtime exists in the public output tree.
export { createApp, nextTick, onMounted, reactive, ref } from "../vendor/vue/3.5.42/vue.esm-browser.prod.js";
