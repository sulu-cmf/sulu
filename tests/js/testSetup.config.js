// @flow
import Enzyme from 'enzyme';
import Adapter from '@wojtekmaj/enzyme-adapter-react-17';
import '@testing-library/jest-dom';

Enzyme.configure({adapter: new Adapter()});

jest.mock('sulu-admin-bundle/services/Config', () => ({
    endpoints: {
        'config': 'config_url',
        'translations': 'translations_url',
        'loginCheck': 'login_check_url',
        'logout': 'logout_url',
        'profileSettings': 'profile_settings_url',
        'forgotPasswordReset': 'forgot_password_reset_url',
        'resetPassword': 'reset_password',
        'resources': 'resources_url/:resource',
        'routing': 'routing',
    },
    translations: ['en', 'de'],
    fallbackLocale: 'en',
}));

Object.defineProperty(window, 'matchMedia', { // see https://github.com/ckeditor/ckeditor5/issues/16368
    writable: true,
    value: jest.fn().mockImplementation((query) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(), // deprecated
        removeListener: jest.fn(), // deprecated
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    })),
});
