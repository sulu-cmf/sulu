// @flow
import type {IObservableValue} from 'mobx/lib/mobx';
import type {SchemaOptions} from '../Form/types';

export type TextEditorProps = {|
    disabled: boolean,
    locale: ?IObservableValue<string>,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    onFocus?: (event: {target: EventTarget}) => void,
    options?: ?SchemaOptions,
    value: ?string,
|};
