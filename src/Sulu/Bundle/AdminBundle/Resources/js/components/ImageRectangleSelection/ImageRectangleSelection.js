// @flow
import {action, computed, observable} from 'mobx';
import log from 'loglevel';
import {observer} from 'mobx-react';
import React from 'react';
import RectangleSelection, {RoundingNormalizer} from '../RectangleSelection';
import type {SelectionData} from '../RectangleSelection';
import withContainerSize from '../withContainerSize';
import imageRectangleSelectionStyles from './imageRectangleSelection.scss';

type Props = {
    /** Determines the position at which the selection box is rendered at the beginning. */
    initialSelection?: SelectionData,
    minWidth?: number,
    minHeight?: number,
    onChange?: (s: SelectionData) => void,
    src: string,
    containerWidth: number,
    containerHeight: number,
};

@observer
export class ImageRectangleSelection extends React.Component<Props> {
    image: Image;
    rounding = new RoundingNormalizer();
    @observable imageLoaded = false;

    naturalHorizontalToScaled = (h: number) => h * this.imageResizedWidth / this.image.naturalWidth;
    scaledHorizontalToNatural = (h: number) => h * this.image.naturalWidth / this.imageResizedWidth;
    naturalVerticalToScaled = (v: number) => v * this.imageResizedHeight / this.image.naturalHeight;
    scaledVerticalToNatural = (v: number) => v * this.image.naturalHeight / this.imageResizedHeight;

    naturalDataToScaled(data: SelectionData): SelectionData {
        return {
            width: this.naturalHorizontalToScaled(data.width),
            height: this.naturalVerticalToScaled(data.height),
            left: this.naturalHorizontalToScaled(data.left),
            top: this.naturalVerticalToScaled(data.top),
        };
    }

    scaledDataToNatural(data: SelectionData): SelectionData {
        return {
            width: this.scaledHorizontalToNatural(data.width),
            height: this.scaledVerticalToNatural(data.height),
            left: this.scaledHorizontalToNatural(data.left),
            top: this.scaledVerticalToNatural(data.top),
        };
    }

    componentWillMount() {
        this.image = new Image();
        this.image.onload = action(() => this.imageLoaded = true);
        this.image.onerror = () => log.error('Failed to preload image "' + this.props.src + '"');
        this.image.src = this.props.src;
    }

    @computed get imageResizedHeight(): number {
        if (this.imageTouchesHorizontalBorders()) {
            return Math.min(this.image.naturalHeight, this.props.containerHeight);
        } else {
            return this.imageResizedWidth * this.image.naturalHeight / this.image.naturalWidth;
        }
    }

    @computed get imageResizedWidth(): number {
        if (this.imageTouchesHorizontalBorders()) {
            return this.imageResizedHeight * this.image.naturalWidth / this.image.naturalHeight;
        } else {
            return Math.min(this.image.naturalWidth, this.props.containerWidth);
        }
    }

    imageTouchesHorizontalBorders() {
        const imageHeightToWidth = this.image.naturalHeight / this.image.naturalWidth;
        const containerHeightToWidth = this.props.containerHeight / this.props.containerWidth;
        return imageHeightToWidth > containerHeightToWidth;
    }

    handleRectangleSelectionChange = (data: SelectionData) => {
        if (this.props.onChange) {
            const onChange = this.props.onChange;
            onChange(this.rounding.normalize(this.scaledDataToNatural(data)));
        }
    };

    render() {
        if (!this.imageLoaded || !this.props.containerWidth || !this.props.containerHeight) {
            return null;
        }

        const minWidth = this.props.minWidth ? this.naturalHorizontalToScaled(this.props.minWidth) : null;
        const minHeight = this.props.minHeight ? this.naturalVerticalToScaled(this.props.minHeight) : null;
        const initialSelection = this.props.initialSelection ?
            this.naturalDataToScaled(this.props.initialSelection) : null;
        return (
            <RectangleSelection
                initialSelection={initialSelection}
                minWidth={minWidth}
                minHeight={minHeight}
                onChange={this.handleRectangleSelectionChange}
                round={false}
            >
                <img
                    width={this.imageResizedWidth}
                    height={this.imageResizedHeight}
                    src={this.props.src}
                />
            </RectangleSelection>
        );
    }
}

export default withContainerSize(ImageRectangleSelection, imageRectangleSelectionStyles.container);
