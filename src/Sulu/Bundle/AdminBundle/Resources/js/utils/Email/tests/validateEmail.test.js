// @flow
import validateEmail from '../validateEmail';

test('The valid email addresses', () => {
    expect(validateEmail('example@sulu.io')).toBe(true);
    expect(validateEmail('example@example.org')).toBe(true);
    expect(validateEmail('example@localhost')).toBe(true);
    expect(validateEmail('0123@domain123.localhost')).toBe(true);
    expect(validateEmail('some.name_more-symbols123+postfix@localhost')).toBe(true);
    expect(validateEmail('some-ip@127.0.0.1')).toBe(true);
});

test('The invalid email addresses', () => {
    expect(validateEmail(null)).toBe(false);
    expect(validateEmail('example')).toBe(false);
    expect(validateEmail('example@')).toBe(false);
    expect(validateEmail('example@localhost@')).toBe(false);
});
