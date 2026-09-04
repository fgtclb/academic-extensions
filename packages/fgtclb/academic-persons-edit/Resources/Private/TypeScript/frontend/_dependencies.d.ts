// Ambient declarations for the libraries this extension does not own.
//
// Only vendor specifiers belong here. The extension's own modules are resolved
// by the "paths" entry of Build/tsconfig.json straight to their TypeScript
// source, so a consumer is checked against the real exports rather than against
// a hand-written copy that drifts.
//
// - "@fgtclb/academic-persons-edit/cropper" is the vendored CropperJS build,
//   published under that specifier by Configuration/JavaScriptModules.php.
// - The six "@ckeditor/*" specifiers are the CKEditor 5 bundles of
//   EXT:rte_ckeditor, mapped one by one in the same file.

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

  export interface ClassicEditorInstance {
    destroy(): Promise<void>;
    editing: { view: { focus: () => void } };
    getData(): string;
    model: { document: EditorModelDocument };
    setData(value: string): void;
  }

  export const ClassicEditor: {
    create(
      field: HTMLTextAreaElement,
      configuration: Record<string, unknown>,
    ): Promise<ClassicEditorInstance>;
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
