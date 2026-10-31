// Los colores viven solo en los tokens (RNF-F20): fuera de tokens.css no se escribe un color literal.
export default {
  extends: ['stylelint-config-standard', 'stylelint-config-recommended-vue'],
  rules: {
    'color-no-hex': true,
    'color-named': 'never',
    'function-disallowed-list': ['rgb', 'rgba', 'hsl', 'hsla'],
    'selector-class-pattern': null,
    'custom-property-pattern': null,
    'keyframes-name-pattern': null,
    'no-descending-specificity': null,
    // Los atributos style="--i: 3" de las plantillas no son hojas de estilo.
    'custom-property-empty-line-before': null,
  },
  overrides: [
    {
      files: ['src/compartido/estilos/tokens.css'],
      rules: { 'color-no-hex': null, 'function-disallowed-list': null },
    },
  ],
}
