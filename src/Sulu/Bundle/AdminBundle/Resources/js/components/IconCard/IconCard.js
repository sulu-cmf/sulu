// @flow
import React from 'react';
import classNames from 'classnames';
import iconCardStyle from './iconCard.scss';

type Props = {
    content: string,
    id: string,
    isSelected: boolean,
    onClick?: (id: string) => void,
};

export default class IconCard extends React.PureComponent<Props> {
    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id);
        }
    };

    render() {
        const {
            id,
            content,
            isSelected,
        } = this.props;

        const classesNames = classNames(
            iconCardStyle.iconCardContent,
            {
                [iconCardStyle.isSelected]: isSelected,
            }
        );

        return (
            <div
                className={iconCardStyle.iconCard}
                onClick={this.handleClick}
                role="button"
                tabIndex="0"
            >
                <div className={classesNames}>
                    <div dangerouslySetInnerHTML={{__html: content}} />

                    <div className={iconCardStyle.iconCardTitle}>
                        {id}
                    </div>
                </div>
            </div>
        );
    }
}
