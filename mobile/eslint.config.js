import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';
import vueParser from 'vue-eslint-parser';

export default [
  // Configuração base do ESLint
  js.configs.recommended,
  ...tseslint.configs.recommended,
  
  // Configurações recomendadas para Vue 3 (strongly-recommended)
  ...pluginVue.configs['flat/strongly-recommended'],
  
  {
    // Arquivos a serem verificados
    files: ['**/*.{js,mjs,cjs,ts,vue}'],
    
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: tseslint.parser,
        ecmaVersion: 'latest',
        sourceType: 'module',
      },
      globals: {
        window: 'readonly',
        document: 'readonly',
        navigator: 'readonly',
        console: 'readonly',
        localStorage: 'readonly',
        sessionStorage: 'readonly',
        fetch: 'readonly',
        process: 'readonly',
        __dirname: 'readonly',
        module: 'readonly',
        require: 'readonly',
        Capacitor: 'readonly',
      },
    },
    
    rules: {
      '@typescript-eslint/no-explicit-any': 'off',
      '@typescript-eslint/no-unused-vars': 'off',
      '@typescript-eslint/no-empty-object-type': 'off',
      'no-unused-vars': 'off',
      'vue/multi-word-component-names': 'off',
      'vue/no-deprecated-slot-attribute': 'off',
      'vue/no-multi-spaces': 'warn',
      'indent': ['error', 2],
      'semi': ['error', 'always'],
      'quotes': ['error', 'single'],
      'comma-spacing': ['error', { 
        before: false, 
        after: true 
      }],
      'vue/max-attributes-per-line': ['error', {
        'singleline': {
          'max': 2
        },      
        'multiline': {
          'max': 1
        }
      }],
      
      'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
    },
  },
  
  {
    // Ignorar arquivos
    ignores: [
      'node_modules/**',
      'dist/**',
      'build/**',
      '.capacitor/**',
      'android/**',
      'ios/**',
      'coverage/**',
      '.cypress/**',
      'tests/e2e/**',
      'cypress.config.ts'
    ],
  },
];
