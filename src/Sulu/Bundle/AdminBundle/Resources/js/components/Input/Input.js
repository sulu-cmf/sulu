// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import CharacterCounter from '../CharacterCounter';
import Icon from '../Icon';
import Loader from '../Loader';
import SegmentCounter from '../SegmentCounter';
import inputStyles from './input.scss';
import type {InputProps} from './types';

const LOADER_SIZE = 20;

export default class Input<T: ?string | ?number> extends React.PureComponent<InputProps<T>> {
    static defaultProps = {
        collapsed: false,
        type: 'text',
        skin: 'default',
        valid: true,
    };

    setInputRef = (ref: ?ElementRef<'input'>) => {
        const {inputRef} = this.props;

        if (!inputRef) {
            return;
        }

        inputRef(ref);
    };

    setLabelRef = (ref: ?ElementRef<'label'>) => {
        const {labelRef} = this.props;

        if (!labelRef) {
            return;
        }

        labelRef(ref);
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value || undefined, event);
    };

    handleKeyPress = (event: SyntheticKeyboardEvent<HTMLInputElement>) => {
        const {onKeyPress} = this.props;

        if (onKeyPress) {
            onKeyPress(event.key || undefined, event);
        }
    };

    render() {
        const {
            inputClass,
            valid,
            icon,
            loading,
            collapsed,
            maxCharacters,
            maxSegments,
            name,
            placeholder,
            onBlur,
            onIconClick,
            onClearClick,
            onKeyPress,
            segmentDelimiter,
            type,
            value,
            iconStyle,
            iconClassName,
            inputRef,
            labelRef,
            skin,
            min,
            max,
            step,
        } = this.props;

        const labelClass = classNames(
            inputStyles.input,
            inputStyles[skin],
            {
                [inputStyles.error]: !valid,
                [inputStyles.collapsed]: collapsed,
                [inputStyles.hasAppendIcon]: onClearClick,
            }
        );

        const iconClass = classNames(
            inputStyles.icon,
            inputStyles[skin],
            iconClassName,
            {
                [inputStyles.iconClickable]: (!!icon && !!onIconClick),
                [inputStyles.collapsed]: collapsed,
            }
        );

        const prependContainerClass = classNames(
            inputStyles.prependedContainer,
            inputStyles[skin],
            {
                [inputStyles.collapsed]: collapsed,
            }
        );

        return (
            <Fragment>
                <label
                    className={labelClass}
                    ref={labelRef ? this.setLabelRef : undefined}
                >
                    {!loading && icon &&
                        <div className={prependContainerClass}>
                            <Icon
                                onClick={onIconClick ? onIconClick : undefined}
                                className={iconClass}
                                name={icon}
                                style={iconStyle}
                            />
                        </div>
                    }

                    {loading &&
                        <div className={prependContainerClass}>
                            <Loader size={LOADER_SIZE} />
                        </div>
                    }

                    <input
                        className={inputClass}
                        ref={inputRef ? this.setInputRef : undefined}
                        name={name}
                        type={type}
                        value={value == null ? '' : value}
                        placeholder={placeholder}
                        onBlur={onBlur}
                        onChange={this.handleChange}
                        onKeyPress={onKeyPress ? this.handleKeyPress : undefined}
                        min={min}
                        max={max}
                        step={step}
                    />

                    {!collapsed && !!value && onClearClick &&
                        <div className={inputStyles.appendContainer}>
                            <Icon
                                onClick={onClearClick ? onClearClick : undefined}
                                className={iconClass}
                                name="su-times"
                                style={iconStyle}
                            />
                        </div>
                    }
                </label>
                {maxCharacters &&
                    <CharacterCounter max={maxCharacters} value={value} />
                }
                {segmentDelimiter && maxSegments &&
                    <SegmentCounter
                        delimiter={segmentDelimiter}
                        max={maxSegments}
                        value={value ? value.toString() : undefined}
                    />
                }
            </Fragment>
        );
    }
}
