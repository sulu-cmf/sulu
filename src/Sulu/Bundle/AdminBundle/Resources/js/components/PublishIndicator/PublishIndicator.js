// @flow
import React from 'react';
import classNames from 'classnames';
import publishIndicatorStyles from './publishIndicator.scss';

type Props = {
    containerClass?: string,
    draft: boolean,
    published: boolean,
};

export default class PublishIndicator extends React.Component<Props> {
    static defaultProps = {
        draft: false,
        published: false,
    };

    render() {
        const {containerClass, draft, published} = this.props;

        const className = classNames(
            publishIndicatorStyles.publishIndicator,
            containerClass
        );

        return (
            <div className={className}>
                {published && <span className={publishIndicatorStyles.published} />}
                {draft && <span className={publishIndicatorStyles.draft} />}
            </div>
        );
    }
}
