// @flow
import {action, observable} from 'mobx';
import type {Localization} from './types';

class LocalizationStore {
    @observable localizations: Array<Localization> = [];

    @action setLocalizations(localizations: Array<Localization>) {
        this.localizations = localizations;
    }
}

export default new LocalizationStore();
