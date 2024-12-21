// @flow
import {translate} from '../../../utils';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

export default class TranslationFieldTransformer implements FieldTransformer {
    transform(value: *, parameters: { [string]: any }): Node {
        if (value === undefined) {
            return null;
        }

        const {prefix = ''} = parameters;

        return translate(prefix + value);
    }
}
