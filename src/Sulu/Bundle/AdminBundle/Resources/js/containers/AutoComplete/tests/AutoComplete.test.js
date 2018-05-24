// @flow
import React from 'react';
import {shallow, render} from 'enzyme';
import AutoComplete from '../AutoComplete';
import AutoCompleteStore from '../stores/AutoCompleteStore';

jest.mock('../stores/AutoCompleteStore', () => jest.fn());

test('Render in loading state', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    expect(render(
        <AutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={undefined}
        />
    )).toMatchSnapshot();
});

test('Render with loaded suggestions', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = [
            {id: 7, number: '007', name: 'James Bond'},
            {id: 6, number: '006', name: 'John Doe'},
        ];
        this.loading = false;
    });

    const autoComplete = shallow(
        <AutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={['name', 'number']}
            value={undefined}
        />
    );

    expect(autoComplete.find('AutoComplete').find('Suggestion').at(0).prop('value')).toEqual(7);
    expect(autoComplete.find('AutoComplete').find('Suggestion').at(1).prop('value')).toEqual(6);
});

test('Render with given value', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = true;
    });

    expect(render(
        <AutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="test"
            searchProperties={[]}
            value={{name: 'James Bond', number: '007'}}
        />
    )).toMatchSnapshot();
});

test('Search using store when new search value is retrieved from AutoComplete component', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = [];
        this.loading = false;
        this.search = jest.fn();
    });

    const autoComplete = shallow(
        <AutoComplete
            displayProperty="name"
            onChange={jest.fn()}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    autoComplete.find('AutoComplete').simulate('search', 'James');

    expect(autoComplete.instance().autoCompleteStore.search).toBeCalledWith('James');
});

test('Call onChange and clear search result when chosen option has changed', () => {
    // $FlowFixMe
    AutoCompleteStore.mockImplementation(function() {
        this.searchResults = [data];
        this.loading = false;
        this.clearSearchResults = jest.fn();
    });

    const changeSpy = jest.fn();

    const data = {
        id: 7,
        name: 'James Bond',
        number: '007',
    };

    const autoComplete = shallow(
        <AutoComplete
            displayProperty="name"
            onChange={changeSpy}
            resourceKey="contact"
            searchProperties={[]}
            value={undefined}
        />
    );

    autoComplete.find('AutoComplete').simulate('change', 7);

    expect(changeSpy).toBeCalledWith(data);
    expect(autoComplete.instance().autoCompleteStore.clearSearchResults).toBeCalledWith();
});
