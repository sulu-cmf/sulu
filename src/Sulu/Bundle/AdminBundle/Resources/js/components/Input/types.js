// @flow
import type {ElementRef} from 'react';

export type InputProps<T: ?string | ?number> = {|
    alignment: 'left' | 'center' | 'right',
    collapsed?: boolean,
    name?: string,
    icon?: string,
    inputClass?: string,
    type: string,
    loading?: boolean,
    placeholder?: string,
    labelRef?: (ref: ?ElementRef<'label'>) => void,
    inputRef?: (ref: ?ElementRef<'input'>) => void,
    inputMode?: 'verbatim' | 'latin' | 'latin-name' | 'latin-prose' | 'full-width-latin' |
        'kana' | 'katakana' | 'numeric' | 'tel' | 'email' | 'url',
    valid: boolean,
    value: ?T,
    maxCharacters?: number,
    maxSegments?: number,
    onBlur?: () => void,
    onChange: (value: ?string, event: SyntheticEvent<HTMLInputElement>) => void,
    onClearClick?: () => void,
    onIconClick?: () => void,
    onKeyPress?: (key: ?string, event: SyntheticKeyboardEvent<HTMLInputElement>) => void,
    segmentDelimiter?: string,
    iconStyle?: Object,
    iconClassName?: string,
    skin?: 'default' | 'dark',
    min?: ?T,
    max?: ?T,
    step?: ?T,
|};
