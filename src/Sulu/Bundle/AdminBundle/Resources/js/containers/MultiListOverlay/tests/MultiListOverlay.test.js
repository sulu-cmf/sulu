// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlay from '../../../containers/ListOverlay';
import MultiListOverlay from '../MultiListOverlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const ExampleList = function ExampleList(props) {
    return <div className={props.adapter ? props.className : null} />;
};

jest.mock('../../../containers/ListOverlay', () => jest.fn(function ListOverlay(props) {
    return <ExampleList adapter={props.adapter} className="list" />;
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn(
    function(resourceKey, listKey, userSettingsKey, observableOptions, options) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.observableOptions = observableOptions;
        this.select = jest.fn();
        this.clear = jest.fn();
        this.reset = jest.fn();
        this.selections = [];
        this.selectionIds = [];
    }
));

test('Should instantiate the ListStore with locale, excluded-ids and options', () => {
    const locale = observable.box('en');
    const options = {};

    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={locale}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={options}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.instance().listStore.listKey).toEqual('snippets_list');
    expect(multiListOverlay.instance().listStore.resourceKey).toEqual('snippets');
    expect(multiListOverlay.instance().listStore.observableOptions.locale.get()).toEqual('en');
    expect(multiListOverlay.instance().listStore.observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(multiListOverlay.instance().listStore.options).toBe(options);
});

test('Should update options of ListStore if the options prop is changed', () => {
    const oldOptions = {key: 'value-1'};

    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={oldOptions}
            resourceKey="snippets"
            title="Selection"
        />
    );
    multiListOverlay.instance().listStore.selectionIds = [12, 14];

    expect(multiListOverlay.instance().listStore.reset).not.toBeCalled();
    expect(multiListOverlay.instance().listStore.options).toEqual(oldOptions);

    const newOptions = {key: 'value-2'};
    multiListOverlay.setProps({
        options: newOptions,
    });

    expect(multiListOverlay.instance().listStore.reset).toBeCalled();
    expect(multiListOverlay.instance().listStore.initialSelectionIds).toEqual([12, 14]);
    expect(multiListOverlay.instance().listStore.options).toEqual(newOptions);
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={oldOptions}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.instance().listStore.reset).not.toBeCalled();

    const newOldOptions = {key: 'value-1'};
    multiListOverlay.setProps({
        options: newOldOptions,
    });

    expect(multiListOverlay.instance().listStore.reset).not.toBeCalled();
});

test('Should clear ListStore if the excludedIds prop is changed', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.instance().listStore.clear).not.toBeCalled();

    multiListOverlay.setProps({
        excludedIds: ['id-3'],
    });

    expect(multiListOverlay.instance().listStore.clear).toBeCalled();
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={observable.box('en')}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.instance().listStore.clear).not.toBeCalled();

    multiListOverlay.setProps({
        excludedIds: ['id-1', 'id-2'],
    });

    expect(multiListOverlay.instance().listStore.clear).not.toBeCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids and options', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.instance().listStore.observableOptions.locale).toEqual(undefined);
    expect(multiListOverlay.instance().listStore.observableOptions.excludedIds.get()).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('overlayType')).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            overlayType="dialog"
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('overlayType')).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            allowActivateForDisabledItems={true}
            disabledIds={disabledIds}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(multiListOverlay.find(ListOverlay).prop('allowActivateForDisabledItems')).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            reloadOnOpen={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('reloadOnOpen')).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            clearSelectionOnClose={true}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('clearSelectionOnClose')).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            allowActivateForDisabledItems={false}
            disabledIds={disabledIds}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(multiListOverlay.find(ListOverlay).prop('allowActivateForDisabledItems')).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            itemDisabledCondition='status == "inactive"'
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('itemDisabledCondition')).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the ListOverlay', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            confirmLoading={true}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{}]}
            resourceKey="snippets"
            title="Test"
        />
    );

    expect(multiListOverlay.find(ListOverlay).prop('confirmLoading')).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const multiListOverlay = mount(
        <MultiListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const listStore = multiListOverlay.instance().listStore;
    listStore.selections = [{id: 1}, {id: 2}];

    expect(confirmSpy).not.toBeCalled();
    multiListOverlay.find(ListOverlay).prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith([{id: 1}, {id: 2}]);
});

test('Should select the preSelectedItems in the ListStore', () => {
    shallow(
        <MultiListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(ListStore).toBeCalledWith(
        'snippets',
        'snippets',
        'multi_list_overlay',
        expect.anything(),
        undefined,
        undefined,
        [1, 2, 3]
    );
});

test('Should not add the preSelectedItems to the ListStore if preloadSelectedItems is set to false', () => {
    shallow(
        <MultiListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preloadSelectedItems={false}
            preSelectedItems={[{id: 1}, {id: 2}, {id: 3}]}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(ListStore).toBeCalledWith(
        'snippets',
        'snippets',
        'multi_list_overlay',
        expect.anything(),
        undefined,
        undefined,
        undefined
    );
});

test('Should not fail when preSelectedItems is undefined', () => {
    const multiListOverlay = shallow(
        <MultiListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const listStore = multiListOverlay.instance().listStore;

    expect(listStore.select).not.toBeCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    const multiListOverlay1 = mount(
        <MultiListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(multiListOverlay1.find(ListOverlay).prop('adapter')).toEqual('table');

    const multiListOverlay2 = mount(
        <MultiListOverlay
            adapter="column_list"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(multiListOverlay2.find(ListOverlay).prop('adapter')).toEqual('column_list');
});
