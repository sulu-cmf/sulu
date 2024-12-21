// @flow
import TranslationFieldTransformer from '../../fieldTransformers/TranslationFieldTransformer';

const translationFieldTransformer = new TranslationFieldTransformer();

jest.mock('../../../../utils/Translator/Translator', () => ({
    translate: (value) => {
        return value;
    },
}));

test('Test undefined', () => {
    expect(translationFieldTransformer.transform(undefined, {}))
        .toBe(null);
});

test('Test transform with prefix', () => {
    expect(translationFieldTransformer.transform('<value>', {prefix: 'sulu_admin.test.'}))
        .toBe('sulu_admin.test.<value>');
});
