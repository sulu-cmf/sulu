// @flow
import {createHashHistory} from 'history';
import log from 'loglevel';
import React from 'react';
import {render} from 'react-dom';
import {configure} from 'mobx';
import ResizeObserver from 'resize-observer-polyfill';
import Requester from './services/Requester';
import Router, {routeRegistry, resourceViewRegistry} from './services/Router';
import Application from './containers/Application';
import {updateRouterAttributesFromView, viewRegistry} from './containers/ViewRenderer';
import CollaborationStore from './stores/CollaborationStore';
import localizationStore from './stores/localizationStore';
import userStore, {
    logoutOnUnauthorizedResponse,
    updateUserStoreContentLocaleFromRouterAttributes,
    updateRouterAttributesFromUserStoreContentLocale,
} from './stores/userStore';
import {Config, resourceRouteRegistry} from './services';
import initializer from './services/initializer';
import {translate} from './utils';
import ResourceTabs from './views/ResourceTabs';
import List, {
    listItemActionRegistry,
    listToolbarActionRegistry,
    LinkItemAction as ListLinkItemAction,
    DetailLinkItemAction as ListDetailLinkItemAction,
    AddToolbarAction as ListAddToolbarAction,
    DeleteToolbarAction as ListDeleteToolbarAction,
    MoveToolbarAction as ListMoveToolbarAction,
    ExportToolbarAction as ListExportToolbarAction,
    UploadToolbarAction as ListUploadToolbarAction,
} from './views/List';
import Tabs from './views/Tabs';
import CKEditor5 from './containers/TextEditor/adapters/CKEditor5';
import {
    ArrayFieldTransformer,
    BooleanFieldFilterType,
    BoolFieldTransformer,
    ColorFieldTransformer,
    HtmlFieldTransformer,
    IconFieldTransformer,
    BytesFieldTransformer,
    ColumnListAdapter,
    listAdapterRegistry,
    listFieldTransformerRegistry,
    listFieldFilterTypeRegistry,
    DateFieldTransformer,
    DateFieldFilterType,
    DateTimeFieldTransformer,
    SelectFieldFilterType,
    FolderAdapter,
    NumberFieldFilterType,
    NumberFieldTransformer,
    SelectionFieldFilterType,
    StringFieldTransformer,
    TableAdapter,
    TextFieldFilterType,
    ThumbnailFieldTransformer,
    TimeFieldTransformer,
    TranslationFieldTransformer,
    TreeTableAdapter,
} from './containers/List';
import FieldBlocks, {
    blockPreviewTransformerRegistry,
    DateTimeBlockPreviewTransformer,
    SelectBlockPreviewTransformer,
    SingleSelectBlockPreviewTransformer,
    SmartContentBlockPreviewTransformer,
    StringBlockPreviewTransformer,
    StripHtmlBlockPreviewTransformer,
    TimeBlockPreviewTransformer,
} from './containers/FieldBlocks';
import {
    bundlesConditionDataProvider,
    Checkbox,
    ColorPicker,
    conditionDataProviderRegistry,
    ChangelogLine,
    DatePicker,
    Email,
    fieldRegistry,
    Heading,
    Input,
    localeConditionDataProvider,
    Select,
    Number,
    parentConditionDataProvider,
    userConditionDataProvider,
    PasswordConfirmation,
    Phone,
    QRCode,
    Selection,
    SingleSelect,
    SingleSelection,
    SmartContent,
    TextArea,
    TextEditor,
    Url,
    Link,
} from './containers/Form';
import {textEditorRegistry} from './containers/TextEditor';
import Form, {
    formToolbarActionRegistry,
    CopyToolbarAction as FormCopyToolbarAction,
    CopyLocaleToolbarAction as FormCopyLocaleToolbarAction,
    DeleteDraftToolbarAction as FormDeleteDraftToolbarAction,
    DeleteToolbarAction as FormDeleteToolbarAction,
    DropdownToolbarAction as FormDropdownToolbarAction,
    SaveToolbarAction as FormSaveToolbarAction,
    PublishToolbarAction as FormPublishToolbarAction,
    SaveWithFormDialogToolbarAction as FormSaveWithFormDialogToolbarAction,
    SaveWithPublishingToolbarAction as FormSaveWithPublishingToolbarAction,
    SetUnpublishedToolbarAction as FormSetUnpublishedToolbarAction,
    TypeToolbarAction as FormTypeToolbarAction,
    TogglerToolbarAction as FormTogglerToolbarAction,
    UpdateFormStoreToolbarAction as FormUpdateFormStoreToolbarAction,
    ReloadFormStoreToolbarAction as FormReloadFormStoreToolbarAction,
} from './views/Form';
import {navigationRegistry} from './containers/Navigation';
import {smartContentConfigStore} from './containers/SmartContent';
import PreviewForm from './views/PreviewForm';
import FormOverlayList from './views/FormOverlayList';
import {initializeJexl} from './utils/jexl';
import {ExternalLinkTypeOverlay, LinkTypeOverlay} from './containers/Link';
import linkTypeRegistry from './containers/Link/registries/linkTypeRegistry';
import AiApplication from './containers/AiApplication';

