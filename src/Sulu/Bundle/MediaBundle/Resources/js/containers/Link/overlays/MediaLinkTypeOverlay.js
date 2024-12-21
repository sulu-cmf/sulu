// @flow
import React from 'react';
import {observable} from 'mobx';
import {Dialog, Input, Form} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import SingleSelect from 'sulu-admin-bundle/components/SingleSelect';
import SingleMediaSelection from '../../SingleMediaSelection';
import type {Value} from '../../SingleMediaSelection';
import type {Media} from '../../../types';
import type {LinkTypeOverlayProps} from 'sulu-admin-bundle/containers/Link/types';

export default class MediaLinkTypeOverlay extends React.Component<LinkTypeOverlayProps> {
    handleChange = (value: Value, media: ?Media) => {
        const {onHrefChange} = this.props;

        onHrefChange(value.id, media);
    };

    render() {
        const {
            href,
            locale,
            onCancel,
            onConfirm,
            onTitleChange,
            onTargetChange,
            onAnchorChange,
            open,
            title,
            target,
            anchor,
        } = this.props;

        if (typeof href === 'string') {
            throw new Error('The id of a media should always be a number!');
        }

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={onCancel}
                onConfirm={onConfirm}
                open={open}
                title={translate('sulu_admin.link')}
            >
                <Form>
                    <Form.Field label={translate('sulu_admin.link_url')} required={true}>
                        <SingleMediaSelection
                            locale={locale || observable.box(userStore.contentLocale)}
                            onChange={this.handleChange}
                            value={{displayOption: undefined, id: href}}
                        />
                    </Form.Field>

                    {!!onAnchorChange &&
                        <Form.Field label={translate('sulu_admin.link_anchor')}>
                            <Input onChange={onAnchorChange} value={anchor} />
                        </Form.Field>
                    }

                    {!!onTargetChange &&
                        <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                            <SingleSelect onChange={onTargetChange} value={target}>
                                <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                                <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                                <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                                <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                            </SingleSelect>
                        </Form.Field>
                    }

                    {!!onTitleChange &&
                        <Form.Field label={translate('sulu_admin.link_title')}>
                            <Input onChange={onTitleChange} value={title} />
                        </Form.Field>
                    }
                </Form>
            </Dialog>
        );
    }
}
