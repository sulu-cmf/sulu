// @flow
import React from 'react';
import log from 'loglevel';
import AlignmentPlugin from '@ckeditor/ckeditor5-alignment/src/alignment';
import BoldPlugin from '@ckeditor/ckeditor5-basic-styles/src/bold';
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import EssentialsPlugin from '@ckeditor/ckeditor5-essentials/src/essentials';
import HeadingPlugin from '@ckeditor/ckeditor5-heading/src/heading';
import ItalicPlugin from '@ckeditor/ckeditor5-basic-styles/src/italic';
import ListPlugin from '@ckeditor/ckeditor5-list/src/list';
import ParagraphPlugin from '@ckeditor/ckeditor5-paragraph/src/paragraph';
import StrikethroughPlugin from '@ckeditor/ckeditor5-basic-styles/src/strikethrough';
import UnderlinePlugin from '@ckeditor/ckeditor5-basic-styles/src/underline';
import SubscriptPlugin from '@ckeditor/ckeditor5-basic-styles/src/subscript';
import SuperscriptPlugin from '@ckeditor/ckeditor5-basic-styles/src/superscript';
import CodePlugin from '@ckeditor/ckeditor5-basic-styles/src/code';
import TablePlugin from '@ckeditor/ckeditor5-table/src/table';
import TableToolbarPlugin from '@ckeditor/ckeditor5-table/src/tabletoolbar';
import {translate} from '../../utils/Translator';
import ExternalLinkPlugin from './plugins/ExternalLinkPlugin';
import InternalLinkPlugin from './plugins/InternalLinkPlugin';
import configRegistry from './registries/configRegistry';
import pluginRegistry from './registries/pluginRegistry';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef} from 'react';
import './ckeditor5.scss';

type Props = {|
    disabled: boolean,
    formats: Array<string>,
    locale?: ?IObservableValue<string>,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    onFocus?: (event: { target: EventTarget }) => void,
    value: ?string,
|};

/**
 * React component that renders a classic ck-editor.
 *
 * Implementation is based upon the official ck-editor component:
 * https://github.com/ckeditor/ckeditor5-react/blob/089e28eafa64baf273c5e3690b08c1f8ee5ebbe5/src/ckeditor.jsx
 */
export default class CKEditor5 extends React.Component<Props> {
    containerRef: ?ElementRef<'div'>;
    editorInstance: any;

    static defaultProps = {
        disabled: false,
        formats: ['h2', 'h3', 'h4', 'h5', 'h6'],
        value: '',
    };

    constructor(props: Props) {
        super(props);

        this.editorInstance = null;
    }

    setContainerRef = (containerRef: ?ElementRef<'div'>) => {
        this.containerRef = containerRef;
    };

    componentDidUpdate() {
        if (this.editorInstance) {
            const {value, disabled} = this.props;

            if (disabled) {
                this.editorInstance.ui.element.classList.add('disabled');
                this.editorInstance.enableReadOnlyMode('disabled');
            } else {
                this.editorInstance.ui.element.classList.remove('disabled');
                this.editorInstance.disableReadOnlyMode('disabled');
            }

            const editorData = this.getEditorData();
            if (editorData !== value && !(value === '' && editorData === undefined)) {
                this.editorInstance.setData(value);
            }
        }
    }

    componentDidMount() {
        const {formats, locale} = this.props;

        const defaultConfig = {
            toolbar: [
                'heading',
                'bold',
                'italic',
                'underline',
                'strikethrough',
                'subscript',
                'superscript',
                'bulletedlist',
                'numberedlist',
                'externalLink',
                'internalLink',
                'alignment',
                'insertTable',
                'code',
            ],
            heading: {
                options: [
                    {
                        model: 'paragraph',
                        title: translate('sulu_admin.paragraph'),
                        class: 'ck-heading_paragraph',
                    },
                    formats.includes('h1') ? {
                        model: 'heading1',
                        view: 'h1',
                        title: translate('sulu_admin.heading1'),
                        class: 'ck-heading_heading1',
                    } : undefined,
                    formats.includes('h2') ? {
                        model: 'heading2',
                        view: 'h2',
                        title: translate('sulu_admin.heading2'),
                        class: 'ck-heading_heading2',
                    } : undefined,
                    formats.includes('h3') ? {
                        model: 'heading3',
                        view: 'h3',
                        title: translate('sulu_admin.heading3'),
                        class: 'ck-heading_heading3',
                    } : undefined,
                    formats.includes('h4') ? {
                        model: 'heading4',
                        view: 'h4',
                        title: translate('sulu_admin.heading4'),
                        class: 'ck-heading_heading4',
                    } : undefined,
                    formats.includes('h5') ? {
                        model: 'heading5',
                        view: 'h5',
                        title: translate('sulu_admin.heading5'),
                        class: 'ck-heading_heading5',
                    } : undefined,
                    formats.includes('h6') ? {
                        model: 'heading6',
                        view: 'h6',
                        title: translate('sulu_admin.heading6'),
                        class: 'ck-heading_heading6',
                    } : undefined,
                ].filter((entry) => entry !== undefined),
            },
            sulu: {
                locale: locale && locale.get(),
            },
            table: {
                contentToolbar: [
                    'tableColumn',
                    'tableRow',
                    'mergeTableCells',
                ],
            },
            ui: {
                poweredBy: {
                    position: 'inside',
                    side: 'right',
                    label: '',
                    verticalOffset: 2,
                    horizontalOffset: 3,
                },
            },
        };

        ClassicEditor
            .create(this.containerRef, {
                plugins: [
                    AlignmentPlugin,
                    BoldPlugin,
                    EssentialsPlugin,
                    ExternalLinkPlugin,
                    HeadingPlugin,
                    InternalLinkPlugin,
                    ItalicPlugin,
                    ListPlugin,
                    ParagraphPlugin,
                    StrikethroughPlugin,
                    UnderlinePlugin,
                    SubscriptPlugin,
                    SuperscriptPlugin,
                    CodePlugin,
                    TablePlugin,
                    TableToolbarPlugin,
                    ...pluginRegistry.plugins,
                ],
                ...configRegistry.configs.reduce((previousConfig, config) => {
                    return {...previousConfig, ...config(previousConfig)};
                }, defaultConfig),
            })
            .then((editor) => {
                this.editorInstance = editor;

                this.editorInstance.setData(this.props.value);

                const {disabled, onBlur, onChange, onFocus} = this.props;
                const {
                    model: {
                        document: modelDocument,
                    },
                    editing: {
                        view: {
                            document: viewDocument,
                        },
                    },
                } = this.editorInstance;

                if (disabled) {
                    this.editorInstance.enableReadOnlyMode('disabled');
                    this.editorInstance.ui.element.classList.add('disabled');
                }

                if (onBlur) {
                    viewDocument.on('blur', () => {
                        onBlur();
                    });
                }

                if (onFocus) {
                    viewDocument.on('focus', () => {
                        onFocus({
                            target: this.editorInstance.ui.element.querySelector('div[contenteditable="true"]'),
                        });
                    });
                }

                if (onChange) {
                    modelDocument.on('change', () => {
                        if (modelDocument.differ.getChanges().length > 0) {
                            onChange(this.getEditorData());
                        }
                    });
                }
            })
            .catch((error) => {
                log.error(error);
            });
    }

    componentWillUnmount() {
        if (this.editorInstance) {
            this.editorInstance.destroy().then(() => this.editorInstance = null);
        }
    }

    getEditorData() {
        const editorData = this.editorInstance.getData();
        return editorData === '' ? undefined : editorData;
    }

    render() {
        return <div ref={this.setContainerRef}></div>;
    }
}