configure({enforceActions: 'observed'});

if (!window.ResizeObserver) {
    window.ResizeObserver = ResizeObserver;
}

window.log = log;
log.setDefaultLevel(process.env.NODE_ENV === 'production' ? log.levels.WARN : log.levels.TRACE);

Requester.handleResponseHooks.push(logoutOnUnauthorizedResponse);

initializeJexl();

const FIELD_TYPE_BLOCK = 'block';
const FIELD_TYPE_CHANGELOG_LINE = 'changelog_line';
const FIELD_TYPE_CHECKBOX = 'checkbox';
const FIELD_TYPE_COLOR = 'color';
const FIELD_TYPE_DATE = 'date';
const FIELD_TYPE_DATE_TIME = 'datetime';
const FIELD_TYPE_EMAIL = 'email';
const FIELD_TYPE_HEADING = 'heading';
const FIELD_TYPE_NUMBER = 'number';
const FIELD_TYPE_PASSWORD_CONFIRMATION = 'password_confirmation';
const FIELD_TYPE_PHONE = 'phone';
const FIELD_TYPE_QRCODE = 'qrcode';
const FIELD_TYPE_SELECT = 'select';
const FIELD_TYPE_SINGLE_SELECT = 'single_select';
const FIELD_TYPE_SMART_CONTENT = 'smart_content';
const FIELD_TYPE_TEXT_AREA = 'text_area';
const FIELD_TYPE_TEXT_EDITOR = 'text_editor';
const FIELD_TYPE_TEXT_LINE = 'text_line';
const FIELD_TYPE_TIME = 'time';
const FIELD_TYPE_URL = 'url';
const FIELD_TYPE_LINK = 'link';

initializer.addUpdateConfigHook('sulu_admin', (config: Object, initialized: boolean) => {
    if (!initialized) {
        registerBlockPreviewTransformers();
        registerListAdapters();
        registerListFieldFilterTypes();
        registerListFieldTransformers();
        registerListItemActions();
        registerFieldTypes(config.fieldTypeOptions);
        registerTextEditors();
        registerInternalLinkTypes(config.internalLinkTypes);
        registerFormToolbarActions();
        registerListToolbarActions();
        registerViews();

        conditionDataProviderRegistry.add(bundlesConditionDataProvider);
        conditionDataProviderRegistry.add(localeConditionDataProvider);
        conditionDataProviderRegistry.add(parentConditionDataProvider);
        conditionDataProviderRegistry.add(userConditionDataProvider);
    }

    processConfig(config);

    userStore.setUser(config.user);
    userStore.setContact(config.contact);
    userStore.setLoggedIn(true);
});

function registerViews() {
    viewRegistry.add('sulu_admin.form', Form);
    viewRegistry.add('sulu_admin.preview_form', PreviewForm);
    viewRegistry.add('sulu_admin.list', List);
    viewRegistry.add('sulu_admin.form_overlay_list', FormOverlayList);
    viewRegistry.add('sulu_admin.resource_tabs', ResourceTabs, {disableDefaultSpacing: true});
    viewRegistry.add('sulu_admin.tabs', Tabs, {disableDefaultSpacing: true});
}

