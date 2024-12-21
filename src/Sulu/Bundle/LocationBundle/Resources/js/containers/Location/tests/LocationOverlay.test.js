// @flow
import React from 'react';
import {mount} from 'enzyme';
import {Input, Number, Overlay} from 'sulu-admin-bundle/components';
import {SingleAutoComplete} from 'sulu-admin-bundle/containers';
import {MapContainer, Marker} from 'react-leaflet';
import LocationOverlay from '../LocationOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Component should render without a given initial-value', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    expect(locationOverlay.render()).toMatchSnapshot();
});

test('Component should render with a given initial-value', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={locationData}
        />
    );

    expect(locationOverlay.render()).toMatchSnapshot();
});

test('Should pass correct props the the Overlay component', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    expect(locationOverlay.find(Overlay).props()).toEqual(expect.objectContaining({
        confirmDisabled: false,
        confirmText: 'sulu_admin.confirm',
        open: true,
        size: 'small',
        title: 'sulu_location.select_location',
    }));
});

test('Should pass correct props the the SingleAutoComplete component', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    expect(locationOverlay.find(SingleAutoComplete).props()).toEqual(expect.objectContaining({
        displayProperty: 'displayTitle',
        searchProperties: ['displayTitle'],
    }));
});

test('Should pass correct props the Map component and Marker component when no initial-value is given', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    expect(locationOverlay.find(MapContainer).props()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [0, 0],
        zoom: 1,
    }));

    expect(locationOverlay.find(Marker).props()).toEqual(expect.objectContaining({
        draggable: true,
        position: [0, 0],
    }));
});

test('Should pass correct props the Map component and Marker component when an initial-value is given', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={locationData}
        />
    );

    expect(locationOverlay.find(MapContainer).props()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [22, 33],
        zoom: 5,
    }));

    expect(locationOverlay.find(Marker).props()).toEqual(expect.objectContaining({
        draggable: true,
        position: [22, 33],
    }));
});

test('Should pass correct props to the input fields', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={locationData}
        />
    );

    expect(locationOverlay.find(Number).at(0).props().value).toEqual(22); // lat
    expect(locationOverlay.find(Number).at(1).props().value).toEqual(33); // long
    expect(locationOverlay.find(Number).at(2).props().value).toEqual(5); // zoom
    expect(locationOverlay.find(Input).at(4).props().value).toEqual('title-123'); // title
    expect(locationOverlay.find(Input).at(5).props().value).toEqual('street-123'); // street
    expect(locationOverlay.find(Input).at(6).props().value).toBeUndefined(); // number
    expect(locationOverlay.find(Input).at(7).props().value).toEqual('code-123'); // code
    expect(locationOverlay.find(Input).at(8).props().value).toEqual('town-123'); // town
    expect(locationOverlay.find(Input).at(9).props().value).toBeUndefined(); // country
});

test('Should pass correct props to the map, marker and input fields after auto-complete was changed', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    locationOverlay.find(MapContainer).props().whenCreated(mockedMap);

    const autoCompleteResult = {
        latitude: 10,
        longitude: 20,
        displayTitle: 'new-display-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    };

    locationOverlay.find(SingleAutoComplete).props().selectionStore.set(autoCompleteResult);
    locationOverlay.update();

    expect(locationOverlay.find(Number).at(0).props().value).toEqual(autoCompleteResult.latitude); // lat
    expect(locationOverlay.find(Number).at(1).props().value).toEqual(autoCompleteResult.longitude); // long
    expect(locationOverlay.find(Number).at(2).props().value).toEqual(1); // zoom
    expect(locationOverlay.find(Input).at(4).props().value).toEqual(autoCompleteResult.displayTitle); // title
    expect(locationOverlay.find(Input).at(5).props().value).toEqual(autoCompleteResult.street); // street
    expect(locationOverlay.find(Input).at(6).props().value).toEqual(autoCompleteResult.number); // number
    expect(locationOverlay.find(Input).at(7).props().value).toEqual(autoCompleteResult.code); // code
    expect(locationOverlay.find(Input).at(8).props().value).toEqual(autoCompleteResult.town); // town
    expect(locationOverlay.find(Input).at(9).props().value).toEqual(autoCompleteResult.country); // country

    expect(mockedMap.setView).toBeCalledWith([autoCompleteResult.latitude, autoCompleteResult.longitude], 1);
    expect(locationOverlay.find(Marker).props().position).toEqual(
        [autoCompleteResult.latitude, autoCompleteResult.longitude]
    );
});

test('Should call onConfirm callback when the Overlay is confirmed after auto-complete was changed', () => {
    const confirmSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            value={null}
        />
    );

    const autoCompleteResult = {
        latitude: 10,
        longitude: 20,
        displayTitle: 'new-display-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    };

    locationOverlay.find(SingleAutoComplete).props().selectionStore.set(autoCompleteResult);
    locationOverlay.find(Overlay).props().onConfirm();

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 10,
        long: 20,
        zoom: 1,
        title: 'new-display-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    }));
});

