import { readFile } from "node:fs/promises";

const productionPackageUrl = new URL("../../JavaScript/package.json", import.meta.url);
const productionPackage = JSON.parse(await readFile(productionPackageUrl, "utf8"));

if (productionPackage.type !== "module") {
  throw new Error(
    "Resources/Public/JavaScript/package.json must contain type=module before Jest can load the production sources.",
  );
}

await import("../../JavaScript/frontend/profile/common.js");

process.stdout.write("Production JavaScript ES-module scope: OK\n");