function registerListAdapters() {
    listAdapterRegistry.add('column_list', ColumnListAdapter);
    listAdapterRegistry.add('folder', FolderAdapter);
    listAdapterRegistry.add('table', TableAdapter);
    // @deprecated use adapterOptions to set the correct skin
    listAdapterRegistry.add('table_light', TableAdapter, {skin: 'light'});
    listAdapterRegistry.add('tree_table', TreeTableAdapter);
    // @deprecated use adapterOptions to set the correct skin
    listAdapterRegistry.add('tree_table_slim', TreeTableAdapter, {showHeader: false});
}

function registerListFieldFilterTypes() {
    listFieldFilterTypeRegistry.add('boolean', BooleanFieldFilterType);
    listFieldFilterTypeRegistry.add('date', DateFieldFilterType, {timeFormat: false});
    listFieldFilterTypeRegistry.add('datetime', DateFieldFilterType, {timeFormat: true});
    listFieldFilterTypeRegistry.add('select', SelectFieldFilterType);
    listFieldFilterTypeRegistry.add('number', NumberFieldFilterType);
    listFieldFilterTypeRegistry.add('selection', SelectionFieldFilterType);
    listFieldFilterTypeRegistry.add('text', TextFieldFilterType);
}

function registerListFieldTransformers() {
    listFieldTransformerRegistry.add('array', new ArrayFieldTransformer());
    listFieldTransformerRegistry.add('bytes', new BytesFieldTransformer());
    listFieldTransformerRegistry.add('date', new DateFieldTransformer());
    listFieldTransformerRegistry.add('time', new TimeFieldTransformer());
    listFieldTransformerRegistry.add('datetime', new DateTimeFieldTransformer());
    listFieldTransformerRegistry.add('number', new NumberFieldTransformer());
    listFieldTransformerRegistry.add('string', new StringFieldTransformer());
    listFieldTransformerRegistry.add('thumbnails', new ThumbnailFieldTransformer());
    listFieldTransformerRegistry.add('bool', new BoolFieldTransformer());
    listFieldTransformerRegistry.add('color', new ColorFieldTransformer());
    listFieldTransformerRegistry.add('icon', new IconFieldTransformer());
    listFieldTransformerRegistry.add('html', new HtmlFieldTransformer());
    listFieldTransformerRegistry.add('translation', new TranslationFieldTransformer());

    // TODO: Remove this type when not needed anymore
    listFieldTransformerRegistry.add('title', new StringFieldTransformer());
}

function registerListItemActions() {
    listItemActionRegistry.add('link', ListLinkItemAction);
    listItemActionRegistry.add('detail_link', ListDetailLinkItemAction);
}

function registerFieldTypes(fieldTypeOptions) {
    fieldRegistry.add(FIELD_TYPE_BLOCK, FieldBlocks);
    fieldRegistry.add(FIELD_TYPE_CHANGELOG_LINE, ChangelogLine);
    fieldRegistry.add(FIELD_TYPE_CHECKBOX, Checkbox);
    fieldRegistry.add(FIELD_TYPE_COLOR, ColorPicker);
    fieldRegistry.add(FIELD_TYPE_DATE, DatePicker, {dateFormat: true, timeFormat: false});
    fieldRegistry.add(FIELD_TYPE_DATE_TIME, DatePicker, {dateFormat: true, timeFormat: true});
    fieldRegistry.add(FIELD_TYPE_EMAIL, Email);
    fieldRegistry.add(FIELD_TYPE_HEADING, Heading);
    fieldRegistry.add(FIELD_TYPE_SELECT, Select);
    fieldRegistry.add(FIELD_TYPE_NUMBER, Number);
    fieldRegistry.add(FIELD_TYPE_PASSWORD_CONFIRMATION, PasswordConfirmation);
    fieldRegistry.add(FIELD_TYPE_PHONE, Phone);
    fieldRegistry.add(FIELD_TYPE_QRCODE, QRCode);
    fieldRegistry.add(FIELD_TYPE_SMART_CONTENT, SmartContent);
    fieldRegistry.add(FIELD_TYPE_SINGLE_SELECT, SingleSelect);
    fieldRegistry.add(FIELD_TYPE_TEXT_AREA, TextArea);
    fieldRegistry.add(FIELD_TYPE_TEXT_EDITOR, TextEditor);
    fieldRegistry.add(FIELD_TYPE_TEXT_LINE, Input);
    fieldRegistry.add(FIELD_TYPE_TIME, DatePicker, {dateFormat: false, timeFormat: true});
    fieldRegistry.add(FIELD_TYPE_URL, Url);
    fieldRegistry.add(FIELD_TYPE_LINK, Link);

    registerFieldTypesWithOptions(fieldTypeOptions['selection'], Selection);
    registerFieldTypesWithOptions(fieldTypeOptions['single_selection'], SingleSelection);
}

