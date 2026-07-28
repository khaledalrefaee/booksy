import globals from 'globals';
/* Throwaway config: lints the RENDERED page JS dumped to storage/app/lint
   by AppointmentsPageJsTest. Delete once the JS is extracted from Blade. */
export default [{
  files: ['**/*.js'],
  languageOptions: {
    ecmaVersion: 2020, sourceType: 'script',
    globals: { ...globals.browser, FullCalendar:'readonly', Swal:'readonly', bootstrap:'readonly',
               bkToast:'readonly', bkConfirm:'readonly', bkConfirmDelete:'readonly',
               Echo:'readonly', Pusher:'readonly', axios:'readonly' },
  },
  rules: { 'no-undef':'error', 'no-redeclare':'error', 'no-dupe-keys':'error',
           'no-func-assign':'error', 'no-unreachable':'error', 'no-self-assign':'error' },
}];
