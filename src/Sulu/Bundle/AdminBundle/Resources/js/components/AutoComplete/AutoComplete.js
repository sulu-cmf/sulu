// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import debounce from 'debounce';
import Input from '../Input';
import AutoCompletePopover from './AutoCompletePopover';
import autoCompleteStyles from './autoComplete.scss';

const LENS_ICON = 'su-search';
const DEBOUNCE_TIME = 300;

type Props = {|
    displayProperty: string,
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
export default class AutoComplete extends React.Component<Props> {
    @observable labelRef: ElementRef<'label'>;

    @observable inputValue: ?string = this.props.value ? this.props.value[this.props.displayProperty] : undefined;

    overrideValue: boolean = false;

    componentDidUpdate() {
        if (this.overrideValue) {
            const {
                displayProperty,
                value,
            } = this.props;
            this.overrideValue = false;
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

    debouncedSearch = debounce((query: string) => {
        this.props.onSearch(query);
    }, DEBOUNCE_TIME);

    handleSelect = (value: Object) => {
        this.overrideValue = true;
        this.props.onChange(value);
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
            loading,
            onFinish,
            placeholder,
            searchProperties,
            suggestions,
        } = this.props;
        const {inputValue} = this;
        const showSuggestionList = (!!inputValue && inputValue.length > 0) && suggestions.length > 0;

        return (
            <div className={autoCompleteStyles.autoComplete}>
                <Input
                    icon={LENS_ICON}
                    value={inputValue}
                    loading={loading}
                    labelRef={this.setLabelRef}
                    onChange={this.handleInputChange}
                    onBlur={onFinish}
                    placeholder={placeholder}
                />
                <AutoCompletePopover
                    anchorElement={this.labelRef}
                    open={showSuggestionList}
                    onSelect={this.handleSelect}
                    query={inputValue}
                    searchProperties={searchProperties}
                    suggestions={suggestions}
                />
            </div>
        );
    }
}
