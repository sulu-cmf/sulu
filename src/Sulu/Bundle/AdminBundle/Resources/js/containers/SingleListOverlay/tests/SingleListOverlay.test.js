// @flow
import React from 'react';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import {mount, shallow} from 'enzyme';
import ListStore from '../../../containers/List/stores/ListStore';
import ListOverlay from '../../../containers/ListOverlay';
import SingleListOverlay from '../SingleListOverlay';

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
    function(resourceKey, listKey, userSettingsKey, observableOptions, options, metadataOptions) {
        this.resourceKey = resourceKey;
        this.listKey = listKey;
        this.userSettingsKey = userSettingsKey;
        this.options = options;
        this.metadataOptions = metadataOptions;
        this.observableOptions = observableOptions;
        this.select = jest.fn();

        mockExtendObservable(this, {
            selections: [],
        });

        this.clear = jest.fn();
        this.reset = jest.fn();
        this.clearSelection = jest.fn();
        this.destroy = jest.fn();
    }
));

test('Should instantiate the ListStore with locale, excluded-ids, options and metadataOptions', () => {
    const locale = observable.box('en');
    const options = {};
    const metadataOptions = {id: 2};

    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets_list"
            locale={locale}
            metadataOptions={metadataOptions}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            options={options}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleListOverlay.instance().listStore.listKey).toEqual('snippets_list');
    expect(singleListOverlay.instance().listStore.resourceKey).toEqual('snippets');
    expect(singleListOverlay.instance().listStore.observableOptions.locale.get()).toEqual('en');
    expect(singleListOverlay.instance().listStore.observableOptions.excludedIds.get()).toEqual(['id-1', 'id-2']);
    expect(singleListOverlay.instance().listStore.options).toBe(options);
    expect(singleListOverlay.instance().listStore.metadataOptions).toBe(metadataOptions);
});

test('Should update options of ListStore if the options prop is changed', () => {
    const oldOptions = {key: 'value-1'};

    const singleListOverlay = shallow(
        <SingleListOverlay
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
    singleListOverlay.instance().listStore.selectionIds = [12, 14];

    expect(singleListOverlay.instance().listStore.reset).not.toBeCalled();
    expect(singleListOverlay.instance().listStore.options).toEqual(oldOptions);

    const newOptions = {key: 'value-2'};
    singleListOverlay.setProps({
        options: newOptions,
    });

    expect(singleListOverlay.instance().listStore.reset).toBeCalled();
    expect(singleListOverlay.instance().listStore.initialSelectionIds).toEqual([12, 14]);
    expect(singleListOverlay.instance().listStore.options).toEqual(newOptions);
});

test('Should not update options of ListStore if new value of options prop is equal to old value', () => {
    const oldOptions = {key: 'value-1'};

    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.instance().listStore.reset).not.toBeCalled();

    const newOldOptions = {key: 'value-1'};
    singleListOverlay.setProps({
        options: newOldOptions,
    });

    expect(singleListOverlay.instance().listStore.reset).not.toBeCalled();
});

test('Should instantiate the ListStore without locale, excluded-ids, options and metadataOptions', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={false}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(singleListOverlay.instance().listStore.observableOptions.locale).toEqual(undefined);
    expect(singleListOverlay.instance().listStore.observableOptions.excludedIds.get()).toEqual(undefined);
    expect(singleListOverlay.instance().listStore.options).toEqual(undefined);
    expect(singleListOverlay.instance().listStore.metadataOptions).toEqual(undefined);
});

test('Should pass overlayType overlay by default', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('overlayType')).toEqual('overlay');
});

test('Should pass overlayType dialog if it is set via props', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('overlayType')).toEqual('dialog');
});

test('Should pass disabledIds to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(singleListOverlay.find(ListOverlay).prop('allowActivateForDisabledItems')).toEqual(true);
});

test('Should pass reloadOnOpen to the ListOverlay', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('reloadOnOpen')).toEqual(true);
});

test('Should pass clearSelectionOnClose to the ListOverlay', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('clearSelectionOnClose')).toEqual(true);
});

