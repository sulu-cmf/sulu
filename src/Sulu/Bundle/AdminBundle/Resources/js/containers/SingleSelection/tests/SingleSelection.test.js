// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import {extendObservable as mockExtendObservable, observable} from 'mobx';
import SingleSelectionStore from '../../../stores/SingleSelectionStore';
import SingleListOverlay from '../../../containers/SingleListOverlay';
import SingleSelection from '../SingleSelection';
import SingleItemSelection from '../../../components/SingleItemSelection';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/SingleListOverlay', () => jest.fn(function() {
    return <div />;
}));

jest.mock('../../../containers/List/stores/ListStore', () => jest.fn());

jest.mock('../../../stores/SingleSelectionStore', () => jest.fn(function() {
    this.set = jest.fn((item) => {
        this.item = item;
    });
    this.loadItem = jest.fn((id) => {
        this.item = {id};
    });
    this.clear = jest.fn();

    mockExtendObservable(this, {
        item: undefined,
        loading: false,
    });
}));

test('Show with passed emptyText and icon', () => {
    expect(render(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Test"
            icon="su-document"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle=""
            resourceKey="test"
            value={undefined}
        />
    )).toMatchSnapshot();
});

test('Render with selected item', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('test', 3, locale, undefined);

    singleSelection.instance().singleSelectionStore.item = {
        id: 3,
        name: 'Name',
        value: 'Value',
    };

    singleSelection.update();

    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(false);
    expect(singleSelection.find('SingleItemSelection').render()).toMatchSnapshot();
});

test('Render with selected item in disabled state', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabled={true}
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        id: 3,
        name: 'Name',
        value: 'Value',
    };
    singleSelection.update();

    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(false);
    expect(singleSelection.find('SingleItemSelection').render()).toMatchSnapshot();
});

test('Pass resourceKey and locale to SingleListOverlay', () => {
    const locale = observable.box('en');
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test_list"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleListOverlay).prop('locale')).toEqual(locale);
    expect(singleSelection.find(SingleListOverlay).prop('resourceKey')).toEqual('test');
    expect(singleSelection.find(SingleListOverlay).prop('listKey')).toEqual('test_list');
    expect(singleSelection.find(SingleListOverlay).prop('options')).toEqual(undefined);
});

test('Pass options to SingleListOverlay and SingleSelectionStore', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            detailOptions={{'ghost-content': true}}
            disabledIds={[]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test_list"
            listOptions={{value: 'Test'}}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(SingleSelectionStore).toBeCalledWith('test', 3, undefined, {'ghost-content': true});

    expect(singleSelection.find(SingleListOverlay).prop('options')).toEqual({value: 'Test'});
});

test('Pass disabledIds to SingleListOverlay', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabledIds={[1, 2, 3]}
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleListOverlay).prop('disabledIds')).toEqual([1, 2, 3]);
});

test('Pass itemDisabledCondition to SingleListOverlay', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            displayProperties={['name', 'value']}
            emptyText="Nothing"
            icon="su-test"
            itemDisabledCondition='status == "inactive"'
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleListOverlay).prop('itemDisabledCondition')).toEqual('status == "inactive"');
});

test('Should open and close an overlay', () => {
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.find('.button').prop('onClick')();
    singleSelection.update();
    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(true);

    singleSelection.find(SingleListOverlay).prop('onClose')();
    singleSelection.update();
    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(false);
});

test('Should not open an overlay on button-click when disabled', () => {
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabled={true}
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(false);

    singleSelection.find('.button').simulate('click');
    singleSelection.update();
    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(false);
});

test('Should call the onChange callback with null if the current item does not exist and set to null', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.instance().singleSelectionStore.item = null;

    expect(changeSpy).toBeCalledWith(null, null);
});

test('Should call the onChange callback if a new item was selected', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.find('.button').prop('onClick')();
    singleSelection.update();
    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(true);

    singleSelection.find(SingleListOverlay).prop('onConfirm')({id: 6});
    expect(singleSelection.instance().singleSelectionStore.loadItem).toBeCalledWith(6);
    expect(changeSpy).toBeCalledWith(6, {id: 6});
    singleSelection.update();
    expect(singleSelection.find(SingleListOverlay).prop('open')).toEqual(false);
});

test('Should not call onChange callback if an unrelated observable that is accessed in the callback changes', () => {
    const unrelatedObservable = observable.box(22);
    const changeSpy = jest.fn(() => {
        jest.fn()(unrelatedObservable.get());
    });

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    // disable load-item mock that would overwrite the item property of the store mock
    singleSelection.instance().singleSelectionStore.loadItem = jest.fn();

    // change callback should be called when item of the store mock changes
    singleSelection.instance().singleSelectionStore.item = {id: 7};
    expect(changeSpy).toBeCalledWith(7, {id: 7});
    expect(changeSpy).toHaveBeenCalledTimes(1);

    // change callback should not be called when the unrelated observable changes
    unrelatedObservable.set(55);
    expect(changeSpy).toHaveBeenCalledTimes(1);
});

