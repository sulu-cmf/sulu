// @flow
import {observable} from 'mobx';
import type {Size} from '../../components/Grid';

export type SchemaEntry = {
    label: string,
    type: string,
    size?: Size,
    spaceAfter?: Size,
    items?: Schema,
};

export type Schema = {
    [string]: SchemaEntry,
};

export type ObservableOptions = {
    locale?: observable,
};