test('Should pass correct props to the map and input fields after map was zoomed', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    const mockedMap = {setView: jest.fn(), on: jest.fn((event, handler) => {
        if (event === 'zoomanim') {
            handler({zoom: 10});
        }
    })};
    locationOverlay.find(MapContainer).props().whenCreated(mockedMap);
    locationOverlay.update();

    expect(locationOverlay.find(Number).at(2).props().value).toEqual(10); // zoom
    expect(locationOverlay.find(MapContainer).props().zoom).toEqual(10);
});

test('Should call onConfirm callback when the Overlay is confirmed after map was zoomed', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            value={locationData}
        />
    );

    const mockedMap = {setView: jest.fn(), on: jest.fn((event, handler) => {
        if (event === 'zoomanim') {
            handler({zoom: 10});
        }
    })};
    locationOverlay.find(MapContainer).props().whenCreated(mockedMap);

    locationOverlay.find(Overlay).props().onConfirm();

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 1,
        long: 1,
        zoom: 10,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    }));
});

test('Should pass correct props to the map, marker and input fields when marker is dragged', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    locationOverlay.find(MapContainer).props().whenCreated(mockedMap);

    locationOverlay.find(Marker).props().eventHandlers.drag({latlng: {lng: 11, lat: 22}});
    locationOverlay.update();

    expect(locationOverlay.find(Number).at(0).props().value).toEqual(22); // lat
    expect(locationOverlay.find(Number).at(1).props().value).toEqual(11); // long
    expect(locationOverlay.find(Marker).props().position).toEqual([22, 11]);
    expect(mockedMap.setView).not.toBeCalled();

    locationOverlay.find(Marker).props().eventHandlers.dragend();
    locationOverlay.update();

    expect(locationOverlay.find(Number).at(0).props().value).toEqual(22); // lat
    expect(locationOverlay.find(Number).at(1).props().value).toEqual(11); // long
    expect(locationOverlay.find(Marker).props().position).toEqual([22, 11]);
    expect(mockedMap.setView).toBeCalledWith([22, 11], 1);
});

test('Should call onConfirm callback when the Overlay is confirmed after marker was dragged', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            value={locationData}
        />
    );

    locationOverlay.find(Marker).props().eventHandlers.drag({latlng: {lng: 11, lat: 22}});
    locationOverlay.find(Marker).props().eventHandlers.dragend();
    locationOverlay.find(Overlay).props().onConfirm();

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 22,
        long: 11,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    }));
});

test('Should call onConfirm callback when the Overlay is confirmed after setting lat and ling to zero', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            value={locationData}
        />
    );

    locationOverlay.find(Number).at(0).props().onChange(0); // lat
    locationOverlay.find(Number).at(1).props().onChange(0); // long
    locationOverlay.find(Overlay).props().onConfirm();

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 0,
        long: 0,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    }));
});

test('Should pass correct props to the map, marker and input fields after reset', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'town-123',
        zoom: 5,
    };

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={locationData}
        />
    );

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    locationOverlay.find(MapContainer).props().whenCreated(mockedMap);

    locationOverlay.find(Overlay).props().actions[0].onClick();
    locationOverlay.update();

    expect(locationOverlay.find(Number).at(0).props().value).toBeNull(); // lat
    expect(locationOverlay.find(Number).at(1).props().value).toBeNull(); // long
    expect(locationOverlay.find(Number).at(2).props().value).toEqual(1); // zoom
    expect(locationOverlay.find(Input).at(4).props().value).toBeNull(); // title
    expect(locationOverlay.find(Input).at(5).props().value).toBeNull(); // street
    expect(locationOverlay.find(Input).at(6).props().value).toBeNull(); // number
    expect(locationOverlay.find(Input).at(7).props().value).toBeNull(); // code
    expect(locationOverlay.find(Input).at(8).props().value).toBeNull(); // town
    expect(locationOverlay.find(Input).at(9).props().value).toBeNull(); // country

    expect(mockedMap.setView).toBeCalledWith([0, 0], 1);
    expect(locationOverlay.find(Marker).props().position).toEqual([0, 0]);
});

test('Should call onConfirm callback when the Overlay is confirmed after reset', () => {
    const locationData = {
        lat: 1,
        long: 1,
        zoom: 1,
        title: 'old-title',
        street: 'old-street',
        number: 'old-number',
        code: 'old-code',
        town: 'old-town',
        country: 'old-country',
    };
    const confirmSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            value={locationData}
        />
    );

    locationOverlay.find(Overlay).props().actions[0].onClick();
    locationOverlay.find(Overlay).props().onConfirm();

    expect(confirmSpy).toBeCalledWith(null);
});

