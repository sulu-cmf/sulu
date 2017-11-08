// @flow
import type {Element} from 'react';

export type Size = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11 | 12;

export type BaseItemProps = {
    size: Size,
    spaceBefore: Size,
    spaceAfter: Size,
    children: Element<*>,
};
