/**
 * Stands in for "leaflet", the Leaflet ES module the partner map imports.
 *
 * Real Leaflet measures its container, loads tiles and animates. jsdom lays
 * nothing out, and none of it is ours. What the tests are about is what the
 * partner map asks of Leaflet: which tiles, which zoom, which markers, which
 * popup, and whether it fits the markers or centres the map.
 *
 * It records into "recorded", which a test reads by importing this file by its
 * path. That is the same module instance the resolve hook hands the partner
 * map for "leaflet", so what the map did is what the test sees. "resetLeaflet()"
 * starts a test from nothing.
 *
 * See "docs/testing/javascript-tests.md".
 */

export const recorded = {
    map: null,
    tiles: null,
    markers: [],
    clusterGroups: [],
};

export const resetLeaflet = () => {
    recorded.map = null;
    recorded.tiles = null;
    recorded.markers = [];
    recorded.clusterGroups = [];
};

export const tileLayer = (urlTemplate, options) => {
    const tiles = { urlTemplate, options: { ...options } };
    recorded.tiles = tiles;
    return tiles;
};

export const map = (elementId, options) => {
    const created = {
        elementId,
        options: { zoom: options.zoom, maxZoom: options.maxZoom },
        layers: [...options.layers],
        fitted: null,
        view: null,
        addLayer(layer) {
            this.layers.push(layer);
        },
        fitBounds(_bounds, fitOptions) {
            this.fitted = fitOptions;
        },
        setView(center, zoom) {
            this.view = { center, zoom };
        },
    };
    recorded.map = created;
    return created;
};

/** Records the options of every default icon the map creates. */
class DefaultIcon {
    constructor(options = {}) {
        this.options = { ...options };
    }
}

export const Icon = { Default: DefaultIcon };

export const marker = (position, options = {}) => {
    const created = {
        position,
        icon: options.icon ?? null,
        popup: null,
        bindPopup(content) {
            this.popup = content;
            return this;
        },
    };
    return created;
};
