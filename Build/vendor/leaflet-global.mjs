/**
 * The global "L" a Leaflet plugin reads, for the vendor build - see
 * "Build/vendor.mjs". esbuild injects this export wherever the plugin's
 * sources name "L", and keeps "leaflet" itself external, so the plugin
 * imports the one Leaflet module the page loads.
 */
import * as leaflet from 'leaflet';

export const L = { ...leaflet };
