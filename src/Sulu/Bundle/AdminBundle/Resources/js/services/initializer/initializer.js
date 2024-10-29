// @flow
import {action, computed, observable} from 'mobx';
import moment from 'moment';
import Config from '../Config';
import {setTranslations} from '../../utils/Translator';
import Requester from '../Requester';
import {resourceRouteRegistry} from '../ResourceRequester';
import type {UpdateConfigHook} from './types';

function getBrowserLanguage() {
    // detect browser locale (ie, ff, chrome fallbacks)
    const language = window.navigator.languages ? window.navigator.languages[0] : null;

    return language || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;
}

function getDefaultLocale() {
    const browserLanguage = getBrowserLanguage();

    // select only language
    const locale = browserLanguage.slice(0, 2).toLowerCase();
    if (Config.translations.indexOf(locale) === -1) {
        return Config.fallbackLocale;
    }

    return locale;
}

function setMomentLocale() {
    moment.locale(getBrowserLanguage());
}

class Initializer {
    @observable config: ?{[string]: Object};
    @observable initialized: boolean = false;
    @observable initializedTranslationsLocale: ?string;
    @observable loading: boolean = false;
    updateConfigHooks: {[string]: Array<UpdateConfigHook>} = {};

    @computed get bundles(): Array<string> {
        if (!this.config) {
            return [];
        }

        return Object.keys(this.config);
    }

    @action clear() {
        this.initialized = false;
        this.initializedTranslationsLocale = undefined;
        this.loading = false;
        this.config = undefined;
    }

    @action setInitialized() {
        this.initialized = true;
    }

    @action setInitializedTranslationsLocale(locale: string) {
        this.initializedTranslationsLocale = locale;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    addUpdateConfigHook(bundle: string, hook: UpdateConfigHook) {
        if (!this.updateConfigHooks[bundle]) {
            this.updateConfigHooks[bundle] = [];
        }
        this.updateConfigHooks[bundle].push(hook);
    }

    initializeSymfonyRouting() {
        return Requester.get(Config.endpoints.routing).then((data) => {
            resourceRouteRegistry.setRoutingData(data);
        });
    }

    initializeTranslations(locale: string) {
        return this.initializedTranslationsLocale === locale
            ? Promise.resolve()
            : Requester.get(Config.endpoints.translations + '?locale=' + locale).then((translations) => {
                setTranslations(translations, locale);
                this.setInitializedTranslationsLocale(locale);
            });
    }

    initialize(userIsLoggedIn: boolean) {
        this.setLoading(true);

        // the config and the routes are accessible only for authenticated users
        // if no user is logged in, we do not want to fetch this data to prevent unnecessary 401 responses
        // a 401 response will reset cached basic auth credentials and lead to a second authentication prompt
        if (!userIsLoggedIn) {
            return this.initializeTranslations(getDefaultLocale())
                .then(() => {
                    this.setLoading(false);
                });
        }

        const configPromise = Requester.get(Config.endpoints.config);
        const routePromise = this.initializeSymfonyRouting();

        return Promise.all([configPromise, routePromise])
            .then(action(([config]) => {
                const locale = config?.sulu_admin?.user?.locale || getDefaultLocale();

                return this.initializeTranslations(locale).then(
                    () => {
                        return config;
                    }
                );
            }))
            .then(action((config) => {
                this.config = config;

                if (!this.initialized) {
                    setMomentLocale();
                }

                for (const bundle in this.updateConfigHooks) {
                    this.updateConfigHooks[bundle].forEach((hook) => {
                        hook(config[bundle], this.initialized);
                    });
                }

                this.setInitialized();
                return Promise.resolve().then(() => {
                    this.setLoading(false);
                });
            }))
            .catch((error) => {
                if (error.status !== 401) {
                    return Promise.reject(error);
                }
                return Promise.resolve().then(() => {
                    this.setLoading(false);
                });
            });
    }
}

export default new Initializer();
