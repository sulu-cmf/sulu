// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import withContainerSize from '../withContainerSize';
import ModifiableRectangle from './ModifiableRectangle';
import PositionNormalizer from './normalizers/PositionNormalizer';
import RatioNormalizer from './normalizers/RatioNormalizer';
import RoundingNormalizer from './normalizers/RoundingNormalizer';
import SizeNormalizer from './normalizers/SizeNormalizer';
import withPercentageValues from './withPercentageValues';
import rectangleSelectionStyles from './rectangleSelection.scss';
import type {Normalizer, RectangleChange, SelectionData} from './types';
import type {Node} from 'react';

type Props = {
    backdrop: boolean,
    children?: Node,
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    label?: string,
    minHeight: number | typeof undefined,
    minSizeNotification: boolean,
    minWidth: number | typeof undefined,
    onChange: (s: ?SelectionData) => void,
    onFinish?: () => void,
    round: boolean,
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

@observer
class RawRectangleSelectionComponent extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        minHeight: undefined,
        minSizeNotification: true,
        minWidth: undefined,
        round: true,
        usePercentageValues: false,
    };

    @computed get value() {
        const {value} = this.props;

        if (!value) {
            return this.maximumSelection;
        }

        return value;
    }

    componentDidMount() {
        this.setInitialValue();
    }

    setInitialValue = () => {
        const {containerHeight, containerWidth, onChange, value} = this.props;

        if (!containerHeight || !containerWidth) {
            return;
        }

        if (!value) {
            onChange(this.value);
        }
    };

    static createNormalizers(props: Props): Array<Normalizer> {
        const {
            containerWidth,
            containerHeight,
            minWidth,
            minHeight,
            round,
        } = props;

        if (!containerWidth || !containerHeight) {
            return [];
        }

        const normalizers = [
            new SizeNormalizer(
                containerWidth,
                containerHeight,
                minWidth,
                minHeight
            ),
        ];

        if (minWidth && minHeight) {
            // It's important that the RatioNormalizer is added before the PositionNormalizer, because it may alter the
            // width and height of the selection and the PositionNormalizer needs the final calculated width and height
            // to correctly calculate all valid positions
            normalizers.push(
                new RatioNormalizer(
                    containerWidth,
                    containerHeight,
                    minWidth,
                    minHeight
                )
            );
        }

        normalizers.push(
            new PositionNormalizer(
                containerWidth,
                containerHeight
            )
        );

        if (round) {
            normalizers.push(new RoundingNormalizer());
        }

        return normalizers;
    }

    @computed get normalizers() {
        return RawRectangleSelectionComponent.createNormalizers(this.props);
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    @computed get maximumSelection(): SelectionData {
        const {containerWidth, containerHeight} = this.props;

        return this.normalize(
            this.centerSelection(
                this.normalize({
                    width: containerWidth,
                    height: containerHeight,
                    left: 0,
                    top: 0,
                })
            )
        );
    }

    centerSelection(selection: SelectionData): SelectionData {
        const {containerWidth, containerHeight} = this.props;

        if (selection.width < containerWidth) {
            selection.left = (containerWidth / 2) - (selection.width / 2);
        }

        if (selection.height < containerHeight) {
            selection.top = (containerHeight / 2) - (selection.height / 2);
        }

        return selection;
    }

    handleRectangleDoubleClick = () => {
        const {onChange} = this.props;

        onChange(this.maximumSelection);
    };

    handleRectangleChange = (change: RectangleChange) => {
        const {value} = this;
        const {onChange} = this.props;

        onChange(this.normalize({
            left: value.left + change.left,
            top: value.top + change.top,
            height: value.height + change.height,
            width: value.width + change.width,
        }));
    };

    render() {
        const {
            backdrop,
            children,
            containerHeight,
            containerWidth,
            disabled,
            label,
            minHeight,
            minSizeNotification,
            minWidth,
            onFinish,
        } = this.props;
        const {height, left, top, width} = this.value;

        let backdropSize = 0;
        if (backdrop && containerHeight && containerWidth) {
            backdropSize = Math.max(containerHeight, containerWidth);
        }

        const minSizeReached = minSizeNotification && height <= (minHeight || 0) && width <= (minWidth || 0);

        const rectangle = (
            <ModifiableRectangle
                backdropSize={backdropSize}
                disabled={disabled}
                height={height}
                label={label}
                left={left}
                minSizeReached={minSizeReached}
                onChange={this.handleRectangleChange}
                onDoubleClick={this.handleRectangleDoubleClick}
                onFinish={onFinish}
                top={top}
                width={width}
            />
        );

        if (children) {
            return (
                <div className={rectangleSelectionStyles.selection}>
                    {children}
                    {rectangle}
                </div>
            );
        }

        return rectangle;
    }
}

const RectangleSelectionComponentWithPercentageValues = withPercentageValues(RawRectangleSelectionComponent);

class RectangleSelectionComponent extends React.Component<Props> {
    render() {
        const {usePercentageValues} = this.props;

        if (usePercentageValues) {
            return <RectangleSelectionComponentWithPercentageValues {...this.props} />;
        }

        return <RawRectangleSelectionComponent {...this.props} />;
    }
}

const RectangleSelectionComponentWithContainerSize = withContainerSize(
    RectangleSelectionComponent,
    rectangleSelectionStyles.container
);

export default class RectangleSelection extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        containerHeight: 0,
        containerWidth: 0,
        disabled: false,
        minHeight: undefined,
        minSizeNotification: true,
        minWidth: undefined,
        round: true,
        usePercentageValues: false,
    };

    render() {
        const {children} = this.props;

        if (children) {
            return <RectangleSelectionComponentWithContainerSize {...this.props} />;
        }

        return <RectangleSelectionComponent {...this.props} />;
    }
}
