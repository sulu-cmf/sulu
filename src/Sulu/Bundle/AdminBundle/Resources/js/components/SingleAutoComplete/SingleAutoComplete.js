// @flow
import React from 'react';
import equals from 'fast-deep-equal';
import type {ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable, toJS} from 'mobx';
import debounce from 'debounce';
import Input from '../Input';
import AutoCompletePopover from '../AutoCompletePopover';
import singleAutoCompleteStyles from './singleAutoComplete.scss';

const LENS_ICON = 'su-search';
const DEBOUNCE_TIME = 300;

type Props = {|
    disabled: boolean,
    displayProperty: string,
    id?: string,
    loading?: boolean,
    onChange: (value: ?Object) => void,
    onFinish?: () => void,
    /** Called with a debounce when text is entered inside the input */
    onSearch: (query: string) => void,
    placeholder?: string,
    searchProperties: Array<string>,
    suggestions: Array<Object>,
    value: ?Object,
|};

@observer
class SingleAutoComplete extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    @observable labelRef: ElementRef<'label'>;

    @observable inputValue: ?string = this.props.value ? this.props.value[this.props.displayProperty] : undefined;

    overrideValue: boolean = false;

    componentDidUpdate(prevProps: Props) {
        const {
            displayProperty,
            value,
        } = this.props;

        if (!equals(toJS(prevProps.value), toJS(value))){
            this.setInputValue(value ? value[displayProperty] : undefined);
        }
    }

    componentWillUnmount() {
        this.debouncedSearch.clear();
    }

    @action setInputValue(value: ?string) {
        this.inputValue = value;
    }

    @action setLabelRef = (labelRef: ?ElementRef<'label'>) => {
        if (labelRef) {
            this.labelRef = labelRef;
        }
    };

    @computed get popoverMinWidth() {
        return this.labelRef ? this.labelRef.scrollWidth - 10 : 0;
    }

    debouncedSearch = debounce((query: string) => {
        this.props.onSearch(query);
    }, DEBOUNCE_TIME);

    handleSelect = (value: Object) => {
        const {
            displayProperty,
            onChange,
        } = this.props;

        this.setInputValue(value ? value[displayProperty] : undefined);
        onChange(value);
    };

    handleInputChange = (value: ?string) => {
        if (!value) {
            this.props.onChange(undefined);
        }

        this.setInputValue(value);
        this.debouncedSearch(this.inputValue);
    };

    render() {
        const {
            disabled,
            id,
            loading,
            onFinish,
            placeholder,
            searchProperties,
            suggestions,
        } = this.props;
        const {inputValue} = this;
        const showSuggestionList = (!!inputValue && inputValue.length > 0) && suggestions.length > 0;

        // The mousetrap class is required to allow mousetrap catch key bindings for up and down keys
        return (
            <div className={singleAutoCompleteStyles.singleAutoComplete}>
                <Input
                    autocomplete="off"
                    disabled={disabled}
                    icon={LENS_ICON}
                    id={id}
                    inputClass="mousetrap"
                    labelRef={this.setLabelRef}
                    loading={loading}
                    onBlur={onFinish}
                    onChange={this.handleInputChange}
                    placeholder={placeholder}
                    value={inputValue}
                />
                <AutoCompletePopover
                    anchorElement={this.labelRef}
                    minWidth={this.popoverMinWidth}
                    onSelect={this.handleSelect}
                    open={!disabled && showSuggestionList}
                    query={inputValue}
                    searchProperties={searchProperties}
                    suggestions={suggestions}
                />
            </div>
        );
    }
}

export default SingleAutoComplete;
