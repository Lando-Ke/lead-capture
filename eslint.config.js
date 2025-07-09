import pluginVue from 'eslint-plugin-vue'
import js from '@eslint/js'

export default [
  // Apply to all JS and Vue files
  {
    files: ['**/*.{js,mjs,vue}'],
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        // Node.js globals
        process: 'readonly',
        // Browser globals
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        // Vitest globals
        describe: 'readonly',
        it: 'readonly',
        expect: 'readonly',
        vi: 'readonly',
        beforeEach: 'readonly',
        afterEach: 'readonly',
        beforeAll: 'readonly',
        afterAll: 'readonly'
      }
    }
  },

  // Base JavaScript rules
  js.configs.recommended,

  // Vue.js specific rules
  ...pluginVue.configs['flat/recommended'],

  // Custom rules
  {
    rules: {
      // General JavaScript rules
      'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      'prefer-const': 'error',
      'no-var': 'error',

      // Vue.js specific rules
      'vue/multi-word-component-names': 'off',
      'vue/no-unused-vars': 'error',
      'vue/component-definition-name-casing': ['error', 'PascalCase'],
      'vue/component-name-in-template-casing': ['error', 'PascalCase'],
      'vue/prop-name-casing': ['error', 'camelCase'],
      'vue/attribute-hyphenation': ['error', 'always'],
      'vue/v-on-event-hyphenation': ['error', 'always'],
      'vue/max-attributes-per-line': ['error', {
        'singleline': { 'max': 3 },
        'multiline': { 'max': 1 }
      }],
      'vue/html-indent': ['error', 2],
      'vue/script-indent': ['error', 2, { 'baseIndent': 0 }],
      'vue/no-spaces-around-equal-signs-in-attribute': 'error',
      'vue/mustache-interpolation-spacing': ['error', 'always'],
      'vue/no-multi-spaces': 'error',
      'vue/html-closing-bracket-spacing': 'error',
      'vue/html-closing-bracket-newline': ['error', {
        'singleline': 'never',
        'multiline': 'always'
      }]
    }
  },

  // Ignore patterns
  {
    ignores: [
      'node_modules/**',
      'public/**',
      'vendor/**',
      'storage/**',
      'bootstrap/cache/**',
      'dist/**',
      '*.min.js'
    ]
  }
] 