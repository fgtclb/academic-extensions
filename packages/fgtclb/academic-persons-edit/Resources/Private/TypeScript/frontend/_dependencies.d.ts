declare module "@fgtclb/academic-persons-edit/frontend/vue.js" {
  export interface App {
    mount(container: Element): unknown;
  }

  export interface Ref<T> {
    value: T;
  }

  export const createApp: (component: {
    setup: () => Record<string, unknown>;
  }) => App;
  export const nextTick: () => Promise<void>;
  export const onMounted: (callback: () => void) => void;
  export const reactive: <T extends object>(value: T) => T;
  export const ref: <T>(value: T) => Ref<T>;
}

declare module "@fgtclb/academic-persons-edit/cropper" {
  interface CropperImageElement extends HTMLElement {
    $getTransform?: () => number[];
    $ready?: () => Promise<void>;
  }

  export interface CropperSelectionElement extends HTMLElement {
    aspectRatio: number;
    height: number;
    initialAspectRatio: number;
    initialCoverage: number;
    width: number;
    $change: (
      x: number,
      y: number,
      width: number,
      height: number,
      aspectRatio: number,
      emitEvent: boolean,
    ) => void;
    $toCanvas: (options: { width: number }) => Promise<HTMLCanvasElement>;
  }

  export default class Cropper {
    constructor(image: HTMLImageElement, options: { container: HTMLElement });
    destroy(): void;
    getCropperImage(): CropperImageElement | null;
    getCropperSelection(): CropperSelectionElement | null;
  }
}

declare module "@ckeditor/ckeditor5-basic-styles" {
  export const Bold: unknown;
  export const Italic: unknown;
}

declare module "@ckeditor/ckeditor5-editor-classic" {
  interface EditorModelDocument {
    on(eventName: "change:data", listener: () => void): void;
  }

  export interface InlineRichTextEditor {
    editing: { view: { focus: () => void } };
    getData(): string;
    model: { document: EditorModelDocument };
    setData(value: string): void;
  }

  export const ClassicEditor: {
    create(
      field: HTMLTextAreaElement,
      configuration: Record<string, unknown>,
    ): Promise<InlineRichTextEditor>;
  };
}

declare module "@ckeditor/ckeditor5-essentials" {
  export const Essentials: unknown;
}

declare module "@ckeditor/ckeditor5-link" {
  export const Link: unknown;
}

declare module "@ckeditor/ckeditor5-list" {
  export const List: unknown;
}

declare module "@ckeditor/ckeditor5-paragraph" {
  export const Paragraph: unknown;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/common.js" {
  export type EditableField =
    | HTMLInputElement
    | HTMLSelectElement
    | HTMLTextAreaElement;
  export type JsonResult = Record<string, unknown> & { success: true };
  export type StatusType = "danger" | "success" | "info" | "warning";

  export const getProfileUid: (root: HTMLElement) => number | null;
  export const initializePopover: (scope?: ParentNode) => unknown[];
  export const isEditableField: (
    element: unknown,
  ) => element is EditableField;
  export const requestJson: (
    url: string,
    options?: RequestInit,
  ) => Promise<JsonResult>;
  export const rootSelector: string;
  export const showStatus: (
    root: HTMLElement,
    type: StatusType,
    message?: string | null,
  ) => void;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js" {
  import type { InlineRichTextEditor } from "@ckeditor/ckeditor5-editor-classic";

  export const ensureRichTextEditor: (
    root: HTMLElement,
    field: HTMLTextAreaElement,
  ) => Promise<InlineRichTextEditor | null>;
  export const getPlainText: (value: string) => string;
  export const getRichTextEditorValue: (
    field: HTMLTextAreaElement,
  ) => string | null;
  export const getRichTextInitialValue: (
    field: HTMLTextAreaElement,
  ) => string | undefined;
  export const isAllowedRichTextLink: (value: string) => boolean;
  export const isRichTextField: (
    field: Element,
  ) => field is HTMLTextAreaElement;
  export const parseRichTextPreview: (value: string) => Document;
  export const renderRichTextPreview: (
    root: HTMLElement,
    field: HTMLTextAreaElement,
    value: unknown,
  ) => void;
  export const setRichTextEditorValue: (
    field: HTMLTextAreaElement,
    value: string,
  ) => void;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/fields.js" {
  export const initializeFieldEditing: (root: HTMLElement) => void;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/documents.js" {
  export interface DocumentEditingController {
    contractContact: Record<string, unknown>;
    document: Record<string, unknown>;
    openDocument(mode: string, event: Event): Promise<void>;
    closeDocument(): void;
    finishDocumentClose(): void;
    submitDocument(): Promise<void>;
    openContractContact(
      mode: string,
      section: string,
      event: Event,
      record?: number,
    ): Promise<void>;
    closeContractContact(): void;
    submitContractContact(): Promise<void>;
    sortContractContact(direction: string, section: string, record: number): Promise<void>;
    sortDocument(direction: string, event: Event): Promise<void>;
    documentFieldHtml(field: unknown): string;
  }

  export const createDocumentEditing: (
    root: HTMLElement,
  ) => DocumentEditingController;
  export const initializeDocumentSections: (root: HTMLElement) => void;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/image.js" {
  import type { Ref } from "@fgtclb/academic-persons-edit/frontend/vue.js";

  export interface ImageEditingController {
    cropperSource: Ref<HTMLImageElement | null>;
    cropperStage: Ref<HTMLElement | null>;
    fileInput: Ref<HTMLInputElement | null>;
    image: Record<string, unknown>;
    openImage(): Promise<void>;
    closeImage(): void;
    finishImageClose(): void;
    selectImage(event: Event): Promise<void>;
    submitImage(event: Event): Promise<void>;
    deleteImage(): Promise<void>;
  }

  export const createImageEditing: (root: HTMLElement) => ImageEditingController;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/sticky-image.js" {
  export const initializeStickyImageOffset: (root: HTMLElement) => void;
}

declare module "@fgtclb/academic-persons-edit/frontend/profile/sync.js" {
  export const createSkipSync: (root: HTMLElement) => {
    updateSkipSync: (event: Event) => Promise<void>;
  };
}