test('Should pass correct props to the map, marker and input fields after input fields are changed', () => {
    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    const mockedMap = {setView: jest.fn(), on: jest.fn()};
    locationOverlay.find(MapContainer).props().whenCreated(mockedMap);

    locationOverlay.find(Number).at(0).props().onChange(10); // lat
    locationOverlay.find(Number).at(1).props().onChange(20); // long
    locationOverlay.find(Number).at(2).props().onChange(12); // zoom
    locationOverlay.find(Input).at(4).props().onChange('new-title'); // title
    locationOverlay.find(Input).at(5).props().onChange('new-street'); // street
    locationOverlay.find(Input).at(6).props().onChange('new-number'); // number
    locationOverlay.find(Input).at(7).props().onChange('new-code'); // code
    locationOverlay.find(Input).at(8).props().onChange('new-town'); // town
    locationOverlay.find(Input).at(9).props().onChange('new-country'); // country
    locationOverlay.update();

    expect(locationOverlay.find(Number).at(0).props().value).toEqual(10); // lat
    expect(locationOverlay.find(Number).at(1).props().value).toEqual(20); // long
    expect(locationOverlay.find(Number).at(2).props().value).toEqual(12); // zoom
    expect(locationOverlay.find(Input).at(4).props().value).toEqual('new-title'); // title
    expect(locationOverlay.find(Input).at(5).props().value).toEqual('new-street'); // street
    expect(locationOverlay.find(Input).at(6).props().value).toEqual('new-number'); // number
    expect(locationOverlay.find(Input).at(7).props().value).toEqual('new-code'); // code
    expect(locationOverlay.find(Input).at(8).props().value).toEqual('new-town'); // town
    expect(locationOverlay.find(Input).at(9).props().value).toEqual('new-country'); // country

    expect(mockedMap.setView).toBeCalledWith([10, 20], 12);
    expect(locationOverlay.find(Marker).props().position).toEqual([10, 20]);
});

test('Should call onConfirm callback when the Overlay is confirmed after input fields are changed', () => {
    const confirmSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={jest.fn()}
            onConfirm={confirmSpy}
            open={true}
            value={null}
        />
    );

    locationOverlay.find(Number).at(0).props().onChange(10); // lat
    locationOverlay.find(Number).at(1).props().onChange(20); // long
    locationOverlay.find(Number).at(2).props().onChange(12); // zoom
    locationOverlay.find(Input).at(4).props().onChange('new-title'); // title
    locationOverlay.find(Input).at(5).props().onChange('new-street'); // street
    locationOverlay.find(Input).at(6).props().onChange('new-number'); // number
    locationOverlay.find(Input).at(7).props().onChange('new-code'); // code
    locationOverlay.find(Input).at(8).props().onChange('new-town'); // town
    locationOverlay.find(Input).at(9).props().onChange('new-country'); // country
    locationOverlay.find(Overlay).props().onConfirm();

    expect(confirmSpy).toBeCalledWith(expect.objectContaining({
        lat: 10,
        long: 20,
        zoom: 12,
        title: 'new-title',
        street: 'new-street',
        number: 'new-number',
        code: 'new-code',
        town: 'new-town',
        country: 'new-country',
    }));
});

test('Should call given onClose callback when onClose callback of Overlay is fired', () => {
    const closeSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    locationOverlay.find(Overlay).props().onClose();

    expect(closeSpy).toBeCalledWith();
});

test('Should enable confirm button if longitude and latitude are both not set or both set', () => {
    const closeSpy = jest.fn();

    const locationOverlay = mount(
        <LocationOverlay
            locale="en"
            onClose={closeSpy}
            onConfirm={jest.fn()}
            open={true}
            value={null}
        />
    );

    locationOverlay.find(Number).at(0).props().onChange(null); // lat
    locationOverlay.find(Number).at(1).props().onChange(null); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(false);

    locationOverlay.find(Number).at(0).props().onChange(11); // lat
    locationOverlay.find(Number).at(1).props().onChange(null); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(true);

    locationOverlay.find(Number).at(0).props().onChange(null); // lat
    locationOverlay.find(Number).at(1).props().onChange(11); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(true);

    locationOverlay.find(Number).at(0).props().onChange(11); // lat
    locationOverlay.find(Number).at(1).props().onChange(11); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(false);

    locationOverlay.find(Number).at(0).props().onChange(0); // lat
    locationOverlay.find(Number).at(1).props().onChange(11); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(false);

    locationOverlay.find(Number).at(0).props().onChange(11); // lat
    locationOverlay.find(Number).at(1).props().onChange(0); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(false);

    locationOverlay.find(Number).at(0).props().onChange(0); // lat
    locationOverlay.find(Number).at(1).props().onChange(0); // long
    locationOverlay.update();
    expect(locationOverlay.find(Overlay).props().confirmDisabled).toEqual(false);
});
