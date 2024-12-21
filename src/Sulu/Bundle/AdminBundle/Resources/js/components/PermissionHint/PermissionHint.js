// @flow
import React from 'react';
import log from 'loglevel';
import {translate} from '../../utils/Translator';
import Hint from '../Hint';

type Props = {||};

export default class PermissionHint extends React.Component<Props> {
    constructor(props: Props) {
        super(props);

        log.warn(
            'The "PermissionHint" component is deprecated since 3.0 and will ' +
            'be removed. Use the "Hint" component instead.'
        );
    }

    render() {
        return (
            <Hint icon="su-lock" title={translate('sulu_admin.no_permissions')} />
        );
    }
}
