/**
 * The entry of the classic marker cluster script, for the vendor build - see
 * "Build/vendor.mjs".
 *
 * The plugin's sources add their classes to the global Leaflet, which
 * "leaflet-global.mjs" hands them as "L". This also publishes
 * "window.Leaflet.markercluster", as the UMD build this replaces did.
 */
/* global window -- this file is bundled into a browser script, not run by node. */
import { MarkerCluster, MarkerClusterGroup } from '../node_modules/leaflet.markercluster/src/index.js';

window.Leaflet = window.Leaflet || {};
window.Leaflet.markercluster = { MarkerClusterGroup, MarkerCluster };
