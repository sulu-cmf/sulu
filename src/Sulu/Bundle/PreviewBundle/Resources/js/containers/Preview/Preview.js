// @flow
import React from 'react';
import {action, computed, observable, reaction, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import classNames from 'classnames';
import {DatePicker, Form, Loader, Toolbar} from 'sulu-admin-bundle/components';
import {ResourceFormStore, sidebarStore} from 'sulu-admin-bundle/containers';
import {Router} from 'sulu-admin-bundle/services';
import {ResourceListStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import {webspaceStore} from 'sulu-page-bundle/stores';
import previewStyles from './preview.scss';
import './public-preview.scss';
import PreviewStore from './stores/PreviewStore';
import PreviewLinkPopover from './PreviewLinkPopover';
import type {PreviewMode} from './types';

type Props = {|
    formStore: ResourceFormStore,
    router: Router,
|};

@observer
class Preview extends React.Component<Props> {
    static debounceDelay: number = 250;
    static mode: PreviewMode = 'auto';
    static audienceTargeting: boolean = false;

    availableDeviceOptions = [
        {label: translate('sulu_preview.auto'), value: 'auto'},
        {label: translate('sulu_preview.desktop'), value: 'desktop'},
        {label: translate('sulu_preview.tablet'), value: 'tablet'},
        {label: translate('sulu_preview.smartphone'), value: 'smartphone'},
    ];

    @observable iframeRef: ?HTMLIFrameElement;
    @observable started: boolean = false;
    @observable selectedDeviceOption = this.availableDeviceOptions[0].value;
    @observable targetGroupsStore: ?ResourceListStore;

    @observable previewStore: PreviewStore;
    @observable previewWindow: any;
    @observable webspaceOptions: Array<Object> = [];
    @observable reloadCounter: number = 0;

    schemaDisposer: () => mixed;
    dataDisposer: () => mixed;
    localeDisposer: () => mixed;

    @computed get webspaceKey() {
        const {
            router: {
                attributes: {
                    webspace,
                },
            },
        } = this.props;

        if (webspace !== undefined && typeof webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string if set!');
        }

        return webspace || this.webspaceOptions[0].value;
    }

    @computed get segments() {
        if (!this.webspaceKey) {
            return [];
        }

        return webspaceStore.getWebspace(this.webspaceKey).segments;
    }

    @computed get shouldUpdateFormStore() {
        return this.props.formStore.resourceKey === this.previewStore.resourceKey;
    }

    constructor(props: Props) {
        super(props);

        if (Preview.audienceTargeting) {
            this.targetGroupsStore = new ResourceListStore('target_groups');
        }

        this.webspaceOptions = webspaceStore.grantedWebspaces.map((webspace): Object => ({
            label: webspace.name,
            value: webspace.key,
        }));

        this.createPreviewStore();
        if (Preview.mode === 'auto') {
            this.startPreview();
        }
    }

    componentDidUpdate(prevProps: Props) {
        const {
            formStore,
        } = this.props;

        if (this.props.formStore !== prevProps.formStore) {
            this.disposeFormStoreReactions();
            this.updatePreview(toJS(formStore.data));

            this.initializeFormStoreReactions();
        }
    }

    @action createPreviewStore = () => {
        const {
            formStore: {
                resourceKey,
                id,
                locale,
            },
            router: {
                route: {
                    options: {
                        previewResourceKey = null,
                    },
                },
            },
        } = this.props;

        this.previewStore = new PreviewStore(
            previewResourceKey || resourceKey,
            id,
            locale,
            this.webspaceKey,
            this.segments.find((segment) => segment.default === true)?.key
        );
    };

    @action setStarted = (started: boolean) => {
        this.started = started;
    };

    startPreview = () => {
        const {previewStore} = this;

        const {
            formStore,
        } = this.props;

        previewStore.start();

        when(
            () => !formStore.loading
                && !previewStore.starting
                && this.iframeRef !== null
                && (!this.targetGroupsStore || !this.targetGroupsStore.loading),
            this.initializeFormStoreReactions
        );

        this.setStarted(true);
    };

    initializeFormStoreReactions = (): void => {
        const {previewStore} = this;

        const {
            formStore,
        } = this.props;

        this.localeDisposer = reaction(
            () => toJS(formStore.locale),
            (locale) => {
                this.previewStore.restart(locale);
            }
        );

        if (previewStore.resourceKey !== formStore.resourceKey) {
            return;
        }

        this.dataDisposer = reaction(
            () => toJS(formStore.data),
            (data) => {
                if (this.iframeRef === null && !this.previewWindow) {
                    return;
                }

                this.updatePreview(data);
            }
        );

        this.schemaDisposer = reaction(
            () => toJS(formStore.schema),
            () => {
                if (formStore.type) {
                    previewStore.updateContext(toJS(formStore.type), toJS(formStore.data)).then(this.setContent);
                }
            }
        );
    };

    updatePreview = debounce((data: Object) => {
        if (this.shouldUpdateFormStore && !!this.previewStore.token) {
            const {previewStore} = this;
            previewStore.update(data).then(this.setContent);
        }
    }, Preview.debounceDelay);

    setContent = (previewContent: string) => {
        const previewDocument = this.getPreviewDocument();
        if (!previewDocument) {
            return;
        }

        const preservedScrollPosition = this.getPreviewScrollPosition();
        previewDocument.open(); // This will lose in Firefox the and safari previewDocument.location
        previewDocument.write(previewContent);
        previewDocument.close();

        if (preservedScrollPosition) {
            setTimeout(() => this.setPreviewScrollPosition(preservedScrollPosition), 0);
        }
    };

    componentWillUnmount() {
        this.disposeFormStoreReactions();

        if (!this.started) {
            return;
        }

        this.updatePreview.clear();
        this.previewStore.stop();
    }

    disposeFormStoreReactions() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }

        if (this.dataDisposer) {
            this.dataDisposer();
        }

        if (this.localeDisposer) {
            this.localeDisposer();
        }
    }

    getPreviewDocument = (): ?Document => {
        if (this.previewWindow) {
            return this.previewWindow.document;
        }

        if (!(this.iframeRef instanceof HTMLIFrameElement)) {
            return;
        }

        return this.iframeRef.contentDocument;
    };

    getPreviewWindow = (): ?any => {
        if (this.previewWindow) {
            return this.previewWindow;
        }

        if (!(this.iframeRef instanceof HTMLIFrameElement)) {
            return;
        }

        return this.iframeRef.contentWindow;
    };

    getPreviewScrollPosition = (): ?number => {
        const previewWindow = this.getPreviewWindow();
        if (previewWindow) {
            return previewWindow.document?.documentElement?.scrollTop
                || previewWindow.pageYOffset
                || previewWindow.document?.body?.scrollTop;
        }
    };

    setPreviewScrollPosition = (pos: number) => {
        const previewWindow = this.getPreviewWindow();
        if (previewWindow) {
            previewWindow.scrollTo({top: pos});
        }
    };

    @action setIframe = (iframeRef: ?Object) => {
        this.iframeRef = iframeRef;
    };

    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'medium') {
            return sidebarStore.setSize('large');
        }

        sidebarStore.setSize('medium');
    };

    @action handleDeviceSelectChange = (value: string | number) => {
        this.selectedDeviceOption = value;
    };

    @action handleDateTimeChange = debounce((value: ?Date) => {
        const {formStore} = this.props;

        this.previewStore.setDateTime(value || new Date());
        this.updatePreview(toJS(formStore.data));
    }, Preview.debounceDelay);

    @action handleWebspaceChange = (webspace: string) => {
        const {formStore} = this.props;

        this.previewStore.setWebspace(webspace);
        this.updatePreview(toJS(formStore.data));
    };

    handleTargetGroupChange = (targetGroupId: number) => {
        const {formStore} = this.props;

        this.previewStore.setTargetGroup(targetGroupId);
        this.updatePreview(toJS(formStore.data));
    };

    handleSegmentChange = (segmentKey: ?string) => {
        const {formStore} = this.props;

        this.previewStore.setSegment(segmentKey);
        this.updatePreview(toJS(formStore.data));
    };

    @action handleRefreshClick = () => {
        // We can not reload the iframe here as safari and firefox
        // resets the location.href to another url on previewDocument.open
        // so instead of this we rerender the whole iframe.
        ++this.reloadCounter;
    };

    handleStartClick = () => {
        this.startPreview();
    };

    @action handlePreviewWindowClick = () => {
        this.previewWindow = window.open(this.previewStore.renderRoute);
        this.previewWindow.addEventListener('beforeunload', action(() => {
            this.previewWindow = undefined;
        }));
    };

    render() {
        const {router} = this.props;
        const {previewWebspaceChooser = true} = router.route.options;

        if (this.previewWindow || (this.targetGroupsStore && this.targetGroupsStore.loading)) {
            return null;
        }

        if (Preview.mode !== 'auto' && !this.started) {
            return <button onClick={this.handleStartClick} type="button">Start</button>;
        }

        const containerClass = classNames(
            previewStyles.container,
            {
                [previewStyles[this.selectedDeviceOption]]: this.selectedDeviceOption,
            }
        );

        return (
            <div className={containerClass}>
                {this.previewStore.starting
                    ? <div className={previewStyles.loaderContainer}>
                        <Loader />
                    </div>
                    : <div className={previewStyles.previewContainer}>
                        <div className={previewStyles.iframeContainer}>
                            <iframe
                                className={previewStyles.iframe}
                                key={this.reloadCounter}
                                ref={this.setIframe}
                                src={this.previewStore.renderRoute}
                            />
                        </div>
                    </div>
                }
                <Toolbar skin="dark">
                    <Toolbar.Controls grow={true}>
                        <Toolbar.Button
                            icon={sidebarStore.size === 'medium' ? 'su-arrow-left' : 'su-arrow-right'}
                            onClick={this.handleToggleSidebarClick}
                        />
                        <Toolbar.Items>
                            <Toolbar.Select
                                icon="su-expand"
                                onChange={this.handleDeviceSelectChange}
                                options={this.availableDeviceOptions}
                                value={this.selectedDeviceOption}
                            />
                            <Toolbar.Popover
                                icon="su-calendar"
                                label={(this.previewStore?.dateTime || new Date()).toLocaleString()}
                            >
                                {() => (
                                    <div className={previewStyles.dateTimeForm}>
                                        <Form skin="dark">
                                            <Form.Field
                                                description={translate('sulu_admin.preview_date_time_description')}
                                                label={translate('sulu_admin.preview_date_time')}
                                            >
                                                <DatePicker
                                                    onChange={this.handleDateTimeChange}
                                                    options={{dateFormat: true, timeFormat: true}}
                                                    value={this.previewStore?.dateTime}
                                                />
                                            </Form.Field>
                                        </Form>
                                    </div>
                                )}
                            </Toolbar.Popover>
                            {previewWebspaceChooser &&
                                <Toolbar.Select
                                    icon="su-webspace"
                                    onChange={this.handleWebspaceChange}
                                    options={this.webspaceOptions}
                                    value={this.previewStore.webspace}
                                />
                            }
                            {!!this.targetGroupsStore &&
                                <Toolbar.Select
                                    icon="su-user"
                                    loading={this.targetGroupsStore.loading}
                                    onChange={this.handleTargetGroupChange}
                                    options={
                                        [
                                            {label: translate('sulu_audience_targeting.no_target_group'), value: -1},
                                            ...(this.targetGroupsStore
                                                ? this.targetGroupsStore.data.map((targetGroup) => ({
                                                    label: targetGroup.title,
                                                    value: targetGroup.id,
                                                }))
                                                : []
                                            ),
                                        ]
                                    }
                                    value={this.previewStore && this.previewStore.targetGroup}
                                />
                            }
                            {this.segments.length > 0 &&
                                <Toolbar.Select
                                    icon="su-focus"
                                    onChange={this.handleSegmentChange}
                                    options={
                                        this.segments.map(({title, key}) => ({
                                            label: title,
                                            value: key,
                                        }))
                                    }
                                    value={this.previewStore && this.previewStore.segment}
                                />
                            }
                            <Toolbar.Button
                                icon="su-sync"
                                onClick={this.handleRefreshClick}
                            >
                                {translate('sulu_preview.reload')}
                            </Toolbar.Button>
                            <Toolbar.Popover
                                icon="su-share"
                                label={translate('sulu_preview.preview_link')}
                            >
                                {() => (
                                    <PreviewLinkPopover
                                        previewStore={this.previewStore}
                                    />
                                )}
                            </Toolbar.Popover>
                            <Toolbar.Button
                                icon="su-link"
                                onClick={this.handlePreviewWindowClick}
                            >
                                {translate('sulu_preview.open_in_window')}
                            </Toolbar.Button>
                        </Toolbar.Items>
                    </Toolbar.Controls>
                </Toolbar>
            </div>
        );
    }
}

export default Preview;
