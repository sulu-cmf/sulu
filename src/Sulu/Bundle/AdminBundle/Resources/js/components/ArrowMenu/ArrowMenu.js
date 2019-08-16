// @flow
import React, {Fragment} from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Popover from '../Popover';
import SingleItemSection from './SingleItemSection';
import Section from './Section';
import Item from './Item';
import Action from './Action';
import arrowMenuStyles from './arrowMenu.scss';

type Props = {
    anchorElement: Element<*>,
    children: ChildrenArray<Element<*> | false>,
    onClose?: () => void,
    open: boolean,
    refProp: string,
};

const VERTICAL_OFFSET = 20;

@observer
class ArrowMenu extends React.Component<Props> {
    static defaultProps = {
        refProp: 'ref',
    };

    static Section = Section;
    static SingleItemSection = SingleItemSection;
    static Item = Item;
    static Action = Action;

    @observable displayValueRef: ?ElementRef<*>;

    @action setDisplayValueRef = (ref: ?ElementRef<*>) => {
        this.displayValueRef = ref;
    };

    cloneAnchorElement = (anchorElement: Element<*>) => {
        return React.cloneElement(
            anchorElement,
            {
                [this.props.refProp]: this.setDisplayValueRef,
            }
        );
    };

    cloneChildren(children: ChildrenArray<Element<*> | false>) {
        return React.Children.map(children, (child) => {
            if (!child) {
                return null;
            }

            if (child.type === Section) {
                return React.cloneElement(child, {
                    children: this.cloneSection(child),
                });
            } else {
                return child;
            }
        });
    }

    cloneSection(section: Element<typeof Section>) {
        if (!section) {
            return null;
        }

        if (section.props.children){
            return React.Children.map(section.props.children, (child) => {
                if (!child) {
                    return null;
                }

                if (child.type === Action) {
                    return this.cloneAction(child);
                }
                return child;
            });
        }
        return section;
    }

    cloneAction(originalAction: Element<typeof Action>) {
        const {onClose} = this.props;
        return React.cloneElement(originalAction, {
            onAfterAction: onClose,
        });
    }

    render() {
        const {
            anchorElement,
            open,
            onClose,
        } = this.props;

        const clonedAnchorElement = this.cloneAnchorElement(anchorElement);

        return (
            <Fragment>
                {clonedAnchorElement}
                <Popover
                    anchorElement={this.displayValueRef}
                    onClose={onClose}
                    open={open}
                    verticalOffset={VERTICAL_OFFSET}
                >
                    {
                        (setPopoverElementRef, popoverStyle, verticalPosition, horizontalPosition) => {
                            const arrowVerticalPosition = verticalPosition === 'top' ? 'bottom' : 'top';

                            return this.renderMenu(
                                setPopoverElementRef,
                                popoverStyle,
                                arrowVerticalPosition,
                                horizontalPosition
                            );
                        }
                    }
                </Popover>
            </Fragment>
        );
    }

    renderMenu(
        setPopoverElementRef: (ref: ElementRef<*>) => void,
        popoverStyle: Object,
        arrowVerticalPosition: string = 'top',
        arrowHorizontalPosition: string = 'left'
    ) {
        const {
            children,
        } = this.props;

        const clonedChildren = this.cloneChildren(children);

        const arrowClass = classNames(
            arrowMenuStyles.arrow,
            {
                [arrowMenuStyles.top]: arrowVerticalPosition === 'top',
                [arrowMenuStyles.bottom]: arrowVerticalPosition === 'bottom',
                [arrowMenuStyles.left]: arrowHorizontalPosition === 'left',
                [arrowMenuStyles.right]: arrowHorizontalPosition === 'right',
            }
        );

        return (
            <div className={arrowMenuStyles.arrowMenuContainer} ref={setPopoverElementRef} style={popoverStyle}>
                <div className={arrowClass} />
                <div className={arrowMenuStyles.arrowMenu}>
                    {clonedChildren}
                </div>
            </div>
        );
    }
}

export default ArrowMenu;
