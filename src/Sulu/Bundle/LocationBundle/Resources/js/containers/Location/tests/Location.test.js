// @flow
import React from 'react';
import {shallow, mount} from 'enzyme';
import {MapContainer, Marker, Tooltip} from 'react-leaflet';
import Location from '../Location';
import LocationOverlay from '../LocationOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Component should render without a value', () => {
    const location = shallow(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={null}
        />
    );

    expect(location.render()).toMatchSnapshot();
});

test('Component should render in disabled state', () => {
    const location = shallow(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={null}
        />
    );

    expect(location.render()).toMatchSnapshot();
});

test('Component should render with a given value', () => {
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

    const location = shallow(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(location.render()).toMatchSnapshot();
});

test('Component should render a map, a marker and a tooltip with correct props and content', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'street-123',
        zoom: 5,
    };

    const location = mount(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(location.find(MapContainer).props()).toEqual(expect.objectContaining({
        attributionControl: false,
        center: [22, 33],
        doubleClickZoom: false,
        dragging: false,
        keyboard: false,
        scrollWheelZoom: false,
        tap: false,
        zoom: 5,
        zoomControl: false,
    }));

    expect(location.find(Marker).props()).toEqual(expect.objectContaining({
        interactive: false,
        position: [22, 33],
    }));

    expect(location.find(Tooltip).props()).toEqual(expect.objectContaining({
        permanent: true,
    }));

    expect(location.find(Tooltip).text()).toContain('title-123');
    expect(location.find(Tooltip).text()).toContain('code-123');
    expect(location.find(Tooltip).text()).toContain('street-123');
    expect(location.find(Tooltip).text()).toContain('street-123');
});

test('Component should not render a tooltip if given value has no additional information', () => {
    const locationData = {
        code: undefined,
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: undefined,
        title: undefined,
        town: undefined,
        zoom: 5,
    };

    const location = mount(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(location.find(Tooltip).exists()).toEqual(false);
});

test('Should pass correct props to the LocationOverlay', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'street-123',
        zoom: 5,
    };

    const location = mount(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={locationData}
        />
    );

    expect(location.find(LocationOverlay).props()).toEqual(expect.objectContaining({
        open: false,
        value: locationData,
    }));
});

test('Should open a LocationOverlay when the edit button is clicked', () => {
    const location = mount(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={null}
        />
    );

    expect(location.find(LocationOverlay).props().open).toEqual(false);
    location.find('button').simulate('click');
    expect(location.find(LocationOverlay).props().open).toEqual(true);
});

test('Should close LocationOverlay when the onClose callback of the overlay is fired', () => {
    const location = mount(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={null}
        />
    );

    location.find('button').simulate('click');
    expect(location.find(LocationOverlay).props().open).toEqual(true);

    location.find(LocationOverlay).props().onClose();
    location.update();
    expect(location.find(LocationOverlay).props().open).toEqual(false);
});

test('Should close overlay and call callback with correct value when the LocationOverlay is confirmed', () => {
    const newLocationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'street-123',
        zoom: 5,
    };
    const changeSpy = jest.fn();

    const location = mount(
        <Location
            disabled={true}
            onChange={changeSpy}
            value={null}
        />
    );

    location.find('button').simulate('click');
    expect(location.find(LocationOverlay).props().open).toEqual(true);

    location.find(LocationOverlay).props().onConfirm(newLocationData);
    location.update();
    expect(location.find(LocationOverlay).props().open).toEqual(false);

    expect(changeSpy).toBeCalledWith(newLocationData);
});

test('Should update view of map when value prop is changed', () => {
    const locationData = {
        code: 'code-123',
        country: undefined,
        lat: 22,
        long: 33,
        number: undefined,
        street: 'street-123',
        title: 'title-123',
        town: 'street-123',
        zoom: 5,
    };

    const location = mount(
        <Location
            disabled={true}
            onChange={jest.fn()}
            value={locationData}
        />
    );

    const mockedMap = {setView: jest.fn()};
    location.find(MapContainer).props().ref(mockedMap);

    expect(mockedMap.setView).not.toBeCalled();

    location.setProps({value: {lat: 44, long: 55, zoom: 2}});

    expect(mockedMap.setView).toBeCalledWith([44, 55], 2);
});
