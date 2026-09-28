/**
 * Stands in for "leaflet.markercluster", the marker cluster plugin module the
 * partner map imports. It records into the "recorded" of the Leaflet stub, so
 * a test reads one place - see "leaflet.mjs".
 */
import { recorded } from './leaflet.mjs';

export class MarkerClusterGroup {
    constructor(options) {
        this.options = { ...options };
        this.added = [];
        recorded.clusterGroups.push(this);
    }

    addLayer(layer) {
        this.added.push(layer);
        recorded.markers.push(layer);
    }

    getLayers() {
        return this.added;
    }

    getBounds() {
        return { _southWest: {} };
    }
}
