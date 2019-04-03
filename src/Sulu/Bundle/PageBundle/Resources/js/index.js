// @flow
import {bundleReady, initializer} from 'sulu-admin-bundle/services';
import {fieldRegistry, viewRegistry} from 'sulu-admin-bundle/containers';
import {formToolbarActionRegistry} from 'sulu-admin-bundle/views';
import SearchResult from './containers/Form/fields/SearchResult';
import PageSettingsNavigationSelect from './containers/Form/fields/PageSettingsNavigationSelect';
import PageSettingsShadowLocaleSelect from './containers/Form/fields/PageSettingsShadowLocaleSelect';
import EditToolbarAction from './views/Form/toolbarActions/EditToolbarAction';
import TemplateToolbarAction from './views/Form/toolbarActions/TemplateToolbarAction';
import PageTabs from './views/PageTabs';
import WebspaceOverview from './views/WebspaceOverview';
import WebspaceTabs from './views/WebspaceTabs';

initializer.addUpdateConfigHook('sulu_page', (config: Object) => {
    WebspaceOverview.clearCacheEndpoint = config.endpoints.clearCache;
});

viewRegistry.add('sulu_page.page_tabs', PageTabs);
viewRegistry.add('sulu_page.webspace_overview', WebspaceOverview);
viewRegistry.add('sulu_page.webspace_tabs', WebspaceTabs);

fieldRegistry.add('search_result', SearchResult);
fieldRegistry.add('page_settings_navigation_select', PageSettingsNavigationSelect);
fieldRegistry.add('page_settings_shadow_locale_select', PageSettingsShadowLocaleSelect);

formToolbarActionRegistry.add('sulu_page.edit', EditToolbarAction);
formToolbarActionRegistry.add('sulu_page.templates', TemplateToolbarAction);

bundleReady();
