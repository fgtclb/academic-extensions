/**
 * The entry of the classic Leaflet script, for the vendor build - see
 * "Build/vendor.mjs".
 *
 * It publishes what the UMD build this replaces published, under the names the
 * extension renamed it to: "window.LeafletObject", "window.leaflet" and
 * "LeafletObject.noConflict()". The object is a plain copy of the module
 * namespace, so its members stay writable and a classic plugin can add its own,
 * as they could on the UMD build.
 */
/* global window -- this file is bundled into a browser script, not run by node. */
import * as leaflet from '../node_modules/leaflet/dist/leaflet-src.esm.js';

const previous = window.LeafletObject;
const LeafletObject = { ...leaflet };

LeafletObject.noConflict = function () {
    window.LeafletObject = previous;
    return this;
};

window.LeafletObject = LeafletObject;
window.leaflet = LeafletObject;
