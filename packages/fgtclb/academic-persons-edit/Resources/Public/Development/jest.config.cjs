module.exports = {
  clearMocks: true,
  rootDir: "..",
  roots: [
    "<rootDir>/Development",
    "<rootDir>/JavaScript",
  ],
  collectCoverageFrom: [
    "<rootDir>/JavaScript/frontend/**/*.js",
  ],
  coverageDirectory: "<rootDir>/Development/coverage",
  coverageProvider: "v8",
  coverageReporters: ["text", "html"],
  moduleNameMapper: {
    "^@ckeditor/ckeditor5-(basic-styles|editor-classic|essentials|link|list|paragraph)$":
      "<rootDir>/Development/tests/mocks/ckeditor-modules.js",
  },
  restoreMocks: true,
  setupFilesAfterEnv: ["<rootDir>/Development/tests/setup.js"],
  testEnvironment: "jsdom",
  testEnvironmentOptions: {
    url: "https://www.example.test/profile",
  },
  testMatch: ["<rootDir>/Development/tests/**/*.test.js"],
  transform: {
    "^.+\\.js$": "<rootDir>/Development/babel-jest-transformer.cjs",
  },
};
