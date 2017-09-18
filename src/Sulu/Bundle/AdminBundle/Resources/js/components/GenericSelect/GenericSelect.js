// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Popover from '../Popover';
import Menu from '../Menu';
import Action from './Action';
import Option from './Option';
import type {OptionSelectedVisualization, SelectChildren, SelectProps} from './types';
import DisplayValue from './DisplayValue';
import genericSelectStyles from './genericSelect.scss';

const HORIZONTAL_OFFSET = -20;
const VERTICAL_OFFSET = 2;

type Props = SelectProps & {
    onSelect: (values: string) => void,
    displayValue: string,
    closeOnSelect: boolean,
    isOptionSelected: (option: Element<typeof Option>) => boolean,
    selectedVisualization?: OptionSelectedVisualization,
};

@observer
export default class GenericSelect extends React.PureComponent<Props> {
    static defaultProps = {
        closeOnSelect: true,
    };

    @observable displayValueRef: ?ElementRef<'button'>;

    @observable selectedOptionRef: ?ElementRef<'li'>;

    @observable open: boolean;

    @action openOptionList = () => {
        this.open = true;
    };

    @action closeOptionList = () => {
        this.open = false;
    };

    @action setDisplayValueRef = (ref: ?ElementRef<'button'>) => {
        if (ref) {
            this.displayValueRef = ref;
        }
    };

    @action setSelectedOptionRef = (ref: ?ElementRef<'li'>, selected: boolean) => {
        if (!this.selectedOptionRef || (ref && selected)) {
            this.selectedOptionRef = ref;
        }
    };

    cloneOption(originalOption: Element<typeof Option>) {
        return React.cloneElement(originalOption, {
            onClick: this.handleOptionClick,
            selected: this.props.isOptionSelected(originalOption),
            selectedVisualization: this.props.selectedVisualization,
            optionRef: this.setSelectedOptionRef,
        });
    }

    cloneAction(originalAction: Element<typeof Action>) {
        return React.cloneElement(originalAction, {
            afterAction: this.closeOptionList,
        });
    }

    cloneChildren(): SelectChildren {
        return React.Children.map(this.props.children, (child: any) => {
            switch (child.type) {
                case Option:
                    child = this.cloneOption(child);
                    break;
                case Action:
                    child = this.cloneAction(child);
                    break;
            }

            return child;
        });
    }

    handleOptionClick = (value: string) => {
        this.props.onSelect(value);

        if (this.props.closeOnSelect) {
            this.closeOptionList();
        }
    };

    handleDisplayValueClick = this.openOptionList;

    handleOptionListClose = this.closeOptionList;

    render() {
        const {
            icon,
            displayValue,
        } = this.props;
        const clonedChildren = this.cloneChildren();

        return (
            <div className={genericSelectStyles.select}>
                <DisplayValue
                    icon={icon}
                    onClick={this.handleDisplayValueClick}
                    displayValueRef={this.setDisplayValueRef}
                >
                    {displayValue}
                </DisplayValue>
                <Popover
                    open={this.open}
                    onClose={this.handleOptionListClose}
                    anchorElement={this.displayValueRef}
                    verticalOffset={VERTICAL_OFFSET}
                    horizontalOffset={HORIZONTAL_OFFSET}
                    centerChildElement={this.selectedOptionRef}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <Menu
                                style={popoverStyle}
                                menuRef={setPopoverElementRef}
                            >
                                {clonedChildren}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    }
}
