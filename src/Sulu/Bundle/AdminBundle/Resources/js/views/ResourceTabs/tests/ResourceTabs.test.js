/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import {extendObservable, observable} from 'mobx';
import React from 'react';
import ResourceTabs from '../ResourceTabs';
import ResourceStore from '../../../stores/ResourceStore';

jest.mock('../../../utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'tabTitle1':
                return 'Tab Titel 1';
            case 'tabTitle2':
                return 'Tab Titel 2';
            case 'tabTitle3':
                return 'Tab Titel 3';
            case 'tabTitle4':
                return 'Tab Titel 4';
        }
    },
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

beforeEach(() => {
    ResourceStore.mockReset();
});

test('Should pass the tab title from the ResourceStore as configured in the route', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const children = jest.fn();
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{children}</ResourceTabs>);

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    resourceTabs.update();

    expect(children).toBeCalledWith(
        {locales: undefined, resourceStore: expect.anything(ResourceStore), title: 'value1'}
    );
});

test('Should not pass the tab title from the ResourceStore if no titleProperty is set', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.loading = false;
        this.load = jest.fn();
        extendObservable(this, {data: {test1: 'value1', test2: 'value2'}});
    });

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const children = jest.fn();

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{children}</ResourceTabs>);

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    resourceTabs.update();

    expect(children).toBeCalledWith(
        {locales: undefined, resourceStore: expect.anything(ResourceStore), title: undefined}
    );
});

test('Should pass the tab title from the resourceStore as configured in the props to the child component', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.loading = false;
        this.load = jest.fn();
        extendObservable(this, {data: {test1: 'value1', test2: 'value2'}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const children = jest.fn();

    mount(
        <ResourceTabs route={route} router={router} titleProperty="test2">
            {children}
        </ResourceTabs>
    );

    expect(children).toBeCalledWith({locales: undefined, resourceStore: expect.any(ResourceStore), title: 'value2'});
});

test('Should not render the tab title on the first tab when tabOrder is defined', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
            titleProperty: 'test1',
        },
        children: [
            {
                name: 'Tab 2',
                options: {
                    tabOrder: 2,
                    tabTitle: 'tabTitle2',
                },
            },
            {
                name: 'Tab 1',
                options: {
                    tabOrder: 1,
                    tabTitle: 'tabTitle1',
                },
            },
        ],
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>
            {() => (<Child route={route.children[1]} />)}
        </ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test1: 'value1'};
    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs > h1')).toHaveLength(0);
        done();
    });
});

test('Should render the child components after the tabs', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[0],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        expect(resourceTabs.find('Loader')).toHaveLength(0);
        expect(resourceTabs.find('ResourceTabs Tabs Tabs').render()).toMatchSnapshot();
        expect(resourceTabs.find('ResourceTabs Child').render()).toMatchSnapshot();
        done();
    });
});

test('Should render a loader if resourceStore was not initialized yet', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = false;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            {
                name: 'Tab 1',
                options: {
                    tabTitle: 'tabTitle1',
                },
            },
            {
                name: 'Tab 2',
                options: {
                    tabTitle: 'tabTitle2',
                },
            },
        ],
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[0],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    expect(resourceTabs.find('Loader')).toHaveLength(1);
    expect(resourceTabs.find('Child')).toHaveLength(0);
});

test('Should mark the currently active child route as selected tab', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    setTimeout(() => {
        expect(resourceTabs.find('ResourceTabs Tabs Tabs').render()).toMatchSnapshot();
        expect(resourceTabs.find('ResourceTabs Child').render()).toMatchSnapshot();
        done();
    });
});

