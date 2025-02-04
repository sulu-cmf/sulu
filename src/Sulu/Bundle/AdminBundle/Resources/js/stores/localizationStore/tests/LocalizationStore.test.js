// @flow
import localizationStore from '../localizationStore';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Load localizations', () => {
    const localizations = [
        {
            country: '',
            default: '1',
            language: 'en',
            locale: 'en',
            localization: 'en',
            shadow: '',
        },
        {
            country: '',
            default: '0',
            language: 'de',
            locale: 'de',
            localization: 'de',
            shadow: '',
        },
    ];

    localizationStore.setLocalizations(localizations);

    localizationStore.localizations.toBe(localizations);
});
