/**
 * The types of the recording in "leaflet.mjs", which a test reads by importing
 * the stub by its path. Hand written for the reason "dom.d.mts" gives.
 */

export interface RecordedMarker {
  readonly position: [number, number];
  /** The icon the map handed to "marker()", or null for Leaflet's default. */
  readonly icon: { readonly options: { imagePath?: string } } | null;
  /** What the map handed to "bindPopup()": an element, or a string. */
  readonly popup: HTMLElement | string | null;
}

export interface RecordedTileLayer {
  readonly urlTemplate: string;
  readonly options: { maxZoom: number; attribution: string };
}

export interface RecordedMap {
  readonly elementId: string;
  readonly options: { zoom: number; maxZoom: number };
  readonly layers: unknown[];
  readonly fitted: { padding: [number, number] } | null;
  readonly view: { center: [number, number]; zoom: number } | null;
}

export interface RecordedClusterGroup {
  readonly options: { chunkedLoading?: boolean };
}

export declare const recorded: {
  map: RecordedMap | null;
  tiles: RecordedTileLayer | null;
  markers: RecordedMarker[];
  clusterGroups: RecordedClusterGroup[];
};

export declare const resetLeaflet: () => void;
