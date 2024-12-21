// @flow
import Router from '../../services/Router';
import FormInspector from './FormInspector';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ColSpan} from '../../components/Grid';

export type SchemaType = {
    key: string,
    title: string,
};

export type SchemaTypes = {
    defaultType: ?string,
    types: {[key: string]: SchemaType},
};

export type Tag = {
    name: string,
    priority?: number,
};

export type Type = {
    form: Schema,
    title: string,
};

export type Types = {[key: string]: Type};

export type PropertyError = {
    keyword: string,
    parameters: {[key: string]: mixed},
};

export type BlockError = Array<?{[key: string]: Error}>;

export type Error = BlockError | PropertyError;

export type ErrorCollection = {[key: string]: Error};

export type SchemaOption = {
    infoText?: string,
    name: string | number,
    title?: string,
    value?: ?string | number | boolean | Array<SchemaOption>,
};

export type SchemaOptions = {[key: string]: SchemaOption | typeof undefined};

export type SchemaEntry = {
    colSpan?: ColSpan,
    defaultType?: string,
    description?: string,
    disabledCondition?: string,
    items?: Schema,
    label?: string,
    maxOccurs?: number,
    minOccurs?: number,
    onInvalid?: string,
    options?: SchemaOptions,
    required?: boolean,
    spaceAfter?: ColSpan,
    tags?: Array<Tag>,
    type: string,
    types?: Types,
    visibleCondition?: string,
};

export type Schema = {[string]: SchemaEntry};

export type FinishFieldHandler = (dataPath: string, schemaPath: string) => void;

export type SaveHandler = (action: ?string | {[string]: any}) => void;

export type ChangeContext = {
    isDefaultValue?: boolean,
    isServerValue?: boolean,
};

export type ConditionDataProvider = (
    data: {[string]: any},
    dataPath: ?string,
    formInspector: FormInspector,
) => {[string]: any};

export interface FormStoreInterface {
    +change: (dataPath: string, value: mixed, context?: ChangeContext) => void,
    +changeMultiple: (values: {[dataPath: string]: mixed}, context?: ChangeContext) => void,
    +changeType: (type: string, context?: ChangeContext) => void,
    // Only exists in one implementation, therefore optional. Maybe we can remove that definition one day...
    +copyFromLocale?: (string, Object) => Promise<*>,
    +data: {[string]: any},
    +destroy: () => void,
    dirty: boolean,
    +errors: Object,
    +finishField: (dataPath: string) => void,
    +forbidden: boolean,
    +getPathsByTag: (tagName: string) => Array<string>,
    +getSchemaEntryByPath: (schemaPath: string) => ?SchemaEntry,
    +getValueByPath: (dataPath: string) => mixed,
    +getValuesByTag: (tagName: string) => Array<mixed>,
    +hasErrors: boolean,
    +hasInvalidType: boolean,
    +id: ?string | number,
    +isFieldModified: (dataPath: string) => boolean,
    +loading: boolean,
    +locale: ?IObservableValue<string>,
    +metadataOptions: ?{[string]: any},
    +notFound: boolean,
    +options: SchemaOptions,
    +resourceKey: ?string,
    +schema: Object,
    +types: {[key: string]: SchemaType},
    +unexpectedError: boolean,
    +validate: () => boolean,
}

export type FieldTypeProps<T> = {|
    data: Object,
    dataPath: string,
    defaultType: ?string,
    disabled: ?boolean,
    error: ?Error | ErrorCollection,
    fieldTypeOptions: Object,
    formInspector: FormInspector,
    label: ?string,
    maxOccurs: ?number,
    minOccurs: ?number,
    onChange: (value: T, context?: ChangeContext) => void,
    onFinish: (subDataPath: ?string, subSchemaPath: ?string) => void,
    onFocus?: (target: EventTarget) => void,
    onSuccess: ?() => void,
    router: ?Router,
    schemaOptions: SchemaOptions,
    schemaPath: string,
    showAllErrors: boolean,
    types: ?Types,
    value: ?T,
|};
