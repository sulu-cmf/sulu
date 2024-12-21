// @flow
import idnEmailValidator from '../idnEmailValidator';

test('Normal email address should pass validation', () => {
    expect(idnEmailValidator('hello@example.com')).toBe(true);
});

test('Invalid email address must not pass validation', () => {
    expect(idnEmailValidator('invalid')).toBe(false);
});