function registerFieldTypesWithOptions(fieldTypeOptions, Component) {
    if (fieldTypeOptions) {
        for (const fieldTypeKey in fieldTypeOptions) {
            fieldRegistry.add(fieldTypeKey, Component, fieldTypeOptions[fieldTypeKey]);
        }
    }
}

function registerBlockPreviewTransformers() {
    blockPreviewTransformerRegistry.add(FIELD_TYPE_COLOR, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_DATE, new DateTimeBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_DATE_TIME, new DateTimeBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_EMAIL, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_NUMBER, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_PHONE, new StringBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_SELECT, new SelectBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_SINGLE_SELECT, new SingleSelectBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_SMART_CONTENT, new SmartContentBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TEXT_AREA, new StringBlockPreviewTransformer(), 512);
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TEXT_EDITOR, new StripHtmlBlockPreviewTransformer(), 512);
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TEXT_LINE, new StringBlockPreviewTransformer(), 1024);
    blockPreviewTransformerRegistry.add(FIELD_TYPE_TIME, new TimeBlockPreviewTransformer());
    blockPreviewTransformerRegistry.add(FIELD_TYPE_URL, new StringBlockPreviewTransformer());
}

function registerTextEditors() {
    textEditorRegistry.add('ckeditor5', CKEditor5);
}

function registerInternalLinkTypes(internalLinkTypes) {
    for (const internalLinkTypeKey in internalLinkTypes) {
        const internalLinkType = internalLinkTypes[internalLinkTypeKey];
        linkTypeRegistry.add(
            internalLinkTypeKey,
            LinkTypeOverlay,
            internalLinkType.title,
            {
                displayProperties: internalLinkType.displayProperties,
                emptyText: internalLinkType.emptyText,
                icon: internalLinkType.icon,
                listAdapter: internalLinkType.listAdapter,
                overlayTitle: internalLinkType.overlayTitle,
                resourceKey: internalLinkType.resourceKey,
            }
        );
    }

    // Add external LinkType
    linkTypeRegistry.add(
        'external',
        ExternalLinkTypeOverlay,
        translate('sulu_admin.external_link'),
        undefined
    );
}

function registerFormToolbarActions() {
    formToolbarActionRegistry.add('sulu_admin.copy', FormCopyToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.copy_locale', FormCopyLocaleToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.delete', FormDeleteToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.delete_draft', FormDeleteDraftToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.dropdown', FormDropdownToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.save_with_publishing', FormSaveWithPublishingToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.save', FormSaveToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.publish', FormPublishToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.save_with_form_dialog', FormSaveWithFormDialogToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.set_unpublished', FormSetUnpublishedToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.type', FormTypeToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.toggler', FormTogglerToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.update_form_store', FormUpdateFormStoreToolbarAction);
    formToolbarActionRegistry.add('sulu_admin.reload_form_store', FormReloadFormStoreToolbarAction);
}

function registerListToolbarActions() {
    listToolbarActionRegistry.add('sulu_admin.add', ListAddToolbarAction);
    listToolbarActionRegistry.add('sulu_admin.delete', ListDeleteToolbarAction);
    listToolbarActionRegistry.add('sulu_admin.move', ListMoveToolbarAction);
    listToolbarActionRegistry.add('sulu_admin.export', ListExportToolbarAction);
    listToolbarActionRegistry.add('sulu_admin.upload', ListUploadToolbarAction);
}