test('Should not call the onChange callback if the same item was selected', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={6}
        />
    );

    singleSelection.find(SingleListOverlay).prop('onConfirm')({id: 6});
    expect(changeSpy).not.toBeCalled();
});

test('Should load the item if value prop changes', () => {
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    singleSelection.setProps({value: 3});
    expect(singleSelection.instance().singleSelectionStore.loadItem).toBeCalledWith(3);
});

test('Should call the onItemClick callback when an item when the item is clicked', () => {
    const itemClickSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            onItemClick={itemClickSpy}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {id: 1};
    singleSelection.find('SingleItemSelection .item').simulate('click');

    expect(itemClickSpy).toBeCalledWith(1, {id: 1});
});

test('Should remove an item when the remove button is clicked', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        name: 'Name',
        value: 'Value',
    };
    singleSelection.find('SingleItemSelection').prop('onRemove')();
    expect(singleSelection.instance().singleSelectionStore.clear).toBeCalledWith();
});

test('Should call the onChange callback if the value of the selection-store changes', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {id: 6};
    expect(changeSpy).toBeCalledWith(6, {id: 6});
});

test('Should not call the onChange callback if the component props change', () => {
    const changeSpy = jest.fn();

    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={[]}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            onChange={changeSpy}
            overlayTitle="Test"
            resourceKey="test"
            value={3}
        />
    );

    singleSelection.setProps({emptyText: 'New Empty Text'});
    expect(changeSpy).not.toBeCalled();
});

test('Correct props should be passed to SingleItemSelection component', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabled={true}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(singleSelection.find(SingleItemSelection).prop('disabled')).toEqual(true);
    expect(singleSelection.find(SingleItemSelection).prop('emptyText')).toEqual('nothing');
});

test('Pass correct itemDisabled prop to SingleItemSelection component when item fulfills itemDisabledCondition', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            displayProperties={[]}
            emptyText="nothing"
            itemDisabledCondition='status == "inactive"'
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(singleSelection.find(SingleItemSelection).prop('itemDisabled')).toEqual(false);

    singleSelection.instance().singleSelectionStore.item = {
        id: 3,
        status: 'inactive',
    };

    expect(singleSelection.find(SingleItemSelection).prop('itemDisabled')).toEqual(true);
});

test('Pass correct itemDisabled prop to SingleItemSelection component when disabledIds contains id of item', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabledIds={[1, 3, 5]}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(singleSelection.find(SingleItemSelection).prop('itemDisabled')).toEqual(false);

    singleSelection.instance().singleSelectionStore.item = {
        id: 3,
        status: 'inactive',
    };

    expect(singleSelection.find(SingleItemSelection).prop('itemDisabled')).toEqual(true);
});

test('Set loading prop of SingleItemSelection component if SingleSelectionStore is loading', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            disabled={true}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(singleSelection.find(SingleItemSelection).prop('loading')).toEqual(false);
    singleSelection.instance().singleSelectionStore.loading = true;
    expect(singleSelection.find(SingleItemSelection).prop('loading')).toEqual(true);
    expect(singleSelection.find(SingleListOverlay)).toHaveLength(0);
});

test('Pass correct allowRemoveWhileItemDisabled prop to SingleItemSelection component', () => {
    const singleSelection = shallow(
        <SingleSelection
            adapter="table"
            allowDeselectForDisabledItems={true}
            disabledIds={[1, 3, 5]}
            displayProperties={[]}
            emptyText="nothing"
            listKey="snippets"
            onChange={jest.fn()}
            overlayTitle="Selection"
            resourceKey="snippets"
            value={1}
        />
    );

    expect(singleSelection.find(SingleItemSelection).prop('allowRemoveWhileItemDisabled')).toEqual(true);
});

test('PublishIndicator should not be rendered if not necessary', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={1}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        id: 1,
        name: 'Name',
        published: '2020-11-16',
        publishedState: true,
    };

    singleSelection.update();

    expect(singleSelection.contains('PublishIndicator')).toBe(false);
});

test('PublishIndicator should be rendered as draft if necessary', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={1}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        id: 1,
        name: 'Name',
        published: '2020-11-16',
        publishedState: false,
    };

    singleSelection.update();

    expect(singleSelection.find('PublishIndicator').prop('draft')).toBe(true);
    expect(singleSelection.find('PublishIndicator').prop('published')).toBe(true);
});

test('PublishIndicator should be rendered as unpublished if necessary', () => {
    const locale = observable.box('en');
    const singleSelection = mount(
        <SingleSelection
            adapter="table"
            disabledIds={[]}
            displayProperties={['name']}
            emptyText="Nothing"
            icon="su-test"
            listKey="test"
            locale={locale}
            onChange={jest.fn()}
            overlayTitle="Test"
            resourceKey="test"
            value={1}
        />
    );

    singleSelection.instance().singleSelectionStore.item = {
        id: 1,
        name: 'Name',
        published: null,
        publishedState: false,
    };

    singleSelection.update();

    expect(singleSelection.find('PublishIndicator').prop('draft')).toBe(true);
    expect(singleSelection.find('PublishIndicator').prop('published')).toBe(false);
});
