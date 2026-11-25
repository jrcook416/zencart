// 8. Shipping Export. The Shipping Export page (IEMS- order prefix) is
//    part of the IEMS Legacy Bridge, which is on the live store branches
//    only. v222-iems-dev does not have it, so there is nothing to test
//    here. This placeholder keeps the gap visible in every run.
const { test } = require('./helpers');

test('8 Shipping Export (not on v222-iems-dev)', async () => {
  test.skip(true, 'Shipping Export is not on v222-iems-dev '
    + '(Legacy Bridge, live branches only)');
});
