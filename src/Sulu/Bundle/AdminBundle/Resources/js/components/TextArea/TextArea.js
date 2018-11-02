// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import CharacterCounter from '../CharacterCounter';
import textAreaStyles from './textArea.scss';

type Props = {|
    id?: string,
    maxCharacters?: number,
    name?: string,
    onBlur?: () => void,
    onChange: (string) => void,
    placeholder?: string,
    valid: boolean,
    value: ?string,
|};

export default class TextArea extends React.PureComponent<Props> {
    static defaultProps = {
        valid: true,
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value);
    };

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {
            id,
            maxCharacters,
            name,
            placeholder,
            value,
            valid,
        } = this.props;

        const textareaClass = classNames(
            textAreaStyles.textArea,
            {
                [textAreaStyles.error]: !valid,
            }
        );

        return (
            <Fragment>
                <textarea
                    className={textareaClass}
                    id={id}
                    name={name}
                    onBlur={this.handleBlur}
                    onChange={this.handleChange}
                    placeholder={placeholder}
                    value={value || ''}
                />
                {maxCharacters &&
                    <CharacterCounter max={maxCharacters} value={value} />
                }
            </Fragment>
        );
    }
}
