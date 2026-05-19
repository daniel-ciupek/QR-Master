import pluginVue from 'eslint-plugin-vue'
import tsPlugin from '@typescript-eslint/eslint-plugin'
import tsParser from '@typescript-eslint/parser'
import vueParser from 'vue-eslint-parser'

export default [
    {
        ignores: [
            'vendor/**',
            'node_modules/**',
            'public/build/**',
            'resources/js/components/ui/**',
        ],
    },
    // Vue 3 recommended (flat config API, eslint-plugin-vue v10)
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{ts,vue}'],
        plugins: {
            '@typescript-eslint': tsPlugin,
        },
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                parser: tsParser,
                sourceType: 'module',
                extraFileExtensions: ['.vue'],
            },
        },
        rules: {
            ...tsPlugin.configs.recommended.rules,
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'off',
            'vue/html-indent': ['warn', 4],
            'vue/singleline-html-element-content-newline': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/max-attributes-per-line': ['warn', { singleline: 3, multiline: 1 }],
            '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
            '@typescript-eslint/no-explicit-any': 'warn',
        },
    },
]