test('Should pass allowActivateForDisabledItems to the ListOverlay', () => {
    const disabledIds = [1, 2, 5];

    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('disabledIds')).toBe(disabledIds);
    expect(singleListOverlay.find(ListOverlay).prop('allowActivateForDisabledItems')).toEqual(false);
});

test('Should pass itemDisabledCondition to the ListOverlay', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
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

    expect(singleListOverlay.find(ListOverlay).prop('itemDisabledCondition')).toBe('status == "inactive"');
});

test('Should pass confirmLoading flag to the Overlay', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            confirmLoading={true}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Test"
        />
    );

    expect(singleListOverlay.find(ListOverlay).prop('confirmLoading')).toEqual(true);
});

test('Should call onConfirm with the current selection', () => {
    const confirmSpy = jest.fn();
    const singleListOverlay = mount(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const listStore = singleListOverlay.instance().listStore;
    listStore.selections = [{id: 1}];

    expect(confirmSpy).not.toBeCalled();
    singleListOverlay.find(ListOverlay).prop('onConfirm')();

    expect(confirmSpy).toBeCalledWith({id: 1});
});

test('Should pass the id from the preSelectedItems to the ListStore', () => {
    shallow(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            metadataOptions={undefined}
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItem={{id: 1}}
            resourceKey="snippets"
            title="Selection"
        />
    );

    expect(ListStore).toBeCalledWith(
        'snippets',
        'snippets',
        'single_list_overlay',
        expect.anything(),
        undefined,
        undefined,
        [1]
    );
});

test('Should not fail when preSelectedItem is undefined', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="Selection"
        />
    );

    const listStore = singleListOverlay.instance().listStore;

    expect(listStore.select).not.toBeCalled();
});

test('Should instantiate the list with the passed adapter', () => {
    const singleListOverlay1 = mount(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(singleListOverlay1.find(ListOverlay).prop('adapter')).toEqual('table');

    const singleListOverlay2 = mount(
        <SingleListOverlay
            adapter="column_list"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );
    expect(singleListOverlay2.find(ListOverlay).prop('adapter')).toEqual('column_list');
});

test('Should only select a single item at a time', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItem={{id: 5}}
            resourceKey="snippets"
            title="test"
        />
    );

    singleListOverlay.instance().listStore.selections.push({id: 3});
    expect(singleListOverlay.instance().listStore.selections).toEqual([{id: 3}]);

    singleListOverlay.instance().listStore.selections.push({id: 5});
    expect(singleListOverlay.instance().listStore.clearSelection).toBeCalledWith();
    expect(singleListOverlay.instance().listStore.select).toBeCalledWith({id: 5});
});

test('Should clear ListStore if the excludedIds prop is changed', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );

    expect(singleListOverlay.instance().listStore.clear).not.toBeCalled();

    singleListOverlay.setProps({
        excludedIds: ['id-3'],
    });

    expect(singleListOverlay.instance().listStore.clear).toBeCalled();
});

test('Should not clear ListStore if new value of excludedIds prop is equal to old value', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            excludedIds={['id-1', 'id-2']}
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            resourceKey="snippets"
            title="test"
        />
    );

    expect(singleListOverlay.instance().listStore.clear).not.toBeCalled();

    singleListOverlay.setProps({
        excludedIds: ['id-1', 'id-2'],
    });

    expect(singleListOverlay.instance().listStore.clear).not.toBeCalled();
});

test('Should destroy listStore and autorun when unmounted', () => {
    const singleListOverlay = shallow(
        <SingleListOverlay
            adapter="table"
            listKey="snippets"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            preSelectedItem={{id: 5}}
            resourceKey="snippets"
            title="test"
        />
    );

    const listStore = singleListOverlay.instance().listStore;
    const selectionDisposerSpy = jest.fn();
    singleListOverlay.instance().selectionDisposer = selectionDisposerSpy;
    singleListOverlay.unmount();

    expect(listStore.destroy).toBeCalledWith();
    expect(selectionDisposerSpy).toBeCalledWith();
});
