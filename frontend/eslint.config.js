import js from '@eslint/js'
import prettier from 'eslint-config-prettier'
import jsdoc from 'eslint-plugin-jsdoc'
import vue from 'eslint-plugin-vue'
import vuetify from 'eslint-plugin-vuetify'
import globals from 'globals'

export default [
  { ignores: ['dist/', 'coverage/', 'playwright-report/', 'test-results/'] },
  js.configs.recommended,
  ...vue.configs['flat/recommended'],
  ...vuetify.configs['flat/base'],
  {
    languageOptions: {
      ecmaVersion: 2023,
      sourceType: 'module',
      globals: { ...globals.browser },
    },
    plugins: { jsdoc },
    rules: {
      // Ningún HTML recibido se inserta como HTML (RNF-F13).
      'vue/no-v-html': 'error',
      // Componentes propios en PascalCase; los de Vuetify, en kebab-case (su convención): no se registran aquí.
      'vue/component-name-in-template-casing': ['error', 'PascalCase', { registeredComponentsOnly: true }],
      'vue/require-default-prop': 'off',
      'no-console': ['error', { allow: ['warn', 'error'] }],
      eqeqeq: ['error', 'always'],
      'prefer-const': 'error',
      // Los tipos de lo que se exporta van documentados (RNF-F17): vue-tsc los comprueba.
      'jsdoc/require-jsdoc': [
        'error',
        { publicOnly: true, require: { FunctionDeclaration: true, ArrowFunctionExpression: false } },
      ],
      'jsdoc/require-param-type': 'error',
      'jsdoc/require-returns-type': 'error',
      'jsdoc/no-undefined-types': 'off',
    },
  },
  {
    files: ['**/*.test.js', 'pruebas/**/*.js', '*.config.js'],
    languageOptions: { globals: { ...globals.node } },
    rules: { 'jsdoc/require-jsdoc': 'off' },
  },
  prettier,
]
