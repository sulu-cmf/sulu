/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import {observable, toJS, when} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import metadataStore from '../../stores/MetadataStore';

jest.mock('../../stores/MetadataStore', () => ({
    getSchema: jest.fn(),
}));

function LoadingStrategy() {
    this.load = jest.fn().mockReturnValue({then: jest.fn()});
    this.initialize = jest.fn();
    this.reset = jest.fn();
    this.destroy = jest.fn();
}

class StructureStrategy {
    @observable data = [];
    clear = jest.fn();
    getData = jest.fn().mockReturnValue(this.data);
    enhanceItem = jest.fn((item) => item);
}

test('The loading strategy should be called when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable(1);
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    structureStrategy.getData.mockReturnValue([]);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        toJS(datagridStore.data),
        'tests',
        {
            locale: undefined,
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different resourceKey when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable(1);
    const locale = observable();
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: undefined,
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different page when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable(1);
    const locale = observable();
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: undefined,
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    page.set(3);
    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: undefined,
            page: 3,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with a different locale when a request is sent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable(1);
    const locale = observable('en');
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
            locale,
        },
        {
            test: 'value',
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data, 'snippets',
        {
            locale: 'en',
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    locale.set('de');
    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            locale: 'de',
            page: 1,
            test: 'value',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The loading strategy should be called with the active item as parent', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable(1);
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.setActive('some-uuid');
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            page: 1,
            parent: 'some-uuid',
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('The active item should not be passed as parent if undefined', () => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    const page = observable(1);
    const datagridStore = new DatagridStore(
        'snippets',
        {
            page,
        },
        {
            parent: 9,
        }
    );

    const data = [{id: 1}];
    structureStrategy.getData.mockReturnValue(data);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    expect(loadingStrategy.load).toBeCalledWith(
        data,
        'snippets',
        {
            page: 1,
            parent: 9,
        },
        structureStrategy.enhanceItem
    );

    datagridStore.destroy();
});

test('Set loading flag to true before request', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {page});
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    page.set(1);
    datagridStore.setLoading(false);
    datagridStore.sendRequest();
    expect(datagridStore.loading).toEqual(true);
    datagridStore.destroy();
});

test('Set loading flag to false after request', (done) => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {page});
    const promise = Promise.resolve({
        pages: 3,
    });
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();
    loadingStrategy.load.mockReturnValue(promise);
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);
    datagridStore.sendRequest();
    return promise.then(() => {
        expect(datagridStore.loading).toEqual(false);
        datagridStore.destroy();
        done();
    });
});

test('Get fields from MetadataStore for correct resourceKey', () => {
    const fields = {
        test: {},
    };
    metadataStore.getSchema.mockReturnValue(fields);

    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    expect(datagridStore.getSchema()).toBe(fields);
    expect(metadataStore.getSchema).toBeCalledWith('tests');
    datagridStore.destroy();
});

test('After initialization no row should be selected', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    expect(datagridStore.selections.length).toBe(0);
    datagridStore.destroy();
});

test('Select an item', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.select(1);
    datagridStore.select(2);
    expect(toJS(datagridStore.selections)).toEqual([1, 2]);

    datagridStore.deselect(1);
    expect(toJS(datagridStore.selections)).toEqual([2]);
    datagridStore.destroy();
});

test('Deselect an item that has not been selected yet', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.select(1);
    datagridStore.deselect(2);

    expect(toJS(datagridStore.selections)).toEqual([1]);
    datagridStore.destroy();
});

test('Select the entire page', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {page});
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.structureStrategy.data = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [1, 7];
    datagridStore.selectEntirePage();
    expect(toJS(datagridStore.selections)).toEqual([1, 7, 2, 3]);
    datagridStore.destroy();
});

test('Deselect the entire page', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.structureStrategy.data = [{id: 1}, {id: 2}, {id: 3}];
    datagridStore.selections = [1, 2, 7];
    datagridStore.deselectEntirePage();
    expect(toJS(datagridStore.selections)).toEqual([7]);
    datagridStore.destroy();
});

test('Clear the selection', () => {
    const page = observable();
    const datagridStore = new DatagridStore('tests', {
        page,
    });
    datagridStore.updateStrategies(new LoadingStrategy(), new StructureStrategy());
    datagridStore.selections = [1, 4, 5];
    page.set(1);
    expect(datagridStore.selections).toHaveLength(3);

    datagridStore.clearSelection();
    expect(datagridStore.selections).toHaveLength(0);
});

test('Nothing should happen when to the same loading strategy is changed', () => {
    const loadingStrategy = new LoadingStrategy();

    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );

    datagridStore.updateStrategies(loadingStrategy, new StructureStrategy());
    datagridStore.updateLoadingStrategy(loadingStrategy);

    expect(loadingStrategy.initialize).toBeCalled();
    expect(loadingStrategy.reset).not.toBeCalled();
    expect(loadingStrategy.destroy).not.toBeCalled();

    datagridStore.destroy();
});

test('The initialize and destroy method of the loading strategies should be called on change', () => {
    const loadingStrategy1 = new LoadingStrategy();
    const loadingStrategy2 = new LoadingStrategy();

    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );

    datagridStore.updateStrategies(loadingStrategy1, new StructureStrategy());
    datagridStore.updateLoadingStrategy(loadingStrategy2);

    expect(loadingStrategy1.destroy).toBeCalled();
    expect(loadingStrategy2.reset).toBeCalledWith(datagridStore);
    expect(loadingStrategy2.initialize).toBeCalledWith(datagridStore);

    datagridStore.destroy();
});

test('Should reset the data array and set page to 1 when the reload method is called', (done) => {
    const loadingStrategy = new LoadingStrategy();
    const structureStrategy = new StructureStrategy();

    const promise = Promise.resolve({});
    loadingStrategy.load.mockReturnValue(promise);

    const page = observable();
    const locale = observable();
    const datagridStore = new DatagridStore(
        'tests',
        {
            page,
            locale,
        },
        {}
    );
    datagridStore.updateStrategies(loadingStrategy, structureStrategy);

    page.set(3);
    locale.set('en');

    when(
        () => !datagridStore.loading,
        () => {
            expect(page.get()).toBe(3);

            datagridStore.reload();
            expect(structureStrategy.clear).toBeCalled();

            expect(page.get()).toBe(1);
            expect(loadingStrategy.load).toBeCalled();

            datagridStore.destroy();
            done();
        }
    );
});
