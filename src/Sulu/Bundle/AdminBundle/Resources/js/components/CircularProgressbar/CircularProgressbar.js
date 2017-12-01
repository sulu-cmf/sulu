// @flow
import React from 'react';
import ReactCircularProgressbar from 'react-circular-progressbar';
import circularProgressbarStyles from './circularProgressbar.scss';

type Props = {
    size: number,
    percentage: number,
    hidePercentageText: boolean,
};

export default class CircularProgressbar extends React.PureComponent<Props> {
    static defaultProps = {
        size: 100,
        percentage: 0,
        hidePercentageText: false,
    };

    handlePercentageText = (percentage: number) => {
        const {hidePercentageText} = this.props;

        if (hidePercentageText) {
            return null;
        }

        return `${percentage}%`;
    };

    render() {
        const {
            size,
            percentage,
        } = this.props;
        const sizeStyle = {
            width: size,
            height: size,
        };

        return (
            <div style={sizeStyle}>
                <ReactCircularProgressbar
                    classes={{
                        root: circularProgressbarStyles.root,
                        path: circularProgressbarStyles.path,
                        tail: circularProgressbarStyles.tail,
                        text: circularProgressbarStyles.text,
                        background: circularProgressbarStyles.background,
                    }}
                    percentage={percentage}
                    background={true}
                    textForPercentage={this.handlePercentageText} // eslint-disable-line react/jsx-handler-names
                />
            </div>
        );
    }
}