test('Should consider the tabOrder option of the route', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabOrder: 40,
            tabTitle: 'tabTitle2',
        },
    };
    const childRoute3 = {
        name: 'Tab 3',
        options: {
            tabTitle: 'tabTitle3',
        },
    };
    const childRoute4 = {
        name: 'Tab 4',
        options: {
            tabOrder: -10,
            tabTitle: 'tabTitle4',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
            childRoute4,
        ],
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs Tab')).toHaveLength(4);
        expect(resourceTabs.find('ResourceTabs Tab').at(0).text()).toEqual('Tab Titel 4');
        expect(resourceTabs.find('ResourceTabs Tab').at(1).text()).toEqual('Tab Titel 1');
        expect(resourceTabs.find('ResourceTabs Tab').at(2).text()).toEqual('Tab Titel 3');
        expect(resourceTabs.find('ResourceTabs Tab').at(3).text()).toEqual('Tab Titel 2');
        done();
    });
});

test('Should hide tabs which do not match the tab condition', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'Tab 1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'Tab 2',
        options: {
            tabCondition: 'test == 2',
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 1,
        },
        redirect: jest.fn(),
        route: route.children[1],
    };

    const Child = () => (<h1>Child</h1>);

    const resourceTabs = mount(
        <ResourceTabs route={route} router={router}>{() => (<Child route={route.children[1]} />)}</ResourceTabs>
    );

    resourceTabs.instance().resourceStore.data = {test: 1};

    setTimeout(() => {
        resourceTabs.update();
        expect(resourceTabs.find('ResourceTabs Tab')).toHaveLength(1);
        expect(resourceTabs.find('ResourceTabs Tab').text()).toEqual('Tab Titel 1');

        resourceTabs.instance().resourceStore.data.test = 2;
        setTimeout(() => {
            resourceTabs.update();
            expect(resourceTabs.find('ResourceTabs Tab')).toHaveLength(1);
            expect(resourceTabs.find('ResourceTabs Tab').text()).toEqual('Tab Titel 2');
            done();
        });
    });
});

test('Should redirect to first child route if no tab is active by default', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route1', attributes);
        done();
    });
});

test('Should redirect to first visible child route if no tab is active', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabCondition: 'test == 2',
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.data = {test: 2};

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should redirect to first visible child route if invisible tab is active', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 1',
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabCondition: 'test == 2',
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.data = {test: 2};

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should redirect to highest prioritized tab if no tab is active', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
            tabPriority: 100,
        },
    };
    const childRoute3 = {
        name: 'route3',
        options: {
            tabTitle: 'tabTitle3',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        expect(router.redirect).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should not redirect to first child route if resourceStore is not initialized', (done) => {
    ResourceStore.mockImplementation(function() {
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.initialized = false;

    setTimeout(() => {
        expect(router.redirect).not.toBeCalledWith('route1', attributes);
        done();
    });
});

test('Should not redirect to first child route if resourceStore is currently loading', (done) => {
    ResourceStore.mockImplementation(function() {
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.loading = true;

    setTimeout(() => {
        expect(router.redirect).not.toBeCalledWith('route1', attributes);
        done();
    });
});

test('Should not redirect if a tab is already active', () => {
    ResourceStore.mockImplementation(function() {
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabTitle: 'tabTitle1',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {
            tabTitle: 'tabTitle2',
        },
    };

    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        id: 1,
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        redirect: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    expect(router.redirect).not.toBeCalled();
});

test('Should reload ResourceStore if route is about to change to another child route', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        navigate: jest.fn(),
        route: childRoute2,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[0][0](childRoute1);

    expect(resourceTabs.instance().resourceStore.reload).toBeCalledWith();
});

test('Should not reload ResourceStore if route is about to change to same route', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        navigate: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[0][0](childRoute1);

    expect(resourceTabs.instance().resourceStore.reload).not.toBeCalled();
});

test('Should reload ResourceStore if route is about to change to parent route', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        navigate: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[0][0](route);

    expect(resourceTabs.instance().resourceStore.reload).toBeCalledWith();
});

test('Should not reload ResourceStore if route is about to change to route outside of tabs', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        this.reload = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const route1 = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
        ],
    };
    const route2 = {};

    const attributes = {
        attribute: 'value',
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        navigate: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route1} router={router}>{() => (<Child />)}</ResourceTabs>);

    router.addUpdateRouteHook.mock.calls[0][0](route2);

    expect(resourceTabs.instance().resourceStore.reload).not.toBeCalledWith();
});

