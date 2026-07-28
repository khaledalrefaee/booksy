import globals from 'globals';

/**
 * Lint rules for the hand-written browser scripts in public/backend/assets/js.
 *
 * These files are classic scripts (no bundler, no modules) loaded straight by
 * Blade, so they are linted as `script` sources sharing one global scope.
 *
 * The rule that earns its keep here is `no-undef`: this codebase has shipped
 * calls to functions that never existed (`bkDraftDiscard()`), which `node -c`
 * cannot see because the syntax is perfectly valid. That class of bug must be
 * impossible to merge.
 */
export default [
    {
        /* Only OUR code. public/backend/assets/js also holds vendored libraries
           (ace, apexcharts, tinymce, jQuery plugins) — linting those is noise. */
        files: [
            'public/backend/assets/js/appointments/**/*.js',
        ],
        languageOptions: {
            ecmaVersion: 2020,
            sourceType: 'script',
            globals: {
                ...globals.browser,
                /* libraries loaded by the layout / page before our scripts */
                FullCalendar: 'readonly',
                Swal: 'readonly',
                bootstrap: 'readonly',
                Echo: 'readonly',
                Pusher: 'readonly',
                /* cross-script helpers published by company partials */
                bkToast: 'readonly',
                bkConfirm: 'readonly',
                bkConfirmDelete: 'readonly',
                /* config bridges injected by Blade (data, never behaviour) */
                BKV2: 'readonly',
                BK: 'readonly',
            },
        },
        linterOptions: {
            reportUnusedDisableDirectives: true,
        },
        rules: {
            /* ── the ones that catch real, shipped bugs ── */
            'no-undef': 'error',                 // bkDraftDiscard() — the whole reason this exists
            'no-unused-vars': ['warn', { args: 'none', varsIgnorePattern: '^_' }],
            'no-redeclare': 'error',             // CREATE_URL was declared twice
            'no-dupe-keys': 'error',
            'no-dupe-args': 'error',
            'no-func-assign': 'error',
            'no-cond-assign': 'error',
            'no-unreachable': 'error',
            'no-fallthrough': 'error',
            'no-self-assign': 'error',
            'no-self-compare': 'error',
            'no-constant-condition': 'error',
            'valid-typeof': 'error',
            'use-isnan': 'error',

            /* ── async correctness: the stuck-spinner class ── */
            'no-async-promise-executor': 'error',
            'require-atomic-updates': 'error',

            /* ── hygiene, kept as warnings so they never block a fix ── */
            'no-empty': ['warn', { allowEmptyCatch: true }],
            eqeqeq: ['warn', 'smart'],
            'no-var': 'off',                     // these files are deliberately ES5-style
        },
    },
];
