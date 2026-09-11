import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { beforeEach, describe, it } from "node:test";
import { resetBody } from "../../../../../Build/tests/dom.mjs";
import { installFetch, type FetchDouble } from "../../../../../Build/tests/fetch.mjs";
import {
  IDENTIFIER_PATTERN,
  IconFactory,
  Sizes,
  endpointFrom,
  type IconEndpoint,
} from "@fgtclb/academic-base/frontend/icons.js";

/**
 * The frontend icon factory, driven the way a module on a page drives it.
 *
 * The module keeps one promise per icon for the lifetime of the page - that is
 * its point - and node hands every test in this file the same module instance.
 * So every test asks for identifiers of its own, named after the test, and
 * none depends on what another one left in the cache.
 *
 * The JSON maps below have the shape `<ab:frontendIconMap>` renders, which
 * `FrontendIconMapViewHelperTest` asserts against the really rendered markup.
 */
const endpoint: IconEndpoint = {
  url: "https://example.test/_academic/icons.json",
  version: "0123456789abcdef",
};

const markupOf = (identifier: string, size = "small"): string =>
  `<span class="t3js-icon icon icon-size-${size} icon-${identifier}" data-identifier="${identifier}" aria-hidden="true">`
  + `<span class="icon-markup"><svg viewBox="0 0 16 16" width="1em" height="1em"></svg></span></span>`;

const answerFor = (identifiers: string[], size = "small"): Record<string, string> =>
  Object.fromEntries(identifiers.map((identifier) => [identifier, markupOf(identifier, size)]));

const iconMap = (map: Record<string, string>, attributes = 'data-academic-icons-size="small"'): string =>
  `<script type="application/json" data-academic-icons ${attributes}>${JSON.stringify(map)}</script>`;

/**
 * A constant of a PHP class of this extension, read out of its source: the
 * factory repeats two limits of the server, and these tests are what keeps them
 * in sync.
 */
const phpConstant = (file: string, name: string): string => {
  const source = readFileSync(new URL(`../../Classes/${file}`, import.meta.url), "utf8");
  const match = new RegExp(`const ${name} = (.+);`).exec(source);
  assert.ok(match, `${name} is not declared in ${file}.`);
  return match[1];
};

const requested = (call: { url: string } | undefined): { path: string; i: string[]; s: string | null; v: string | null } => {
  assert.ok(call, "No request was sent.");
  const url = new URL(call.url);
  return {
    path: url.origin + url.pathname,
    i: (url.searchParams.get("i") ?? "").split(","),
    s: url.searchParams.get("s"),
    v: url.searchParams.get("v"),
  };
};

