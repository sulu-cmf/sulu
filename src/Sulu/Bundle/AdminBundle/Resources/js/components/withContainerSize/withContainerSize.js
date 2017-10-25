// @flow
import {action, observable} from 'mobx';
import type {ComponentType, Element, ElementRef} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import {buildHocDisplayName} from '../../services/react';
import {afterElementsRendered} from '../../services/DOM';
import styles from './withContainerSize.scss';

export default function withContainerSize(Component: ComponentType<*>, containerClass: string = styles.container) {
    @observer
    class WithContainerSizeComponent extends React.Component<*> {
        component: Element<*>;

        container: HTMLElement;

        @observable containerWidth: number = 0;

        @observable containerHeight: number = 0;

        componentDidMount() {
            window.addEventListener('resize', this.handleWindowResize);

            if (typeof this.component.containerDidMount === 'function') {
                afterElementsRendered(this.component.containerDidMount);
            }
        }

        componentWillUnmount() {
            window.removeEventListener('resize', this.handleWindowResize);
        }

        readContainerDimensions = (container: ElementRef<'div'>) => {
            if (!container) {
                return;
            }

            afterElementsRendered(action(() => {
                this.container = container;
                this.containerWidth = container.clientWidth;
                this.containerHeight = container.clientHeight;
            }));
        };

        setComponent = (component: Element<*>) => {
            this.component = component;
        };

        handleWindowResize = () => this.readContainerDimensions(this.container);

        render() {
            const props = {
                ...this.props,
                containerWidth: this.containerWidth,
                containerHeight: this.containerHeight,
                ref: this.setComponent,
            };

            return (
                <div ref={this.readContainerDimensions} className={containerClass}>
                    <Component {...props} />
                </div>
            );
        }
    }

    WithContainerSizeComponent.displayName = buildHocDisplayName('withContainerSize', Component);

    return WithContainerSizeComponent;
}
