module.exports = {
  root: true,
  env: { browser: true, es2021: true, node: true },
  extends: [
    'plugin:vue/vue3-recommended',
    '@vue/eslint-config-prettier'
  ],
  rules: {
    'vue/multi-word-component-names': 'off',
    'prettier/prettier': 'error'
  }
}