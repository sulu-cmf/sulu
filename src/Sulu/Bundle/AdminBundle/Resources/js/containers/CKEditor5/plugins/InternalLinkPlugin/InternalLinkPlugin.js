// @flow
import React, {Fragment} from 'react';
import {render, unmountComponentAtNode} from 'react-dom';
import {action, computed, observable} from 'mobx';
import {Observer} from 'mobx-react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ListView from '@ckeditor/ckeditor5-ui/src/list/listview';
import ListItemView from '@ckeditor/ckeditor5-ui/src/list/listitemview';
import {createDropdown} from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import {translate} from '../../../../utils';
import LinkBalloonView from '../../LinkBalloonView';
import LinkCommand from '../../LinkCommand';
import {addLinkConversion, findModelItemInSelection, findViewLinkItemInSelection} from '../../utils';
import UnlinkCommand from '../../UnlinkCommand';
import linkTypeRegistry from '../../../Link/registries/linkTypeRegistry';
// $FlowFixMe
import linkIcon from '!!raw-loader!./link.svg'; // eslint-disable-line import/no-webpack-loader-syntax

const DEFAULT_TARGET = '_self';

const LINK_EVENT_TARGET = 'target';
const LINK_EVENT_HREF = 'href';
const LINK_EVENT_PROVIDER = 'provider';
const LINK_EVENT_TITLE = 'title';

const LINK_DEFAULT_TEXT = 'defaultText';

const LINK_HREF_ATTRIBUTE = 'internalLinkHref';
const LINK_TARGET_ATTRIBUTE = 'internalLinkTarget';
const LINK_PROVIDER_ATTRIBUTE = 'internalLinkProvider';
const LINK_TITLE_ATTRIBUTE = 'internalLinkTitle';
const LINK_VALIDATION_STATE_ATTRIBUTE = 'validationState';

const LINK_TAG = 'sulu-link';

export default class InternalLinkPlugin extends Plugin {
    @observable openOverlay: ?string = undefined;
    @observable target: ?string = DEFAULT_TARGET;
    @observable id: ?string | number = undefined;
    @observable title: ?string;
    @observable query: ?string;
    @observable anchor: ?string;
    defaultText: ?string;
    balloon: typeof ContextualBalloon;

    @computed get internalLinkTypes(): Array<string> {
        return linkTypeRegistry.getKeys().filter((type) => type !== 'external');
    }

