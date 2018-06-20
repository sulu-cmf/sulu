// @flow
import type {Size} from '../../components/Grid';
import FormStore from './stores/FormStore';

export type SchemaType = {
    key: string,
    title: string,
};

export type SchemaTypes = {[key: string]: SchemaType};

export type Type = {
    title: string,
    form: Schema,
};
export type Types = {[key: string]: Type};

export type Tag = {
    name: string,
};

export type SchemaEntry = {
    items?: Schema,
    label?: string,
    maxOccurs?: number,
    minOccurs?: number,
    options?: Object,
    required?: boolean,
    size?: Size,
    spaceAfter?: Size,
    tags?: Array<Tag>,
    type: string,
    types?: Types,
};

export type Schema = {
    [string]: SchemaEntry,
};

export type FinishFieldHandler = (formStore: FormStore, schemaPath: string) => void;
