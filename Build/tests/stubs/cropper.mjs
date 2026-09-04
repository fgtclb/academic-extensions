/**
 * Stands in for "@fgtclb/academic-persons-edit/cropper", the CropperJS 2 build
 * this extension vendors and publishes under that specifier.
 *
 * Real CropperJS registers custom elements, measures a layout and rasterises
 * through a canvas. jsdom lays nothing out and has no canvas, so none of that
 * can run here - and none of it is ours. What the tests are about is the code
 * around it: when a cropper is created, on which stage, and - the reason this
 * stub exists at all - whether it is destroyed again on every path that drops
 * it.
 *
 * It reports through the DOM, the way the CKEditor stub does, so a test asserts
 * on the element it already has:
 *
 *   data-test-cropper="live"        a cropper was created on this container
 *   data-test-cropper="destroyed"   and later destroyed
 *   data-test-cropper-destroys="n"  how many times "destroy()" was called
 *
 * See "docs/testing/javascript-tests.md".
 */

class StubSelection {
    constructor(container) {
        this.container = container;
        this.aspectRatio = 0;
        this.initialAspectRatio = 0;
        this.initialCoverage = 0;
        this.width = 0;
        this.height = 0;
        // The production code reads "selection.parentElement" to measure the
        // canvas the selection sits in. The container stands in for it, and a
        // test that needs a size puts one on the container.
        this.parentElement = container;
    }

    $change(x, y, width, height, aspectRatio) {
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.aspectRatio = aspectRatio;
    }

    $toCanvas({ width }) {
        // Not a real canvas: jsdom has none. Only the two dimensions the caller
        // checks, plus the "toBlob" it encodes through.
        return Promise.resolve({
            width,
            height: Math.round(width / (this.aspectRatio || 1)),
            toBlob: (callback, mimeType) => {
                callback(new Blob(['stub-image'], { type: mimeType ?? 'image/png' }));
            },
        });
    }
}

class StubImage {
    constructor() {
        this.ready = false;
    }

    $getTransform() {
        return [1, 0, 0, 1, 0, 0];
    }

    $ready() {
        this.ready = true;

        return Promise.resolve();
    }
}

export default class Cropper {
    #container;
    #selection;
    #image;

    constructor(source, { container }) {
        this.source = source;
        this.#container = container;
        this.#selection = new StubSelection(container);
        this.#image = new StubImage();
        container.setAttribute('data-test-cropper', 'live');
    }

    getCropperSelection() {
        return this.#selection;
    }

    getCropperImage() {
        return this.#image;
    }

    destroy() {
        const destroys = Number.parseInt(this.#container.getAttribute('data-test-cropper-destroys') ?? '0', 10) + 1;
        this.#container.setAttribute('data-test-cropper', 'destroyed');
        this.#container.setAttribute('data-test-cropper-destroys', String(destroys));
    }
}
