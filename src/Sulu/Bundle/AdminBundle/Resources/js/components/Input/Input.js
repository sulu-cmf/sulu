// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Loader from '../Loader';
import inputStyles from './input.scss';

const LOADER_SIZE = 20;

type Props = {|
    name?: string,
    icon?: string,
    type: string,
    loading?: boolean,
    placeholder?: string,
    inputRef?: (ref: ?ElementRef<'label'>) => void,
    valid: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string, event: SyntheticEvent<HTMLInputElement>) => void,
    onIconClick?: () => void,
    iconStyle?: Object,
    iconClassName?: string,
|};

export default class Input extends React.PureComponent<Props> {
    static defaultProps = {
        type: 'text',
        valid: true,
    };

    setRef = (ref: ?ElementRef<'label'>) => {
        const {inputRef} = this.props;

        if (!inputRef) {
            return;
        }

        inputRef(ref);
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value || undefined, event);
    };

    handleBlur = () => {
        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    render() {
        const {
            valid,
            icon,
            loading,
            name,
            placeholder,
            onIconClick,
            type,
            value,
            iconStyle,
            iconClassName,
        } = this.props;

        const labelClass = classNames(
            inputStyles.input,
            {
                [inputStyles.error]: !valid,
            }
        );

        const iconClass = classNames(
            inputStyles.icon,
            iconClassName,
            {
                [inputStyles.iconClickable]: (!!icon && !!onIconClick),
            }
        );

        const onIconClickProperties = onIconClick
            ? {
                onClick: onIconClick,
            }
            : {};

        return (
            <label
                className={labelClass}
                ref={this.setRef}
            >
                {!loading && icon &&
                    <div className={inputStyles.prependedContainer}>
                        <Icon {...onIconClickProperties} className={iconClass} name={icon} style={iconStyle} />
                    </div>
                }
                {loading &&
                    <div className={inputStyles.prependedContainer}>
                        <Loader size={LOADER_SIZE} />
                    </div>
                }
                <input
                    name={name}
                    type={type}
                    value={value || ''}
                    placeholder={placeholder}
                    onBlur={this.handleBlur}
                    onChange={this.handleChange}
                />
            </label>
        );
    }
}
