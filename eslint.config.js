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
        afterAll: 'readonly',
        // Laravel/Inertia.js globals
        route: 'readonly',
      },
    },
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
      
      // Disable Vue formatting rules that conflict with Prettier
      'vue/max-attributes-per-line': 'off',
      'vue/html-indent': 'off',
      'vue/script-indent': 'off',
      'vue/html-closing-bracket-spacing': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/html-self-closing': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/multiline-html-element-content-newline': 'off',
      'vue/no-spaces-around-equal-signs-in-attribute': 'off',
      'vue/mustache-interpolation-spacing': 'off',
      'vue/no-multi-spaces': 'off'
    },
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
      '*.min.js',
    ],
  },
]
