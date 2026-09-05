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
}

export interface FetchDouble {
  readonly calls: RecordedRequest[];
  respond: (
    body: unknown,
    options?: { status?: number; headers?: Record<string, string>; raw?: boolean },
  ) => void;
  respondLater: () => {
    settle: (
      body: unknown,
      options?: { status?: number; headers?: Record<string, string>; raw?: boolean },
    ) => void;
  };
  respondWithError: (body: unknown, status?: number) => void;
  respondWithText: (body: string, status?: number) => void;
  lastCall: () => RecordedRequest | undefined;
  restore: () => void;
}

/**
 * Replaces "globalThis.fetch" with the double and returns its handle. One call
 * per test; the previous installation is replaced rather than stacked.
 */
export declare const installFetch: () => FetchDouble;
