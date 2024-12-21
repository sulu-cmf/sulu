// @flow
import React from 'react';
import {mount, render, shallow} from 'enzyme';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import TreeTableAdapter from '../../adapters/TreeTableAdapter';

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.page':
                return 'Page';
            case 'sulu_admin.of':
                return 'of';
        }
    },
}));

jest.mock('../../registries/listFieldTransformerRegistry', () => ({
    add: jest.fn(),
    get: jest.fn(() => {
        return {
            transform(value) {
                return value;
            },
        };
    }),
    has: jest.fn(),
}));

test('Render data with schema', () => {
    const data = [
        {
            data: {
                id: 1,
                title: 'Page 1',
                published: '2017-08-23',
                publishedState: true,
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 2,
                title: 'Page 2',
                publishedState: true,
                published: null,
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 3,
                title: 'Page 3',
                publishedState: false,
                published: '2017-08-23',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 4,
                title: 'Page 4',
                publishedState: false,
                published: null,
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 5,
                title: 'Page 5',
                published: '2017-08-23',
                publishedState: true,
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 6,
                title: 'Page 6',
                publishedState: true,
                published: null,
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 7,
                title: 'Page 7',
                publishedState: false,
                published: '2017-08-23',
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
        {
            data: {
                id: 8,
                title: 'Page 8',
                publishedState: false,
                published: null,
                ghostLocale: 'de',
            },
            children: [],
            hasChildren: true,
        },
    ];

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };

    const treeTableAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(treeTableAdapter).toMatchSnapshot();
});

test('Render data without header', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [
            {
                data: {
                    id: 7,
                    title: 'Test4',
                },
                children: [],
                hasChildren: false,
            },
        ],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{show_header: false}}
            data={data}
            page={1}
            pageCount={2}
            paginated={false}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data with skin', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [
            {
                data: {
                    id: 7,
                    title: 'Test4',
                },
                children: [],
                hasChildren: false,
            },
        ],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{skin: 'flat'}}
            data={data}
            page={1}
            pageCount={2}
            paginated={false}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data without header', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [
            {
                data: {
                    id: 7,
                    title: 'Test4',
                },
                children: [],
                hasChildren: false,
            },
        ],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            adapterOptions={{show_header: false}}
            data={data}
            page={1}
            pageCount={2}
            paginated={false}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Attach onClick handler for sorting if schema says the header is sortable', () => {
    const sortSpy = jest.fn();

    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: false,
            visibility: 'yes',
            label: 'Description',
        },
    };

    const treeTableAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            onSort={sortSpy}
            schema={schema}
        />
    );

    expect(treeTableAdapter.find('HeaderCell').at(0).prop('onClick')).toBe(sortSpy);
    expect(treeTableAdapter.find('HeaderCell').at(1).prop('onClick')).toEqual(undefined);
});

test('Render data with two columns', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
            title2: 'Title2 - Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
            title2: 'Title2 - Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
            title2: 'Title2 - Test3',
        },
        children: [],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
        title2: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title2',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render data with schema and selections', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            schema={schema}
            selections={[1, 3]}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Execute onItemActivate respectively onItemDeactivate callback when an item is clicked', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
        },
        children: [],
        hasChildren: false,
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [
            test21,
            test22,
        ],
        hasChildren: true,
    };
    const test3 = {
        data: {
            id: 6,
            title: 'Test3',
        },
        children: [],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
        test3,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };

    const onItemActivateSpy = jest.fn();
    const onItemDeactivateSpy = jest.fn();

    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemActivate={onItemActivateSpy}
            onItemDeactivate={onItemDeactivateSpy}
            schema={schema}
        />
    );

    // expand the row
    treeListAdapter.find('Row[id=6]').find('span.toggleIcon Icon').simulate('click');
    expect(onItemActivateSpy).toBeCalledWith(6);

    // close the row
    treeListAdapter.find('Row[id=3]').find('span.toggleIcon Icon').simulate('click');
    expect(onItemDeactivateSpy).toBeCalledWith(3);
});

test('Render data with pencil button and given itemActions when onItemEdit callback is passed', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const actionsProvider = () => [
        {
            icon: 'su-process',
            onClick: undefined,
        },
        {
            icon: 'su-trash',
            onClick: undefined,
        },
    ];

    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            /* eslint-disable-next-line react/jsx-no-bind */
            itemActionsProvider={actionsProvider}
            onItemClick={jest.fn()}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Render correct buttons based on permissions when item permissions are provided', () => {
    const data = [
        {
            data: {
                id: 1,
                title: 'Missing view permission',
                _permissions: {
                    view: false,
                },
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 2,
                title: 'Missing edit permission',
                _permissions: {
                    edit: false,
                },
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 3,
                title: 'Missing add permission',
                _permissions: {
                    add: false,
                },
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 4,
                title: 'No missing permissions',
            },
            children: [],
            hasChildren: false,
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            schema={schema}
        />
    );

    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(0).props().icon).toEqual('su-pen');
    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(0).props().disabled).toEqual(true);
    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(0).find('ButtonCell').at(1).props().disabled).toEqual(false);

    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(0).props().icon).toEqual('su-eye');
    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(0).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(1).find('ButtonCell').at(1).props().disabled).toEqual(false);

    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(0).props().icon).toEqual('su-pen');
    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(0).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(2).find('ButtonCell').at(1).props().disabled).toEqual(true);

    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(0).props().icon).toEqual('su-pen');
    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(0).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(1).props().icon).toEqual('su-plus-circle');
    expect(treeListAdapter.find('Row').at(3).find('ButtonCell').at(1).props().disabled).toEqual(false);
});

