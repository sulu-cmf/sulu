// @flow
import React, {Fragment} from 'react';
import {render, unmountComponentAtNode} from 'react-dom';
import {action, observable} from 'mobx';
import {Observer} from 'mobx-react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ListView from '@ckeditor/ckeditor5-ui/src/list/listview';
import ListItemView from '@ckeditor/ckeditor5-ui/src/list/listitemview';
import {createDropdown} from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import internalLinkTypeRegistry from './registries/InternalLinkTypeRegistry';
import LinkBalloonView from '../../LinkBalloonView';
import LinkCommand from '../../LinkCommand';
import {addLinkConversion, findModelItemInSelection, findViewLinkItemInSelection} from '../../utils';
import UnlinkCommand from '../../UnlinkCommand';
// $FlowFixMe
import linkIcon from '!!raw-loader!./link.svg'; // eslint-disable-line import/no-webpack-loader-syntax

const DEFAULT_TARGET = '_self';

const LINK_EVENT_TARGET = 'target';
const LINK_EVENT_ID = 'id';
const LINK_EVENT_PROVIDER = 'provider';
const LINK_EVENT_TITLE = 'title';

const LINK_HREF_ATTRIBUTE = 'internalLinkHref';
const LINK_TARGET_ATTRIBUTE = 'internalLinkTarget';
const LINK_PROVIDER_ATTRIBUTE = 'internalLinkProvider';
const LINK_VALIDATION_STATE_ATTRIBUTE = 'validationState';

const LINK_TAG = 'sulu:link';

export default class InternalLinkPlugin extends Plugin {
    @observable openOverlay: ?string = undefined;
    @observable target: ?string = DEFAULT_TARGET;
    @observable id: ?string | number = undefined;
    title: ?string;
    balloon: ContextualBalloon;

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

            const id = node.getAttribute(LINK_HREF_ATTRIBUTE);

            this.target = node.getAttribute(LINK_TARGET_ATTRIBUTE);
            this.id = !isNaN(id) ? parseInt(id) : id;
            this.openOverlay = node.getAttribute(LINK_PROVIDER_ATTRIBUTE);

            this.hideBalloon();
        }));

        const locale = this.editor.config.get('internalLinks.locale');

        render(
            (
                <Observer>
                    {() => (
                        <Fragment>
                            {internalLinkTypeRegistry.getKeys().map((key) => {
                                const InternalLinkOverlay = internalLinkTypeRegistry.getOverlay(key);

                                return (
                                    <InternalLinkOverlay
                                        id={this.openOverlay === key ? this.id : undefined}
                                        key={key}
                                        locale={locale}
                                        onCancel={this.handleOverlayClose}
                                        onConfirm={this.handleOverlayConfirm}
                                        onResourceChange={this.handleResourceChange}
                                        onTargetChange={this.handleTargetChange}
                                        open={this.openOverlay === key}
                                        options={internalLinkTypeRegistry.getOptions(key)}
                                        target={this.target}
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
                    [LINK_HREF_ATTRIBUTE]: LINK_EVENT_ID,
                    [LINK_TARGET_ATTRIBUTE]: LINK_EVENT_TARGET,
                    [LINK_PROVIDER_ATTRIBUTE]: LINK_EVENT_PROVIDER,
                },
                LINK_EVENT_TITLE
            )
        );
        this.editor.commands.add(
            'internalUnlink',
            new UnlinkCommand(
                this.editor,
                [LINK_TARGET_ATTRIBUTE, LINK_HREF_ATTRIBUTE, LINK_VALIDATION_STATE_ATTRIBUTE, LINK_PROVIDER_ATTRIBUTE]
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
            dropdownButton.buttonView.set({icon: linkIcon});

            internalLinkTypeRegistry.getKeys().forEach((key) => {
                const button = new ButtonView(locale);
                button.set({
                    class: 'ck-link-button',
                    label: internalLinkTypeRegistry.getTitle(key),
                    withText: true,
                });
                const listItem = new ListItemView(locale);
                listItem.children.add(button);
                button.delegate('execute').to(listItem);

                button.on('execute', action(() => {
                    this.selection = this.editor.model.document.selection;
                    this.openOverlay = key;
                    this.target = DEFAULT_TARGET;
                    this.id = undefined;
                }));

                list.items.add(listItem);
            });

            list.items.delegate('execute').to(dropdownButton);

            dropdownButton.panelView.children.add(list);

            return dropdownButton;
        });

        addLinkConversion(this.editor, LINK_TAG, LINK_VALIDATION_STATE_ATTRIBUTE, 'sulu:validation-state');
        addLinkConversion(this.editor, LINK_TAG, LINK_PROVIDER_ATTRIBUTE, 'provider');
        addLinkConversion(this.editor, LINK_TAG, LINK_TARGET_ATTRIBUTE, 'target');
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
                [LINK_EVENT_ID]: this.id,
                [LINK_EVENT_PROVIDER]: this.openOverlay,
                selection: this.selection,
                [LINK_EVENT_TARGET]: this.target,
                [LINK_EVENT_TITLE]: this.title,
            }
        );
        this.openOverlay = undefined;
    };

    @action handleOverlayClose = () => {
        this.openOverlay = undefined;
    };

    @action handleTargetChange = (target: ?string) => {
        this.target = target;
    };

    @action handleResourceChange = (id: ?string | number, item: ?Object) => {
        this.id = id;
        this.title = item ? item.title : undefined;
    };

    destroy() {
        unmountComponentAtNode(this.internalLinkElement);
        this.internalLinkElement.remove();
        this.internalLinkElement = undefined;
    }
}
