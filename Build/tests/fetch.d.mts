/**
 * The types of "fetch.mjs", the recording request double.
 *
 * Hand written for the same reason as "dom.d.mts": the harness is plain
 * JavaScript, and declaring its small surface is what makes a test that asserts
 * on a request payload type checked rather than "any".
 *
 * See "docs/testing/javascript-tests.md".
 */

/** One recorded request. "body" is the decoded JSON where the body was JSON. */
export interface RecordedRequest {
  url: string;
  method: string;
  headers: Record<string, string>;
  credentials?: string;
  body: unknown;
  rawBody: unknown;
  /** The signal the request was started with, null without one. */
  signal: AbortSignal | null;
}

export interface FetchDouble {
  readonly calls: RecordedRequest[];
  respond: (
    body: unknown,
    options?: { status?: number; headers?: Record<string, string>; raw?: boolean; url?: string },
  ) => void;
  respondLater: () => {
    settle: (
      body: unknown,
      options?: { status?: number; headers?: Record<string, string>; raw?: boolean; url?: string },
    ) => void;
  };
  respondWithError: (body: unknown, status?: number) => void;
  respondWithText: (body: string, status?: number) => void;
  /** Queues an HTML page with the url a followed redirect ended at. */
  respondWithPage: (body: string, url: string, status?: number) => void;
  lastCall: () => RecordedRequest | undefined;
  restore: () => void;
}

/**
 * Replaces "globalThis.fetch" with the double and returns its handle. One call
 * per test; the previous installation is replaced rather than stacked.
 */
export declare const installFetch: () => FetchDouble;