test('Should navigate to child route if tab is clicked', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {},
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        navigate: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);

    setTimeout(() => {
        resourceTabs.update();
        resourceTabs.find('Tab button').at(1).simulate('click');
        expect(router.navigate).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should navigate to child route if tab is clicked with hidden tabs', (done) => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        this.load = jest.fn();
        extendObservable(this, {data: {}});
    });

    const childRoute1 = {
        name: 'route1',
        options: {
            tabCondition: 'test == 2',
        },
    };
    const childRoute2 = {
        name: 'route2',
        options: {},
    };
    const childRoute3 = {
        name: 'route3',
        options: {},
    };
    const route = {
        options: {
            resourceKey: 'test',
        },
        children: [
            childRoute1,
            childRoute2,
            childRoute3,
        ],
    };

    const attributes = {
        attribute: 'value',
    };

    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes,
        navigate: jest.fn(),
        redirect: jest.fn(),
        route: childRoute1,
    };

    const Child = () => (<h1>Child</h1>);
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => (<Child />)}</ResourceTabs>);
    resourceTabs.instance().resourceStore.data = {test: 1};

    setTimeout(() => {
        resourceTabs.update();
        resourceTabs.find('Tab button').at(0).simulate('click');
        expect(router.navigate).toBeCalledWith('route2', attributes);
        done();
    });
});

test('Should create a ResourceStore on mount and destroy it on unmount', () => {
    ResourceStore.mockImplementation(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        route,
        attributes: {
            id: 5,
        },
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).not.toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed in route options', () => {
    ResourceStore.mockImplementation(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should create a ResourceStore with locale on mount if locales have been passed as observable array', () => {
    ResourceStore.mockImplementation(function() {
        this.destroy = jest.fn();
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: observable(['de', 'en']),
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    router.addUpdateRouteHook.mockImplementationOnce(() => jest.fn());
    const resourceTabs = mount(<ResourceTabs route={route} router={router}>{() => null}</ResourceTabs>);
    const resourceStoreConstructorCall = ResourceStore.mock.calls;
    expect(router.bind).toBeCalled();
    expect(resourceStoreConstructorCall[0][0]).toEqual('snippets');
    expect(resourceStoreConstructorCall[0][1]).toEqual(5);
    expect(resourceStoreConstructorCall[0][2].locale).toBeDefined();

    resourceTabs.unmount();
    expect(ResourceStore.mock.instances[0].destroy).toBeCalled();
});

test('Should pass the ResourceStore and locales to child components', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const locales = observable(['de', 'en']);
    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales,
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const ChildComponent = jest.fn(() => null);
    const resourceTabs = mount(
        <ResourceTabs
            locales={[]}
            route={route}
            router={router}
        >
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    ).instance();

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(resourceTabs.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toBe(locales);
});

test('Should pass locales from route options instead of props to child components', () => {
    ResourceStore.mockImplementation(function() {
        this.initialized = true;
        extendObservable(this, {data: {}});
    });

    const route = {
        children: [],
        options: {
            resourceKey: 'snippets',
            locales: ['de', 'en'],
        },
    };
    const router = {
        addUpdateRouteHook: jest.fn(),
        attributes: {
            id: 5,
        },
        bind: jest.fn(),
        route,
    };

    const ChildComponent = jest.fn(() => null);
    const resourceTabs = mount(
        <ResourceTabs locales={['fr', 'nl']} route={route} router={router}>
            {(props) => (<ChildComponent {...props} />)}
        </ResourceTabs>
    ).instance();

    expect(ChildComponent.mock.calls[0][0].resourceStore).toBe(resourceTabs.resourceStore);
    expect(ChildComponent.mock.calls[0][0].locales).toEqual(['de', 'en']);
});

test('Should throw an error when no resourceKey is defined in the route options', () => {
    const route = {
        options: {},
    };

    const router = {
        route,
        attributes: {
            id: 5,
        },
    };

    expect(() => render(<ResourceTabs route={route} router={router} />)).toThrow(/mandatory "resourceKey" option/);
});
