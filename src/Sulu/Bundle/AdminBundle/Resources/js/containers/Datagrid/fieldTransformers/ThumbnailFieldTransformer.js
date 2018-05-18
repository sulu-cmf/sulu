// @flow
import React from 'react';
import log from 'loglevel';
import type {Node} from 'react';
import type {FieldTransformer} from '../types';

const IMAGE_FORMAT = 'sulu-40x40';

export default class ThumbnailFieldTransformer implements FieldTransformer {
    transform(value: *): Node {
        if (!value) {
            return undefined;
        }

        if (typeof value !== 'object') {
            log.error('Invalid type given: "' + typeof value + '". "object" is needed.');

            return undefined;
        }

        if (!value.hasOwnProperty(IMAGE_FORMAT)) {
            log.error('Object needs property "' + IMAGE_FORMAT + '".');

            return undefined;
        }

        return <img alt={value.alt} src={value[IMAGE_FORMAT]} />;
    }
}