function processConfig(config: Object) {
    routeRegistry.clear();
    resourceViewRegistry.clear();
    navigationRegistry.clear();
    resourceRouteRegistry.clear();

    routeRegistry.addCollection(config.routes);
    resourceViewRegistry.addResourceViews(config.resources);
    localizationStore.setLocalizations(config.localizations);
    navigationRegistry.set(config.navigation);

    resourceRouteRegistry.setEndpoints(config.resources);
    smartContentConfigStore.setConfig(config.smartContent);
    CollaborationStore.enabled = config.collaborationEnabled;
    CollaborationStore.interval = config.collaborationInterval;
}

function startAdmin() {
    // eslint-disable-next-line no-console
    console.log(
        '%cWelcome to Sulu CMS 👋' + '\n' +
        '%c\n' +
        'The Symfony based content management platform.' + '\n' +
        '\n' +
        '📖 Developer documentation: %chttps://docs.sulu.io/%c,' +
        ' %chttps://jsdocs.sulu.io/%c and %chttps://symfony.com/doc%c' + '\n' +
        '🤝 Contribute to Sulu: %chttps://github.com/sulu/sulu%c' + '\n' +
        '🔎 Create a new issue: %chttps://github.com/sulu/sulu/issues%c' + '\n' +
        '🪜 Implementation examples: %chttps://github.com/sulu/sulu-demo%c' +
        ' and %chttps://github.com/sulu/sulu-workshop%c' + '\n' +
        '\n' +
        'If you like Sulu – give it a ⭐ on Github: %chttps://github.com/sulu/sulu%c',
        'font-family: monospace; font-size: 24px; font-weight: bold;',
        'font-family: monospace; font-size: 10px;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;',
        'font-family: monospace; font-size: 10px; text-decoration: underline;',
        'font-family: monospace; font-size: 10px; text-decoration: none;'
    );

    const id = 'application';
    const applicationElement = document.getElementById(id);

    if (!applicationElement) {
        throw new Error(`DOM element with ID "${id}" was not found!`);
    }

    if (!('config' in applicationElement.dataset)) {
        throw new Error(`Attribute "data-config" not found on element with ID "${id}"!`);
    }

    Object.assign(Config, JSON.parse(applicationElement.dataset.config));
    Object.freeze(Config);

    if (Config.suluVersion !== SULU_ADMIN_BUILD_VERSION) {
        log.error(
            'Sulu administration interface: JavaScript build version mismatch' +
            '\nJavaScript build of the Sulu administration interface does not match the version of the Sulu backend.' +
            '\nBackend version: ' + Config.suluVersion + ', JavaScript build version: ' + SULU_ADMIN_BUILD_VERSION +
            '\n\nHave you forgotten to update the build while upgrading your application?' + '' +
            '\nhttps://docs.sulu.io/en/latest/upgrades/upgrade-2.x.html'
        );
    }

    const router = new Router(createHashHistory());
    router.addUpdateAttributesHook(updateRouterAttributesFromView);
    router.addUpdateAttributesHook(updateRouterAttributesFromUserStoreContentLocale);
    router.addUpdateRouteHook(updateUserStoreContentLocaleFromRouterAttributes, -1024);

    initializer.initialize(Config.initialLoginState).then(() => {
        router.reload();
    });

    render(
        <Application appVersion={Config.appVersion} router={router} suluVersion={Config.suluVersion} />,
        applicationElement
    );
}

initializer.addUpdateConfigHook('sulu_ai', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

    if (undefined === config){
        return;
    }

    const div = document.createElement('div');
    div.id = 'su-ai-application';
    document.body?.appendChild(div);

    if (!config['writing_assistant']?.enabled && !config['translation']?.enabled && !config['feedback']?.enabled) {
        return;
    }

    render(<AiApplication
        feedback={config['feedback']}
        translation={config['translation']}
        writingAssistant={config['writing_assistant']}
    />, div);
});

export {
    startAdmin,
};
