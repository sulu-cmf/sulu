// @flow
import React from 'react';
import {mount, render} from 'enzyme';
import ColumnList from '../ColumnList';
import Column from '../Column';
import Item from '../Item';

jest.mock('../columnList.scss', () => new Proxy({}, {
    get: function(target, key) {
        if (key === '__esModule') {
            return false;
        }
        if (key === 'columnWidth') {
            return '270px';
        }

        return key;
    },
}));

test('The ColumnList component should render in a non-scrolling container', () => {
    const onItemClick = jest.fn();

    const buttonsConfig = [
        {
            icon: 'fa-heart',
            onClick: () => {},
        },
        {
            icon: 'fa-pencil',
            onClick: () => {},
        },
    ];

    const toolbarItems = [
        {
            index: 0,
            icon: 'fa-plus',
            type: 'button',
            onClick: () => {},
        },
        {
            index: 0,
            icon: 'fa-search',
            type: 'button',
            onClick: () => {},
        },
        {
            index: 0,
            icon: 'fa-gear',
            type: 'dropdown',
            options: [
                {
                    label: 'Option1 ',
                    onClick: () => {},
                },
                {
                    label: 'Option2 ',
                    onClick: () => {},
                },
            ],
        },
    ];

    expect(render(
        <ColumnList
            buttons={buttonsConfig}
            onItemClick={onItemClick}
            toolbarItems={toolbarItems}
        >
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item id="2" hasChildren={true}>Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren={true}>Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
            <Column loading={true} />
        </ColumnList>
    )).toMatchSnapshot();
});

test('The ColumnList component should render in a scrolling container', () => {
    const onItemClick = jest.fn();

    const toolbarItems = [
        {
            index: 0,
            icon: 'fa-plus',
            type: 'button',
            onClick: () => {},
        },
    ];

    const columnList = mount(
        <ColumnList
            onItemClick={onItemClick}
            toolbarItems={toolbarItems}
        >
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item id="2" hasChildren={true}>Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren={true}>Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
            <Column loading={true} />
        </ColumnList>
    );

    columnList.instance().container = {
        clientWidth: 500,
        scrollWidth: 600,
    };
    columnList.instance().activeColumnIndex = 2;
    columnList.instance().scrollPosition = 20;
    columnList.update();

    expect(columnList.render()).toMatchSnapshot();
});

test('The ColumnList component should trigger the item callback', () => {
    const onItemClick = jest.fn();

    const columnList = mount(
        <ColumnList
            onItemClick={onItemClick}
        >
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item id="2" hasChildren={true}>Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren={true}>Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
        </ColumnList>
    );
    const columns = columnList.find(Column);
    expect(columns.length).toBe(3);

    columns.first().find(Item).first().simulate('click');
    columns.first().find(Item).at(2).simulate('click');
    columns.at(1).find(Item).first().simulate('click');

    expect(onItemClick.mock.calls.length).toBe(3);
    expect(onItemClick.mock.calls[0][0]).toBe('1');
    expect(onItemClick.mock.calls[1][0]).toBe('3');
    expect(onItemClick.mock.calls[2][0]).toBe('1-1');
});

test('The ColumnList component should handle which toolbar is active on mouse enter event', () => {
    const buttonClickSpy = jest.fn();

    const toolbarItems = [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: buttonClickSpy,
        },
    ];

    const columnList = mount(
        <ColumnList
            toolbarItems={toolbarItems}
            onItemClick={jest.fn()}
        >
            <Column>
                <Item id="1" selected={true}>Item 1</Item>
                <Item id="2" hasChildren={true}>Item 1</Item>
                <Item id="3">Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1">Item 1</Item>
                <Item id="1-2" hasChildren={true}>Item 1</Item>
            </Column>
            <Column>
                <Item id="1-1-1">Item 1</Item>
                <Item id="1-1-2">Item 1</Item>
            </Column>
        </ColumnList>
    );
    const columns = columnList.find(Column);
    columnList.find('.fa-plus').simulate('click');
    expect(buttonClickSpy).toHaveBeenCalledWith(0);

    columns.at(1).simulate('mouseEnter');
    columnList.find('.fa-plus').simulate('click');
    expect(buttonClickSpy).toHaveBeenLastCalledWith(1);

    columns.at(2).simulate('mouseEnter');
    columnList.find('.fa-plus').simulate('click');
    expect(buttonClickSpy).toHaveBeenLastCalledWith(2);
});

test('Should move the toolbar container to the correct position', () => {
    const toolbarItems = [
        {
            icon: 'fa-plus',
            type: 'button',
            onClick: jest.fn(),
        },
    ];

    const columnList = mount(
        <ColumnList onItemClick={jest.fn()} toolbarItems={toolbarItems}>
            <Column />
        </ColumnList>
    );

    expect(columnList.find('Toolbar').parent().prop('style')).toEqual({marginLeft: 0});

    columnList.instance().scrollPosition = 35;
    columnList.instance().activeColumnIndex = 2;
    columnList.update();

    expect(columnList.find('Toolbar').parent().prop('style')).toEqual({marginLeft: 505});
});

test('Should set classes if the toolbar is active on the first or last visible column', () => {
    const columnList = mount(
        <ColumnList onItemClick={jest.fn()}>
            <Column />
            <Column />
            <Column />
        </ColumnList>
    );

    columnList.update();

    expect(columnList.find('.columnListContainer').prop('className'))
        .toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnList.find('.columnListContainer').prop('className'))
        .toEqual(expect.stringContaining('lastVisibleColumnActive'));

    columnList.instance().container = {
        clientWidth: 500,
    };
    columnList.instance().activeColumnIndex = 0;
    columnList.instance().scrollPosition = 20;
    columnList.update();

    expect(columnList.find('.columnListContainer').prop('className'))
        .toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnList.find('.columnListContainer').prop('className'))
        .not.toEqual(expect.stringContaining('lastVisibleColumnActive'));

    columnList.instance().activeColumnIndex = 2;
    columnList.instance().scrollPosition = 20;
    columnList.update();

    expect(columnList.find('.columnListContainer').prop('className'))
        .not.toEqual(expect.stringContaining('firstVisibleColumnActive'));
    expect(columnList.find('.columnListContainer').prop('className'))
        .toEqual(expect.stringContaining('lastVisibleColumnActive'));
});

test('Should scroll to the last column when new column is loaded', () => {
    const columnList = mount(
        <ColumnList onItemClick={jest.fn()}>
            <Column />
        </ColumnList>
    );

    columnList.instance().container = {
        clientWidth: 500,
    };

    columnList.setProps({
        children: [
            <Column key={1} />,
            <Column key={2} />,
            <Column key={3} />,
        ],
    });
    columnList.update();

    expect(columnList.instance().container.scrollLeft).toEqual(540);
});

test('Should not scroll to the last column when other props are updated', () => {
    const children = [
        <Column key={1} />,
    ];
    const columnList = mount(
        <ColumnList onItemClick={jest.fn()}>
            {children}
        </ColumnList>
    );

    columnList.instance().toolbar = {
        clientWidth: 271,
    };
    columnList.instance().container = {
        clientWidth: 500,
        scrollLeft: 10,
    };

    columnList.setProps({
        children,
    });
    columnList.update();

    expect(columnList.instance().container.scrollLeft).toEqual(10);
});
