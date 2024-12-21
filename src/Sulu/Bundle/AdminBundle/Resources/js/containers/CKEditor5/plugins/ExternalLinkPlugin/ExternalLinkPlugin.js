// @flow
import React from 'react';
import {Observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ContextualBalloon from '@ckeditor/ckeditor5-ui/src/panel/balloon/contextualballoon';
import ClickObserver from '@ckeditor/ckeditor5-engine/src/view/observer/clickobserver';
import {render, unmountComponentAtNode} from 'react-dom';
import {translate} from '../../../../utils';
import {addLinkConversion, findModelItemInSelection, findViewLinkItemInSelection} from '../../utils';
import LinkBalloonView from '../../LinkBalloonView';
import LinkCommand from '../../LinkCommand';
import UnlinkCommand from '../../UnlinkCommand';
import {ExternalLinkTypeOverlay} from '../../../Link';
// $FlowFixMe
import linkIcon from '!!raw-loader!./link.svg'; // eslint-disable-line import/no-webpack-loader-syntax

const DEFAULT_TARGET = '_self';

const LINK_EVENT_TARGET = 'target';
const LINK_EVENT_TITLE = 'title';
const LINK_EVENT_URL = 'url';
const LINK_EVENT_REL = 'rel';

const LINK_HREF_ATTRIBUTE = 'externalLinkHref';
const LINK_TARGET_ATTRIBUTE = 'externalLinkTarget';
const LINK_TITLE_ATTRIBUTE = 'externalLinkTitle';
const LINK_REL_ATTRIBUTE = 'externalLinkRel';

const LINK_TAG = 'a';

export default class ExternalLinkPlugin extends Plugin {
    @observable open: boolean = false;
    @observable target: ?string = DEFAULT_TARGET;
    @observable title: ?string;
    @observable rel: ?string;
    @observable url: ?string;
    balloon: typeof ContextualBalloon;

    init() {
        this.externalLinkOverlayElement = document.createElement('div');
        this.editor.sourceElement.appendChild(this.externalLinkOverlayElement);
        this.balloon = this.editor.plugins.get(ContextualBalloon);
        this.balloonView = new LinkBalloonView(this.editor.locale, true);
        this.balloonView.bind('href').to(this, 'href');

        this.listenTo(this.balloonView, 'unlink', () => {
            this.editor.execute('externalUnlink');
            this.hideBalloon();
        });

        this.listenTo(this.balloonView, 'link', action(() => {
            this.selection = this.editor.model.document.selection;
            const node = findModelItemInSelection(this.editor);

            this.target = node.getAttribute(LINK_TARGET_ATTRIBUTE);
            this.title = node.getAttribute(LINK_TITLE_ATTRIBUTE);
            this.url = node.getAttribute(LINK_HREF_ATTRIBUTE);
            this.rel = node.getAttribute(LINK_REL_ATTRIBUTE);
            this.open = true;

            this.hideBalloon();
        }));

        const locale = this.editor.config.get('sulu.locale');

        render(
            (
                <Observer>
                    {() => (
                        <ExternalLinkTypeOverlay
                            href={this.url}
                            locale={locale}
                            onCancel={this.handleOverlayClose}
                            onConfirm={this.handleOverlayConfirm}
                            onHrefChange={this.handleHrefChange}
                            onRelChange={this.handleRelChange}
                            onTargetChange={this.handleTargetChange}
                            onTitleChange={this.handleTitleChange}
                            open={this.open}
                            options={undefined}
                            rel={this.rel}
                            target={this.target}
                            title={this.title}
                        />
                    )}
                </Observer>
            ),
            this.externalLinkOverlayElement
        );

        this.editor.commands.add(
            'externalLink',
            new LinkCommand(
                this.editor,
                {
                    [LINK_HREF_ATTRIBUTE]: LINK_EVENT_URL,
                    [LINK_TARGET_ATTRIBUTE]: LINK_EVENT_TARGET,
                    [LINK_TITLE_ATTRIBUTE]: LINK_EVENT_TITLE,
                    [LINK_REL_ATTRIBUTE]: LINK_EVENT_REL,
                },
                LINK_EVENT_URL
            )
        );
        this.editor.commands.add(
            'externalUnlink',
            new UnlinkCommand(
                this.editor,
                [LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE, LINK_TITLE_ATTRIBUTE, LINK_REL_ATTRIBUTE]
            )
        );

        this.editor.ui.componentFactory.add('externalLink', (locale) => {
            const button = new ButtonView(locale);

            button.bind('isEnabled').to(
                this.editor.commands.get('internalLink'),
                'buttonEnabled',
                this.editor.commands.get('externalLink'),
                'buttonEnabled',
                (internalLinkEnabled, externalLinkEnabled) => internalLinkEnabled && externalLinkEnabled
            );

            button.set({
                icon: linkIcon,
                label: translate('sulu_admin.external_link'),
                tooltip: true,
            });

            button.on('execute', action(() => {
                this.selection = this.editor.model.document.selection;
                this.open = true;
                this.target = DEFAULT_TARGET;
                this.title = undefined;
                this.url = undefined;
                this.rel = undefined;
            }));

            return button;
        });

        addLinkConversion(this.editor, LINK_TAG, LINK_TARGET_ATTRIBUTE, 'target');
        addLinkConversion(this.editor, LINK_TAG, LINK_HREF_ATTRIBUTE, 'href');
        addLinkConversion(this.editor, LINK_TAG, LINK_TITLE_ATTRIBUTE, 'title');
        addLinkConversion(this.editor, LINK_TAG, LINK_REL_ATTRIBUTE, 'rel');

        const view = this.editor.editing.view;
        view.addObserver(ClickObserver);

        this.listenTo(view.document, 'click', () => {
            const externalLink = findViewLinkItemInSelection(this.editor, LINK_TAG);

            this.hideBalloon();

            if (externalLink) {
                this.set('href', externalLink.getAttribute('href'));
                this.balloon.add({
                    position: {
                        target: view.domConverter.mapViewToDom(externalLink),
                    },
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
            'externalLink',
            {
                selection: this.selection,
                [LINK_EVENT_TARGET]: this.target,
                [LINK_EVENT_TITLE]: this.title,
                [LINK_EVENT_URL]: this.url,
                [LINK_EVENT_REL]: this.rel,
            }
        );
        this.open = false;
    };

    @action handleOverlayClose = () => {
        this.open = false;
    };

    @action handleTargetChange = (target: ?string) => {
        this.target = target;
    };

    @action handleTitleChange = (title: ?string) => {
        this.title = title;
    };

    @action handleRelChange = (rel: ?string) => {
        this.rel = rel;
    };

    @action handleHrefChange = (href: ?string | number) => {
        this.url = String(href);
    };

    destroy() {
        unmountComponentAtNode(this.externalLinkOverlayElement);
        this.externalLinkOverlayElement.remove();
        this.externalLinkOverlayElement = undefined;
    }
}
