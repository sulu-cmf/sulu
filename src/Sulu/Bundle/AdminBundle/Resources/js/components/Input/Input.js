// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import CharacterCounter from '../CharacterCounter';
import Icon from '../Icon';
import Loader from '../Loader';
import SegmentCounter from '../SegmentCounter';
import inputStyles from './input.scss';
import type {ElementRef} from 'react';
import type {InputProps} from './types';

const LOADER_SIZE = 20;

export default class Input<T: ?string | ?number> extends React.PureComponent<InputProps<T>> {
    static defaultProps = {
        alignment: 'left',
        autoFocus: false,
        collapsed: false,
        disabled: false,
        skin: 'default',
        type: 'text',
        valid: true,
    };

    setInputRef = (ref: ?ElementRef<'input'>) => {
        const {inputRef} = this.props;

        if (!inputRef) {
            return;
        }

        inputRef(ref);
    };

    setInputContainerRef = (ref: ?ElementRef<*>) => {
        const {inputContainerRef} = this.props;

        if (!inputContainerRef) {
            return;
        }

        inputContainerRef(ref);
    };

    handleChange = (event: SyntheticEvent<HTMLInputElement>) => {
        this.props.onChange(event.currentTarget.value || undefined, event);
    };

    handleFocus = (event: Event) => {
        const {onFocus} = this.props;

        if (onFocus) {
            onFocus(event);
        }
    };

    handleKeyPress = (event: SyntheticKeyboardEvent<HTMLInputElement>) => {
        const {onKeyPress} = this.props;

        if (onKeyPress) {
            onKeyPress(event.key || undefined, event);
        }
    };

    render() {
        const {
            alignment,
            autocomplete,
            autoFocus,
            headline,
            id,
            inputClass,
            valid,
            disabled,
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
            inputMode,
            inputRef,
            inputContainerRef,
            skin,
            min,
            max,
            step,
        } = this.props;

        const inputContainerClass = classNames(
            inputStyles.input,
            inputStyles[skin],
            inputStyles[alignment],
            {
                [inputStyles.error]: !valid,
                [inputStyles.disabled]: disabled,
                [inputStyles.collapsed]: collapsed,
                [inputStyles.hasAppendIcon]: onClearClick,
                [inputStyles.headline]: headline,
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
                <div
                    className={inputContainerClass}
                    ref={inputContainerRef ? this.setInputContainerRef : undefined}
                >
                    {!loading && icon &&
                        <div className={prependContainerClass}>
                            <Icon
                                className={iconClass}
                                name={icon}
                                onClick={onIconClick ? onIconClick : undefined}
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
                        autoComplete={autocomplete}
                        autoFocus={autoFocus}
                        className={inputClass}
                        disabled={disabled}
                        id={id}
                        inputMode={inputMode}
                        max={max}
                        min={min}
                        name={name}
                        onBlur={onBlur}
                        onChange={this.handleChange}
                        onFocus={this.handleFocus}
                        onKeyPress={onKeyPress ? this.handleKeyPress : undefined}
                        placeholder={placeholder}
                        ref={inputRef ? this.setInputRef : undefined}
                        step={step}
                        type={type}
                        value={value == null ? '' : value}
                    />

                    {!collapsed && !!value && onClearClick &&
                        <div className={inputStyles.appendContainer}>
                            <Icon
                                className={iconClass}
                                name="su-times"
                                onClick={onClearClick ? onClearClick : undefined}
                                style={iconStyle}
                            />
                        </div>
                    }
                </div>
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
