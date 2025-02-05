// @flow

import React, {Component} from 'react';
import classNames from 'classnames';
import loaderStyles from './loader.scss';
import messageStyles from './message.scss';

type Props = {|
    commandTitle: string,
    expert: string,
|};

/**
 * @internal
 */
class Loader extends Component<Props> {
    render() {
        const {
            commandTitle,
            expert,
        } = this.props;

        const short = classNames(
            loaderStyles.skeletonLoader,
            loaderStyles.short
        );

        return (
            <React.Fragment>
                <div className={messageStyles.command}>
                    {commandTitle}
                    <div className={messageStyles.expert}>{expert}</div>
                </div>
                <div className={messageStyles.message}>
                    <div className={loaderStyles.skeletonLoader}></div>
                    <div className={loaderStyles.skeletonLoader}></div>
                    <div className={loaderStyles.skeletonLoader}></div>
                    <div className={short}></div>
                    <div className={loaderStyles.skeletonLoader}></div>
                    <div className={short}></div>
                </div>
            </React.Fragment>
        );
    }
}

export default Loader;
