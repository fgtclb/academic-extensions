/**
 * The recording "fetch" double every test that drives an endpoint installs.
 *
 * Node has a real "fetch", and a test that reached it would go to the network:
 * slow, flaky, and dependent on a TYPO3 instance that the node suite
 * deliberately does not have. This replaces it with a queue of prepared
 * responses and a log of the calls that consumed them.
 *
 * It is a double, not a stub of a library, so it does not live below "stubs/":
 * what it models is the browser API the modules call, and the point of it is
 * the *assertion* - the method, the url, the headers (the "X-Requested-With"
 * one is the CSRF guard of every writing endpoint, so it is asserted, not
 * assumed) and the decoded body of each request.
 *
 * The responses themselves are real "Response" objects, so "response.ok",
 * "response.status" and "response.json()" behave exactly as they do in a
 * browser - including the rejection on a body that is not JSON, which
 * "requestJson()" catches.
 *
 * See "docs/testing/javascript-tests.md".
 */

const originalFetch = globalThis.fetch;

const decodeBody = (body) => {
    if (typeof body !== 'string') {
        // FormData and everything else is handed back untouched: an upload is
        // asserted on its entries, not on a decoded string.
        return body ?? null;
    }
    try {
        return JSON.parse(body);
    } catch {
        return body;
    }
};

/**
 * Installs the double and returns the handle a test drives it with. Call it
 * once per test - the previous installation is replaced, never stacked.
 */
export const installFetch = () => {
    const calls = [];
    const responses = [];

    const makeResponse = (body, { status = 200, headers = { 'Content-Type': 'application/json' }, raw = false } = {}) =>
        new Response(raw ? body : JSON.stringify(body), { status, headers });

    const respond = (body, options) => {
        responses.push(Promise.resolve(makeResponse(body, options)));
    };

    /**
     * Queues a response that stays open until the test settles it.
     *
     * A queued response resolves in the same microtask as the call that takes
     * it, so two "overlapping" requests would in truth run one after the other
     * and a test about the overlap would prove nothing. This is what makes the
     * overlap real.
     */
    const respondLater = () => {
        let settle = () => undefined;
        responses.push(new Promise((resolve) => {
            settle = (body, options) => resolve(makeResponse(body, options));
        }));

        return { settle };
    };

    globalThis.fetch = (url, options = {}) => {
        const headers = { ...(options.headers ?? {}) };
        calls.push({
            url: String(url),
            method: options.method ?? 'GET',
            headers,
            credentials: options.credentials,
            body: decodeBody(options.body),
            rawBody: options.body ?? null,
        });
        const response = responses.shift();
        if (response === undefined) {
            // Never a silent default: a request nobody prepared a response for
            // is a test that does not describe what it is exercising.
            return Promise.reject(new Error(`No response was queued for the request to "${String(url)}".`));
        }

        return response;
    };

    return {
        calls,
        /** Queues a JSON body. The default status is 200. */
        respond,
        /** Queues a response the test settles later, for a real overlap. */
        respondLater,
        /** Queues a JSON body with a failing status, the shape an endpoint refuses with. */
        respondWithError: (body, status = 400) => respond(body, { status }),
        /** Queues a body that is not JSON at all, for the "no decodable result" path. */
        respondWithText: (body, status = 200) => respond(body, { status, headers: { 'Content-Type': 'text/html' }, raw: true }),
        /** The single call a test expects, with the assertion that there was exactly one. */
        lastCall: () => calls[calls.length - 1],
        restore: () => {
            globalThis.fetch = originalFetch;
        },
    };
};
