/**
 * The global "L" a Leaflet plugin reads, for the vendor build - see
 * "Build/vendor.mjs". esbuild injects this export wherever the plugin's sources
 * name "L", so the plugin extends the Leaflet the page loaded before it, which
 * publishes itself as "window.LeafletObject".
 */
/* global window -- this file is bundled into a browser script, not run by node. */
export const L = window.LeafletObject;