test('Render disabled rows based on given disabledIds prop', () => {
    const data = [
        {
            data: {
                id: 1,
                title: 'First item',
            },
            children: [],
            hasChildren: false,
        },
        {
            data: {
                id: 2,
                title: 'Second item',
            },
            children: [
                {
                    data: {
                        id: 3,
                        title: 'Child item',
                    },
                    children: [],
                    hasChildren: false,
                },
            ],
            hasChildren: true,
        },
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
    };
    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            disabledIds={[1, 3]}
            onItemAdd={jest.fn()}
            onItemClick={jest.fn()}
            schema={schema}
        />
    );

    expect(treeListAdapter.find('Row').at(0).props().disabled).toEqual(true);
    expect(treeListAdapter.find('Row').at(1).props().disabled).toEqual(false);
    expect(treeListAdapter.find('Row').at(2).props().disabled).toEqual(true);
});

test('Render data with plus button when onItemAdd callback is passed', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = render(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={jest.fn()}
            schema={schema}
        />
    );

    expect(treeListAdapter).toMatchSnapshot();
});

test('Click on pencil should execute onItemClick callback', () => {
    const rowEditClickSpy = jest.fn();
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const treeListAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemClick={rowEditClickSpy}
            schema={schema}
        />
    );
    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-pen');

    buttons[0].onClick(1);
    expect(rowEditClickSpy).toBeCalledWith(1);
});

test('Click on add should execute onItemAdd callback', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [
        test1,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const rowAddClickSpy = jest.fn();
    const treeListAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemAdd={rowAddClickSpy}
            schema={schema}
        />
    );
    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(1);
    expect(buttons[0].icon).toBe('su-plus-circle');

    buttons[0].onClick(1);
    expect(rowAddClickSpy).toBeCalledWith(1);
});

test('Click on itemAction should execute its callback', () => {
    const actionClickSpy = jest.fn();
    const item1Data = {
        id: 2,
        title: 'Test1',
    };
    const item1 = {
        data: item1Data,
        children: [],
        hasChildren: false,
    };
    const data = [item1];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Title',
            sortable: true,
            type: 'string',
            visibility: 'no',
        },
        description: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            label: 'Description',
            sortable: true,
            type: 'string',
            visibility: 'yes',
        },
    };
    const actionsProvider = jest.fn(() => [
        {
            icon: 'su-process',
            onClick: actionClickSpy,
        },
    ]);

    const treeListAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            itemActionsProvider={actionsProvider}
            onItemAdd={jest.fn()}
            schema={schema}
        />
    );

    expect(actionsProvider).toBeCalledWith(item1Data);

    const buttons = treeListAdapter.find('Table').prop('buttons');
    expect(buttons).toHaveLength(2);
    expect(buttons[1].icon).toBe('su-process');

    buttons[1].onClick(1);
    expect(actionClickSpy).toBeCalledWith(1);
});

test('Pagination should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();

    const item1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const data = [item1];

    const treeTableAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            limit={10}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );
    expect(treeTableAdapter.find('Pagination').get(0).props).toEqual({
        totalPages: 7,
        currentPage: 2,
        currentLimit: 10,
        loading: false,
        onLimitChange: limitChangeSpy,
        onPageChange: treeTableAdapter.instance().handlePageChange,
        children: expect.anything(),
    });
});

test('Pagination should not be rendered if API is not paginated', () => {
    const item1 = {
        data: {
            id: 1,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };

    const item2 = {
        data: {
            id: 2,
            title: 'Test2',
        },
        children: [],
        hasChildren: false,
    };

    const item3 = {
        data: {
            id: 3,
            title: 'Test3',
        },
        children: [item2],
        hasChildren: true,
    };
    const data = [item1, item3];

    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const treeTableAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={1}
            pageCount={undefined}
        />
    );
    expect(treeTableAdapter.find('Pagination')).toHaveLength(0);
});

test('Pagination should not be rendered if no data is available', () => {
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();
    const treeTableAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={1}
        />
    );
    expect(treeTableAdapter.find('Pagination')).toHaveLength(0);
});

test('Pagination should not be rendered if pagination is false', () => {
    const treeTableAdapter = shallow(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            limit={10}
            page={2}
            pageCount={7}
            paginated={false}
        />
    );
    expect(treeTableAdapter.find('Pagination')).toHaveLength(0);
});

test('Next page should call onItemActiveate with undefined', () => {
    const test1 = {
        data: {
            id: 2,
            title: 'Test1',
        },
        children: [],
        hasChildren: false,
    };
    const test21 = {
        data: {
            id: 4,
            title: 'Test2.1',
        },
        children: [],
        hasChildren: false,
    };
    const test22 = {
        data: {
            id: 5,
            title: 'Test2.2',
        },
        children: [],
        hasChildren: false,
    };
    const test2 = {
        data: {
            id: 3,
            title: 'Test2',
        },
        children: [
            test21,
            test22,
        ],
        hasChildren: true,
    };

    const data = [
        test1,
        test2,
    ];
    const schema = {
        title: {
            filterType: null,
            filterTypeParameters: null,
            transformerTypeParameters: {},
            type: 'string',
            sortable: true,
            visibility: 'yes',
            label: 'Title',
        },
    };

    const onPageChangeSpy = jest.fn();
    const onItemActivateSpy = jest.fn();

    const treeListAdapter = mount(
        <TreeTableAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemActivate={onItemActivateSpy}
            onPageChange={onPageChangeSpy}
            page={1}
            pageCount={2}
            schema={schema}
        />
    );

    // Click next page
    treeListAdapter.find('Pagination').find('ButtonGroup Button').at(1).simulate('click');
    expect(onPageChangeSpy).toBeCalledWith(2);
    expect(onItemActivateSpy).toBeCalledWith(undefined);
});