describe("the frontend icon factory", () => {
  let fetch: FetchDouble;

  beforeEach(() => {
    resetBody("");
    fetch = installFetch();
  });

  describe("reading the JSON icon maps of the page", () => {
    it("answers an icon the page carries without a request", async () => {
      resetBody(iconMap(answerFor(["tx-academicbase-test-seeded"])));

      const markup = await new IconFactory().getIcon("tx-academicbase-test-seeded");

      assert.equal(markup, markupOf("tx-academicbase-test-seeded"));
      assert.equal(fetch.calls.length, 0);
    });

    it("reads a map that reached the page after the first icon was asked for", async () => {
      resetBody(iconMap(answerFor(["tx-academicbase-test-first-map"])));
      const factory = new IconFactory();
      await factory.getIcon("tx-academicbase-test-first-map");

      document.body.insertAdjacentHTML("beforeend", iconMap(answerFor(["tx-academicbase-test-later-map"])));

      assert.equal(await factory.getIcon("tx-academicbase-test-later-map"), markupOf("tx-academicbase-test-later-map"));
      assert.equal(fetch.calls.length, 0);
    });

    it("keeps the sizes apart", async () => {
      resetBody(iconMap(answerFor(["tx-academicbase-test-sized"])));
      fetch.respond(answerFor(["tx-academicbase-test-sized"], "large"));

      const markup = await new IconFactory(endpoint).getIcon("tx-academicbase-test-sized", Sizes.large);

      assert.equal(markup, markupOf("tx-academicbase-test-sized", "large"));
      assert.deepEqual(requested(fetch.lastCall()).i, ["tx-academicbase-test-sized"]);
      assert.equal(requested(fetch.lastCall()).s, "large");
    });

    it("reads the size of a map from its attribute", async () => {
      resetBody(iconMap(answerFor(["tx-academicbase-test-map-size"], "medium"), 'data-academic-icons-size="medium"'));

      const markup = await new IconFactory().getIcon("tx-academicbase-test-map-size", Sizes.medium);

      assert.equal(markup, markupOf("tx-academicbase-test-map-size", "medium"));
    });

    it("skips a map that is not JSON and reads the next one", async () => {
      resetBody(
        '<script type="application/json" data-academic-icons data-academic-icons-size="small">{not json</script>'
        + iconMap(answerFor(["tx-academicbase-test-after-broken"])),
      );

      assert.equal(
        await new IconFactory().getIcon("tx-academicbase-test-after-broken"),
        markupOf("tx-academicbase-test-after-broken"),
      );
    });

    it("rejects an icon the page does not carry when there is no endpoint", async () => {
      await assert.rejects(new IconFactory().getIcon("tx-academicbase-test-nowhere"), /no endpoint/);
      assert.equal(fetch.calls.length, 0);
    });
  });

  describe("asking the endpoint", () => {
    it("sends the calls of one microtask as one request, in the order asked for", async () => {
      const identifiers = ["tx-academicbase-test-batch-b", "tx-academicbase-test-batch-a", "tx-academicbase-test-batch-c"];
      fetch.respond(answerFor(identifiers));
      const factory = new IconFactory(endpoint);

      const markups = await Promise.all(identifiers.map((identifier) => factory.getIcon(identifier)));

      assert.equal(fetch.calls.length, 1);
      assert.deepEqual(requested(fetch.lastCall()), {
        path: "https://example.test/_academic/icons.json",
        i: identifiers,
        s: "small",
        v: "0123456789abcdef",
      });
      assert.equal(fetch.lastCall()?.method, "GET");
      assert.equal(fetch.lastCall()?.headers.Accept, "application/json");
      // No cookie: the answer does not depend on a session, and a proxy passes
      // a request with one on to the backend.
      assert.equal(fetch.lastCall()?.credentials, "omit");
      assert.deepEqual(markups, identifiers.map((identifier) => markupOf(identifier)));
    });

    it("sends one request per size", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-per-size-small"]));
      fetch.respond(answerFor(["tx-academicbase-test-per-size-mega"], "mega"));
      const factory = new IconFactory(endpoint);

      await Promise.all([
        factory.getIcon("tx-academicbase-test-per-size-small"),
        factory.getIcon("tx-academicbase-test-per-size-mega", Sizes.mega),
      ]);

      assert.deepEqual(fetch.calls.map((call) => [requested(call).s, requested(call).i]), [
        ["small", ["tx-academicbase-test-per-size-small"]],
        ["mega", ["tx-academicbase-test-per-size-mega"]],
      ]);
    });

    it("splits more than 32 icons into requests the endpoint accepts", async () => {
      const identifiers = Array.from({ length: 40 }, (_, index) => `tx-academicbase-test-many-${index}`);
      fetch.respond(answerFor(identifiers.slice(0, 32)));
      fetch.respond(answerFor(identifiers.slice(32)));

      await new IconFactory(endpoint).prefetch(identifiers);

      assert.deepEqual(fetch.calls.map((call) => requested(call).i.length), [32, 8]);
      assert.deepEqual(fetch.calls.flatMap((call) => requested(call).i), identifiers);
    });

    it("asks for an icon once, however often and by however many factories it is asked for", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-once"]));

      const first = new IconFactory(endpoint).getIcon("tx-academicbase-test-once");
      const second = new IconFactory(endpoint).getIcon("tx-academicbase-test-once");
      await Promise.all([first, second]);
      await new IconFactory(endpoint).getIcon("tx-academicbase-test-once");

      assert.equal(first, second);
      assert.equal(fetch.calls.length, 1);
      assert.deepEqual(requested(fetch.lastCall()).i, ["tx-academicbase-test-once"]);
    });

    it("leaves the version out when the page has none", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-unversioned"]));

      await new IconFactory({ url: endpoint.url, version: "" }).getIcon("tx-academicbase-test-unversioned");

      assert.equal(requested(fetch.lastCall()).v, null);
    });

    it("resolves an endpoint relative to the document", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-relative"]));

      await new IconFactory({ url: "/de/_academic/icons.json", version: "" }).getIcon("tx-academicbase-test-relative");

      assert.equal(requested(fetch.lastCall()).path, "https://example.test/de/_academic/icons.json");
    });

    it("rejects an icon the endpoint leaves out, and does not ask for it again", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-served"]));
      const factory = new IconFactory(endpoint);

      const served = factory.getIcon("tx-academicbase-test-served");
      const refused = factory.getIcon("tx-academicbase-test-refused");

      assert.equal(await served, markupOf("tx-academicbase-test-served"));
      await assert.rejects(refused, /"tx-academicbase-test-refused" \(small\) is not available/);
      await assert.rejects(factory.getIcon("tx-academicbase-test-refused"), /is not available/);
      assert.equal(fetch.calls.length, 1);
    });

    it("rejects the icons of a failed request, and asks again later", async () => {
      fetch.respondWithError({ error: "Parameter \"s\" is not one of default, small, medium, large or mega." }, 400);
      const factory = new IconFactory(endpoint);

      await assert.rejects(factory.getIcon("tx-academicbase-test-retry"), /could not be loaded: HTTP 400/);

      fetch.respond(answerFor(["tx-academicbase-test-retry"]));
      assert.equal(await factory.getIcon("tx-academicbase-test-retry"), markupOf("tx-academicbase-test-retry"));
      assert.equal(fetch.calls.length, 2);
    });

    it("rejects the icons of a request that did not reach the server", async () => {
      // Nothing queued: the double rejects the request the way a network error does.
      await assert.rejects(new IconFactory(endpoint).getIcon("tx-academicbase-test-offline"), /could not be loaded/);
    });

    it("rejects a malformed identifier on its own and never sends it", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-next-to-malformed"]));
      const factory = new IconFactory(endpoint);
      // Identifiers out of the data a module loads: upper case, a comma, a line
      // feed. The endpoint answers a request with any of them with 400 as a
      // whole.
      const malformed = ["Tx-From-Data", "tx-academicbase-test-a,tx-academicbase-test-b", "tx-academicbase-test-lf\n"];

      const valid = factory.getIcon("tx-academicbase-test-next-to-malformed");
      const rejected = malformed.map((identifier) => factory.getIcon(identifier));

      assert.equal(await valid, markupOf("tx-academicbase-test-next-to-malformed"));
      for (const [index, promise] of rejected.entries()) {
        await assert.rejects(promise, new RegExp(`"${malformed[index]}" is not an icon identifier`));
      }
      assert.equal(fetch.calls.length, 1);
      assert.deepEqual(requested(fetch.lastCall()).i, ["tx-academicbase-test-next-to-malformed"]);
    });

    it("gives a request up after ten seconds, and asks again on the next call", async (t) => {
      t.mock.timers.enable({ apis: ["setTimeout"] });
      fetch.respondLater();
      const factory = new IconFactory(endpoint);
      let settled = false;
      const hanging = factory.getIcon("tx-academicbase-test-hanging");
      hanging.then(() => { settled = true; }, () => { settled = true; });
      // The request is sent in the microtask after the call.
      await new Promise((resolve) => setImmediate(resolve));
      assert.equal(fetch.calls.length, 1);

      t.mock.timers.tick(9_999);
      await new Promise((resolve) => setImmediate(resolve));
      assert.equal(settled, false, "The request was given up before ten seconds.");
      t.mock.timers.tick(1);
      await new Promise((resolve) => setImmediate(resolve));
      // Checked before awaiting it: a request that is never given up would
      // otherwise hang the test instead of failing it.
      assert.equal(settled, true, "The request was not given up after ten seconds.");

      await assert.rejects(hanging, /"tx-academicbase-test-hanging" \(small\) could not be loaded: no answer within 10 seconds/);
      fetch.respond(answerFor(["tx-academicbase-test-hanging"]));
      assert.equal(await factory.getIcon("tx-academicbase-test-hanging"), markupOf("tx-academicbase-test-hanging"));
      assert.equal(fetch.calls.length, 2);
    });

    it("rejects an answer that is not an object", async () => {
      fetch.respond(null);

      await assert.rejects(new IconFactory(endpoint).getIcon("tx-academicbase-test-null-answer"), /not an object/);
    });
  });

  describe("prefetch()", () => {
    it("answers the icons it fetched without another request", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-prefetched"]));
      const factory = new IconFactory(endpoint);

      await factory.prefetch(["tx-academicbase-test-prefetched", "tx-academicbase-test-prefetch-refused"]);

      assert.equal(await factory.getIcon("tx-academicbase-test-prefetched"), markupOf("tx-academicbase-test-prefetched"));
      assert.equal(fetch.calls.length, 1);
    });
  });

  describe("getIconElement()", () => {
    it("answers a new element on every call", async () => {
      fetch.respond(answerFor(["tx-academicbase-test-element"]));
      const factory = new IconFactory(endpoint);

      const first = await factory.getIconElement("tx-academicbase-test-element");
      const second = await factory.getIconElement("tx-academicbase-test-element");

      assert.notEqual(first, second);
      for (const element of [first, second]) {
        assert.equal(element.getAttribute("data-identifier"), "tx-academicbase-test-element");
        assert.ok(element.classList.contains("t3js-icon"));
        assert.ok(element.querySelector("svg"));
        assert.equal(element.isConnected, false);
      }
    });
  });

  describe("endpointFrom()", () => {
    it("reads the endpoint the JSON icon map inside an element names", () => {
      const root = resetBody(
        `<div>${iconMap({}, 'data-academic-icons-size="small" data-academic-icons-url="/de/_academic/icons.json" data-academic-icons-version="abc"')}</div>`,
      );

      assert.deepEqual(endpointFrom(root), { url: "/de/_academic/icons.json", version: "abc" });
    });

    it("reads the endpoint an element carries itself", () => {
      const root = resetBody(
        '<div data-academic-icons-url="https://example.test/_academic/icons.json" data-academic-icons-version="abc"></div>',
      ).firstElementChild;
      assert.ok(root);

      assert.deepEqual(endpointFrom(root), { url: "https://example.test/_academic/icons.json", version: "abc" });
    });

    it("answers null where no endpoint is named", () => {
      const root = resetBody(iconMap({}));

      assert.equal(endpointFrom(root), null);
    });
  });

  describe("the limits of the server", () => {
    it("checks identifiers with the pattern of FrontendIconRenderer", () => {
      const php = phpConstant("Imaging/FrontendIconRenderer.php", "IDENTIFIER_PATTERN");
      // '/\A<body>\z/' in PHP is /^<body>$/ in JavaScript without the m flag.
      const body = /^'\/\\A(.+)\\z\/'$/.exec(php);
      assert.ok(body, `The PHP pattern ${php} is no longer anchored with \\A and \\z and without modifiers.`);

      assert.equal(IDENTIFIER_PATTERN.source, `^${body[1]}$`);
      assert.equal(IDENTIFIER_PATTERN.flags, "");
    });

    it("sends as many identifiers in one request as FrontendIconEndpoint accepts", async () => {
      const maximum = Number(phpConstant("Middleware/FrontendIconEndpoint.php", "MAX_IDENTIFIERS"));
      assert.ok(maximum > 0);
      const identifiers = Array.from({ length: maximum + 1 }, (_, index) => `tx-academicbase-test-limit-${index}`);
      fetch.respond(answerFor(identifiers.slice(0, maximum)));
      fetch.respond(answerFor(identifiers.slice(maximum)));

      await new IconFactory(endpoint).prefetch(identifiers);

      assert.deepEqual(fetch.calls.map((call) => requested(call).i.length), [maximum, 1]);
    });
  });
});
