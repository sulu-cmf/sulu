// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import CharacterCounter from '../CharacterCounter';
import textAreaStyles from './textArea.scss';

type Props = {|
    disabled: boolean,
    id?: string,
    maxCharacters?: number,
    name?: string,
    onBlur?: () => void,
    onChange: (?string) => void,
    onFocus?: (event: Event) => void,
    placeholder?: string,
    rows?: number,
    valid: boolean,
    value: ?string,
|};

export default class TextArea extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value || undefined);
    };

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    handleFocus = (event: Event) => {
        const {onFocus} = this.props;

        if (onFocus) {
            onFocus(event);
        }
    };

    render() {
        const {
            id,
            disabled,
            maxCharacters,
            name,
            placeholder,
            rows,
            value,
            valid,
        } = this.props;

        const textareaClass = classNames(
            textAreaStyles.textArea,
            {
                [textAreaStyles.error]: !valid,
                [textAreaStyles.disabled]: disabled,
            }
        );

        return (
            <Fragment>
                <textarea
                    className={textareaClass}
                    disabled={disabled}
                    id={id}
                    name={name}
                    onBlur={this.handleBlur}
                    onChange={this.handleChange}
                    onFocus={this.handleFocus}
                    placeholder={placeholder}
                    rows={rows}
                    value={value || ''}
                />
                {maxCharacters &&
                    <CharacterCounter max={maxCharacters} value={value} />
                }
            </Fragment>
        );
    }
}
