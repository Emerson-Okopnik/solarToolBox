module.exports = {
  root: true,
  env: {
    node: true,
    browser: true,
    es2022: true,
  },
  extends: [
    "eslint:recommended",
    "plugin:vue/vue3-essential",
    "plugin:vue/vue3-strongly-recommended",
    "plugin:vue/vue3-recommended",
  ],
  plugins: ["vue"],
  parser: "vue-eslint-parser",
  parserOptions: {
    ecmaVersion: 2022,
    sourceType: "module",
  },
  rules: {
    // Vue.js specific rules
    "vue/multi-word-component-names": "off",
    "vue/no-unused-vars": "error",
    "vue/no-multiple-template-root": "off",
    "vue/script-setup-uses-vars": "error",

    // General JavaScript rules
    "no-console": process.env.NODE_ENV === "production" ? "warn" : "off",
    "no-debugger": process.env.NODE_ENV === "production" ? "warn" : "off",
    "no-unused-vars": ["error", { argsIgnorePattern: "^_" }],
  },
  overrides: [
    {
      files: ["src/**/*.vue", "src/**/*.js"],
      rules: {
        // Vue-specific overrides
        "vue/component-definition-name-casing": ["error", "PascalCase"],
        "vue/component-name-in-template-casing": ["error", "PascalCase"],
      },
    },
  ],
  ignorePatterns: ["dist/", "node_modules/"],
}
