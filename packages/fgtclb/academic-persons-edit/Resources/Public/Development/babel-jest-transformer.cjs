const { createTransformer } = require("babel-jest");

module.exports = createTransformer({
  presets: [
    [
      require.resolve("@babel/preset-env"),
      {
        modules: "commonjs",
        targets: {
          node: "current",
        },
      },
    ],
  ],
});
