/**
 * Stands in for "cropperjs", the CropperJS 1.6.1 build TYPO3 core publishes and
 * the profile image editor imports.
 *
 * Real CropperJS measures a layout, builds a widget of a dozen elements and
 * rasterises through a canvas. jsdom lays nothing out and has no canvas, so
 * none of that can run here - and none of it is ours. What the tests are about
 * is the code around it: when a cropper is created, on which stage, at which
 * ratio, at which width the crop is rasterised, and - the reason this stub
 * exists at all - whether it is destroyed again on every path that drops it.
 *
 * It reports through the DOM, the way the CKEditor stub does, so a test asserts
 * on the element it already has. The element it reports on is the stage, which
 * is the parent of the image: that is where the real one mounts too.
 *
 *   data-test-cropper="live"        a cropper was created on this stage
 *   data-test-cropper="destroyed"   and later destroyed
 *   data-test-cropper-destroys="n"  how many times "destroy()" was called
 *   data-test-cropper-ratio="r"     the aspect ratio it was configured with
 *   data-test-cropper-width="n"     the width "getCroppedCanvas()" was asked for
 *
 * The arithmetic is the real one, reduced: the crop box is the configured share
 * of the stage, fitted to the ratio and centred, exactly as "initCropBox()"
 * computes it, and "getData()" divides that by the scale the image is displayed
 * at. A test says what the geometry is with "setClientSize()" on the stage and,
 * where the cap on the upload width is what it is about, with a "naturalWidth"
 * on the source image.
 *
 * See "docs/testing/javascript-tests.md".
 */

export default class Cropper {
    #stage;
    #source;
    #aspectRatio;
    #coverage;

    constructor(source, options = {}) {
        this.#source = source;
        // Not a container option: CropperJS 1 builds its widget next to the
        // image it is handed, so the parent is the stage. A source without one
        // is a caller error and not something to paper over.
        this.#stage = source.parentElement;
        if (this.#stage === null) {
            throw new Error('The cropper was given an image that is not in a stage.');
        }
        this.#aspectRatio = Number(options.aspectRatio) || 1;
        this.#coverage = Number(options.autoCropArea) || 0.8;
        this.#stage.setAttribute('data-test-cropper', 'live');
        this.#stage.setAttribute('data-test-cropper-ratio', String(this.#aspectRatio));
        // Asynchronously, like the real one, which decodes the image before it
        // reports - but in a microtask rather than in a timer, because
        // "settle()" of "Build/tests/dom.mjs" drains microtasks and never
        // reaches a timer.
        queueMicrotask(() => options.ready?.());
    }

    getCropBoxData() {
        const stageWidth = this.#stage.clientWidth;
        const stageHeight = this.#stage.clientHeight;
        if (stageWidth <= 0 || stageHeight <= 0) {
            // The real one answers with nothing while it has no box, and a
            // stage of zero is what jsdom reports for anything a test has not
            // given a size to.
            return {};
        }
        let width = stageWidth;
        let height = width / this.#aspectRatio;
        if (height > stageHeight) {
            height = stageHeight;
            width = height * this.#aspectRatio;
        }
        width *= this.#coverage;
        height *= this.#coverage;

        return {
            left: (stageWidth - width) / 2,
            top: (stageHeight - height) / 2,
            width,
            height,
        };
    }

    getData(rounded = false) {
        const { width = 0, height = 0 } = this.getCropBoxData();
        const naturalWidth = Number(this.#source.naturalWidth) || 0;
        const scale = naturalWidth > 0 ? this.#stage.clientWidth / naturalWidth : 1;
        const data = { x: 0, y: 0, width: width / scale, height: height / scale };

        return rounded
            ? { ...data, width: Math.round(data.width), height: Math.round(data.height) }
            : data;
    }

    getCroppedCanvas({ width } = {}) {
        // Reported as it was asked for, and "auto" where it was not asked for
        // at all: the real one then rasterises at the natural size of the crop,
        // which is the same number the caller would have computed - so a stub
        // that reported that number could not tell the two apart.
        this.#stage.setAttribute('data-test-cropper-width', width === undefined ? 'auto' : String(width));
        const target = width ?? Math.round(this.getData(true).width);

        // Not a real canvas: jsdom has none. Only the two dimensions the caller
        // checks, plus the "toBlob" it encodes through.
        return {
            width: target,
            height: Math.round(target / this.#aspectRatio),
            toBlob: (callback, mimeType) => {
                callback(new Blob(['stub-image'], { type: mimeType ?? 'image/png' }));
            },
        };
    }

    destroy() {
        const destroys = Number.parseInt(this.#stage.getAttribute('data-test-cropper-destroys') ?? '0', 10) + 1;
        this.#stage.setAttribute('data-test-cropper', 'destroyed');
        this.#stage.setAttribute('data-test-cropper-destroys', String(destroys));
    }
}
