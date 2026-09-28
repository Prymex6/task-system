import js from '@eslint/js'
import ts from 'typescript-eslint'
import vue from 'eslint-plugin-vue'
import prettier from 'eslint-config-prettier'
import globals from 'globals'

export default [
  {
    ignores: [
      'public/build/**',
      'vendor/**',
      'node_modules/**',
      'storage/**',
      'tests/e2e/.output/**',
      'tests/e2e/.report/**',
    ],
  },

  js.configs.recommended,
  ...ts.configs.recommended,
  ...vue.configs['flat/recommended'],
  prettier,

  {
    languageOptions: {
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        // Ziggy puts route() on the window; Echo and Pusher are set up the
        // same way from bootstrap.js.
        route: 'readonly',
        Echo: 'readonly',
        Pusher: 'readonly',
      },
    },
    rules: {
      // The manager panel has screens whose markup is genuinely deeper than
      // three levels. Enforcing a limit there would mean splitting files to
      // satisfy a linter rather than a reader.
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': 'off',
      'vue/html-indent': 'off',
      'vue/html-closing-bracket-newline': 'off',
      'vue/attributes-order': 'off',
      'vue/first-attribute-linebreak': 'off',

      // Laravel's paginator hands back labels that are markup, and every
      // list here renders them through Inertia's <Link>. That content comes
      // from the framework, never from a person, so this reports rather than
      // blocks — a v-html that is not the paginator still shows up here.
      'vue/no-v-text-v-html-on-component': 'warn',

      // Inertia names a page after its path, so most page components are
      // called Index, Show or Form. The rule exists to stop a component name
      // colliding with an HTML element, which a path-named page cannot.
      'vue/multi-word-component-names': 'off',

      // Reported rather than hidden: an unused variable is usually a
      // leftover and seeing them is the point, but it should not stop a
      // build over something nobody is looking at yet.
      'no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
      '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
    },
  },

  {
    // The service worker runs in a worker, where `self` exists and `window`
    // does not.
    files: ['public/sw.js', 'public/sw-push.js'],
    languageOptions: {
      globals: { ...globals.serviceworker },
    },
  },

  {
    // Build configuration, the maintenance scripts and the E2E suite run in
    // Node, not a browser.
    files: ['*.config.js', '*.config.ts', 'tests/e2e/**/*.ts', 'tests/e2e/**/*.js'],
    languageOptions: {
      globals: { ...globals.node },
    },
    rules: {
      '@typescript-eslint/no-explicit-any': 'warn',
    },
  },
]
