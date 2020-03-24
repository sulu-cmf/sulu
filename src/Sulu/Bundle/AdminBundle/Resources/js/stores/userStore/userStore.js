// @flow
import 'core-js/library/fn/promise';
import {action, computed, observable} from 'mobx';
import debounce from 'debounce';
import {Config, Requester} from '../../services';
import initializer from '../../services/initializer';
import localizationStore from '../localizationStore';
import type {Contact, User} from './types';

const UPDATE_PERSISTENT_SETTINGS_DELAY = 2500;
const CONTENT_LOCALE_SETTING_KEY = 'sulu_admin.content_locale';

class UserStore {
    @observable persistentSettings: Map<string, string> = new Map();
    dirtyPersistentSettings: Array<string> = [];

    @observable user: ?User = undefined;
    @observable contact: ?Contact = undefined;

    @observable loggedIn: boolean = false;
    @observable loading: boolean = false;
    @observable loginError: boolean = false;
    @observable forgotPasswordSuccess: boolean = false;

    @action clear() {
        this.persistentSettings = new Map();
        this.loggedIn = false;
        this.loading = false;
        this.user = undefined;
        this.contact = undefined;
        this.loginError = false;
        this.forgotPasswordSuccess = false;
    }

    @computed get systemLocale() {
        return this.user ? this.user.locale : Config.fallbackLocale;
    }

    @action setLoggedIn(loggedIn: boolean) {
        this.loggedIn = loggedIn;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    @action setLoginError(loginError: boolean) {
        this.loginError = loginError;
    }

    @action setForgotPasswordSuccess(forgotPasswordSuccess: boolean) {
        this.forgotPasswordSuccess = forgotPasswordSuccess;
    }

    @computed get contentLocale(): string {
        const contentLocale = this.persistentSettings.get(CONTENT_LOCALE_SETTING_KEY);

        if (contentLocale) {
            return contentLocale;
        }

        const {localizations} = localizationStore;

        const defaultLocalizations = localizations.filter((localization) => localization.default);
        const fallbackLocalization = defaultLocalizations.length ? defaultLocalizations[0] : localizations[0];

        return fallbackLocalization ? fallbackLocalization.locale : Config.fallbackLocale;
    }

    @action setUser(user: User) {
        this.user = user;

        const persistentSettings = this.user.settings;
        Object.keys(persistentSettings).forEach((key) => {
            this.persistentSettings.set(key, persistentSettings[key]);
        });
    }

    @action updateContentLocale(contentLocale: string) {
        this.setPersistentSetting(CONTENT_LOCALE_SETTING_KEY, contentLocale);
    }

    @action setContact(contact: Contact) {
        this.contact = contact;
    }

    @action setFullName(fullName: string){
        if (this.contact){
            this.contact.fullName = fullName;
        }
    }

    handleLogin = (user: string) => {
        if (this.user) {
            // when the user was logged in already and comes again with the same user
            // we don't need to initialize again
            if (user === this.user.username) {
                this.setLoggedIn(true);
                this.setLoading(false);

                return;
            }

            this.clear();
        }

        this.setLoading(true);
        return initializer.initialize(true).then(() => {
            this.setLoading(false);
        });
    };

    login = (user: string, password: string) => {
        this.setLoading(true);

        return Requester.post(Config.endpoints.loginCheck, {username: user, password: password})
            .then(() => this.handleLogin(user))
            .catch((error) => {
                this.setLoading(false);
                if (error.status !== 401) {
                    return Promise.reject(error);
                }

                this.setLoginError(true);
            });
    };

    forgotPassword(user: string) {
        this.setLoading(true);

        if (this.forgotPasswordSuccess) {
            // if email was already sent use different api
            return Requester.post(Config.endpoints.forgotPasswordResend, {user: user})
                .then(() => {
                    this.setLoading(false);
                })
                .catch((error) => {
                    if (error.status !== 400) {
                        return Promise.reject(error);
                    }
                    this.setLoading(false);
                });
        }

        return Requester.post(Config.endpoints.forgotPasswordReset, {user: user})
            .then(() => {
                this.setLoading(false);
                this.setForgotPasswordSuccess(true);
            })
            .catch((error) => {
                this.setLoading(false);
                this.setForgotPasswordSuccess(true);
                if (error.status !== 400) {
                    return Promise.reject(error);
                }
            });
    }

    resetPassword(password: string, token: string) {
        this.setLoading(true);

        return Requester.post(Config.endpoints.resetPassword, {password, token})
            .then(({user}) => this.handleLogin(user))
            .catch(() => {
                this.setLoading(false);
            });
    }

    logout() {
        return Requester.get(Config.endpoints.logout).then(() => {
            this.setLoggedIn(false);
        });
    }

    updatePersistentSettings = debounce(() => {
        const persistentSettings = this.dirtyPersistentSettings.reduce((persistentSettings, persistentSettingKey) => {
            if (this.persistentSettings.has(persistentSettingKey)) {
                persistentSettings[persistentSettingKey] = this.persistentSettings.get(persistentSettingKey);
            }
            return persistentSettings;
        }, {});

        Requester.patch(Config.endpoints.profileSettings, persistentSettings);

        this.dirtyPersistentSettings.splice(0, this.dirtyPersistentSettings.length);
    }, UPDATE_PERSISTENT_SETTINGS_DELAY);

    @action setPersistentSetting(key: string, value: *) {
        if (this.persistentSettings.get(key) === value) {
            return;
        }

        this.persistentSettings.set(key, value);
        this.dirtyPersistentSettings.push(key);
        this.updatePersistentSettings();
    }

    getPersistentSetting(key: string): * {
        const value = this.persistentSettings.get(key);

        return value;
    }
}

export default new UserStore();