    @computed get href(): ?string | number {
        const {id, query, anchor} = this;

        if (!id) {
            return null;
        }

        let suffix = '';
        if (query) {
            suffix += '?' + query.replace(/^\?+/g, '');
        }
        if (anchor) {
            suffix += '#' + anchor.replace(/^#+/g, '');
        }

        return id + suffix;
    }

    init() {
        this.internalLinkElement = document.createElement('div');
        this.editor.sourceElement.appendChild(this.internalLinkElement);
        this.balloon = this.editor.plugins.get(ContextualBalloon);
        this.balloonView = new LinkBalloonView(this.editor.locale);

        this.listenTo(this.balloonView, 'unlink', () => {
            this.editor.execute('internalUnlink');
            this.hideBalloon();
        });

        this.listenTo(this.balloonView, 'link', action(() => {
            this.selection = this.editor.model.document.selection;
            const node = findModelItemInSelection(this.editor);

            const href = node.getAttribute(LINK_HREF_ATTRIBUTE);
            let hrefParts = href.split('#', 2);
            const anchor = hrefParts[1] || null;
            hrefParts = hrefParts[0]?.split('?', 2);
            const id = hrefParts[0] || null;
            const query = hrefParts[1] || null;
            this.id = !isNaN(id) ? parseInt(id) : id;
            this.anchor = anchor;
            this.query = query;
            this.target = node.getAttribute(LINK_TARGET_ATTRIBUTE);
            this.title = node.getAttribute(LINK_TITLE_ATTRIBUTE);
            this.openOverlay = node.getAttribute(LINK_PROVIDER_ATTRIBUTE);

            this.hideBalloon();
        }));

        const locale = this.editor.config.get('sulu.locale');

        render(
            (
                <Observer>
                    {() => (
                        <Fragment>
                            {this.internalLinkTypes.map((key) => {
                                const LinkOverlay = linkTypeRegistry.getOverlay(key);

                                return (
                                    <LinkOverlay
                                        anchor={this.anchor}
                                        href={this.openOverlay === key ? this.id : undefined}
                                        key={key}
                                        locale={observable.box(locale)}
                                        onAnchorChange={this.handleAnchorChange}
                                        onCancel={this.handleOverlayClose}
                                        onConfirm={this.handleOverlayConfirm}
                                        onHrefChange={this.handleHrefChange}
                                        onQueryChange={this.handleQueryChange}
                                        onTargetChange={this.handleTargetChange}
                                        onTitleChange={this.handleTitleChange}
                                        open={this.openOverlay === key}
                                        options={linkTypeRegistry.getOptions(key)}
                                        query={this.query}
                                        target={this.target}
                                        title={this.title}
                                    />
                                );
                            })}
                        </Fragment>
                    )}
                </Observer>
            ),
            this.internalLinkElement
        );

        this.editor.commands.add(
            'internalLink',
            new LinkCommand(
                this.editor,
                {
                    [LINK_HREF_ATTRIBUTE]: LINK_EVENT_HREF,
                    [LINK_TARGET_ATTRIBUTE]: LINK_EVENT_TARGET,
                    [LINK_TITLE_ATTRIBUTE]: LINK_EVENT_TITLE,
                    [LINK_PROVIDER_ATTRIBUTE]: LINK_EVENT_PROVIDER,
                },
                LINK_DEFAULT_TEXT
            )
        );
        this.editor.commands.add(
            'internalUnlink',
            new UnlinkCommand(
                this.editor,
                [
                    LINK_TARGET_ATTRIBUTE,
                    LINK_TITLE_ATTRIBUTE,
                    LINK_HREF_ATTRIBUTE,
                    LINK_VALIDATION_STATE_ATTRIBUTE,
                    LINK_PROVIDER_ATTRIBUTE,
                ]
            )
        );

        this.editor.ui.componentFactory.add('internalLink', (locale) => {
            const dropdownButton = createDropdown(locale);
            const list = new ListView(locale);

            dropdownButton.bind('isEnabled').to(
                this.editor.commands.get('internalLink'),
                'buttonEnabled',
                this.editor.commands.get('externalLink'),
                'buttonEnabled',
                (internalLinkEnabled, externalLinkEnabled) => internalLinkEnabled && externalLinkEnabled
            );

            dropdownButton.buttonView.set({
                icon: linkIcon,
                label: translate('sulu_admin.internal_link'),
                tooltip: true,
            });

            this.internalLinkTypes.forEach((key) => {
                const button = new ButtonView(locale);
                button.set({
                    class: 'ck-link-button',
                    label: linkTypeRegistry.getTitle(key),
                    withText: true,
                });
                const listItem = new ListItemView(locale);
                listItem.children.add(button);
                button.delegate('execute').to(listItem);

                button.on('execute', action(() => {
                    this.selection = this.editor.model.document.selection;
                    this.openOverlay = key;
                    this.target = DEFAULT_TARGET;
                    this.title = undefined;
                    this.id = undefined;
                    this.query = undefined;
                    this.anchor = undefined;
                }));

                list.items.add(listItem);
            });

            list.items.delegate('execute').to(dropdownButton);

            dropdownButton.panelView.children.add(list);

            return dropdownButton;
        });

        addLinkConversion(this.editor, LINK_TAG, LINK_VALIDATION_STATE_ATTRIBUTE, 'sulu-validation-state');
        addLinkConversion(this.editor, LINK_TAG, LINK_PROVIDER_ATTRIBUTE, 'provider');
        addLinkConversion(this.editor, LINK_TAG, LINK_TARGET_ATTRIBUTE, 'target');
        addLinkConversion(this.editor, LINK_TAG, LINK_TITLE_ATTRIBUTE, 'title');
        addLinkConversion(this.editor, LINK_TAG, LINK_HREF_ATTRIBUTE, 'href');

        const view = this.editor.editing.view;
        view.addObserver(ClickObserver);

        this.listenTo(view.document, 'click', () => {
            const externalLink = findViewLinkItemInSelection(this.editor, LINK_TAG);

            this.hideBalloon();

            if (externalLink) {
                this.balloon.add({
                    position: {target: view.domConverter.mapViewToDom(externalLink)},
                    view: this.balloonView,
                });
            }
        });
    }

    hideBalloon() {
        if (this.balloon.hasView(this.balloonView)) {
            this.balloon.remove(this.balloonView);
        }
    }

    @action handleOverlayConfirm = () => {
        this.editor.execute(
            'internalLink',
            {
                [LINK_EVENT_HREF]: this.href,
                [LINK_EVENT_PROVIDER]: this.openOverlay,
                selection: this.selection,
                [LINK_EVENT_TARGET]: this.target,
                [LINK_EVENT_TITLE]: this.title,
                [LINK_DEFAULT_TEXT]: this.defaultText,
            }
        );
        this.openOverlay = undefined;
    };

    @action handleOverlayClose = () => {
        this.openOverlay = undefined;
    };

    @action handleQueryChange = (query: ?string) => {
        this.query = query;
    };

    @action handleAnchorChange = (anchor: ?string) => {
        this.anchor = anchor;
    };

    @action handleTargetChange = (target: ?string) => {
        this.target = target;
    };

    @action handleTitleChange = (title: ?string) => {
        this.title = title;
    };

    @action handleHrefChange = (id: ?string | number, item: ?Object) => {
        this.id = id;
        this.defaultText = item ? item.title : undefined;
    };

    destroy() {
        unmountComponentAtNode(this.internalLinkElement);
        this.internalLinkElement.remove();
        this.internalLinkElement = undefined;
    }
}
