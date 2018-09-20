// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import type {ChildrenArray, Element} from 'react';
import {translate} from '../../utils/index';
import Item from './Item';
import matrixStyles from './matrix.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    name: string,
    onChange?: (name: string, value: {[string]: boolean}) => void,
    values: {[string]: boolean},
};

@observer
export default class Row extends React.Component<Props> {
    static defaultProps = {
        values: {},
    };

    @computed get showDeactiveAllButton(): boolean {
        const {values} = this.props;
        for (const value in values) {
            if (values[value] === true) {
                return true;
            }
        }

        return false;
    }

    handleChange = (itemName: string, value: boolean) => {
        const {
            name,
            onChange,
            values,
        } = this.props;

        if (!onChange) {
            return;
        }

        values[itemName] = value;

        onChange(name, values);
    };

    cloneItems = (originalItems: ChildrenArray<Element<typeof Item>>) => {
        const values = this.props.values;
        return React.Children.map(originalItems, (item, index) => React.cloneElement(
            item,
            {
                ...item.props,
                key: `matrix-item-${index}`,
                onChange: this.handleChange,
                value: values[item.props.name],
            }
        ));
    };

    handleDeactivateAllButtonClicked = () => {
        this.handleAllButtonClicked(false);
    };

    handleActivateAllButtonClicked = () => {
        this.handleAllButtonClicked(true);
    };

    handleAllButtonClicked = (newValue: boolean) => {
        const {
            name,
            onChange,
            values,
        } = this.props;

        if (!onChange) {
            return;
        }

        for (const value in values) {
            values[value] = newValue;
        }

        onChange(name, values);
    };

    renderAllButton() {
        let clickHandler = this.handleDeactivateAllButtonClicked;
        let translation = 'sulu_admin.deactive_all';

        if (!this.showDeactiveAllButton) {
            clickHandler = this.handleActivateAllButtonClicked;
            translation = 'sulu_admin.activate_all';
        }

        return (
            <span className={matrixStyles.rowButton} onClick={clickHandler}>
                {translate(translation)}
            </span>
        );
    }

    render() {
        const {
            children,
            name,
        } = this.props;

        return (
            <div className={matrixStyles.row}>
                <div>{name}</div>
                <div>
                    {this.cloneItems(children)}
                    {this.renderAllButton()}
                </div>
            </div>
        );
    }
}
