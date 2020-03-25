/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import mockReact from 'react';
import {findWithHighOrderFunction} from '../../../utils/TestHelper';
import ResourceStore from '../../../stores/ResourceStore';

const React = mockReact;

jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('jexl', () => ({
    evalSync: jest.fn().mockImplementation((expression) => {
        if (undefined === expression) {
            throw new Error('Expression cannot be undefined');
        }

        return expression === 'nodeType == 1';
    }),
}));

jest.mock('../../Form', () => class FormMock extends mockReact.Component<*> {
    resourceFormStore = {
        data: {
            testKey: 'test-value',
        },
    };

    render() {
        return <div>form view mock</div>;
    }
});

jest.mock('../../../containers/Sidebar/withSidebar', () => jest.fn((Component) => Component));

beforeEach(() => {
    jest.resetModules();
});

test('Should render Form view', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 1',
        },
    };
    const router = {
        route,
    };

    const PreviewForm = require('../PreviewForm').default;

    expect(render(
        <PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />
    )).toMatchSnapshot();
});

test('Should initialize preview sidebar per default when previewCondition is not set', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {},
    };
    const router = {
        route,
    };

    // require preview form to trigger call of withSidebar mock and retrieve passed function
    const PreviewForm = require('../PreviewForm').default;
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const Form = require('../../Form');
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);

    // mount PreviewForm and call function that was passed to withSidebar
    const previewForm = mount(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);
    const sidebarConfig = sidebarFunction.call(previewForm.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);
    expect(sidebarConfig.props.formStore).toBeDefined();

    // check if evalSync was called with correct parameters during function call
    const jexl = require('jexl');
    expect(jexl.evalSync).not.toBeCalled();
});

test('Should initialize preview sidebar when previewCondition evaluates to true', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 1',
        },
    };
    const router = {
        route,
    };

    // require preview form to trigger call of withSidebar mock and retrieve passed function
    const PreviewForm = require('../PreviewForm').default;
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const Form = require('../../Form');
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);

    // mount PreviewForm and call function that was passed to withSidebar
    const previewForm = mount(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);
    const sidebarConfig = sidebarFunction.call(previewForm.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig.view).toEqual('sulu_preview.preview');
    expect(sidebarConfig.sizes).toEqual(['medium', 'large']);
    expect(sidebarConfig.props.router).toEqual(router);
    expect(sidebarConfig.props.formStore).toBeDefined();

    // check if evalSync was called with correct parameters during function call
    const jexl = require('jexl');
    expect(jexl.evalSync).toBeCalledWith( 'nodeType == 1', {testKey: 'test-value'});
});

test('Should not initialize preview sidebar when previewCondition evaluates to true', () => {
    const resourceStore = new ResourceStore('snippet', 1);

    const route = {
        options: {
            previewCondition: 'nodeType == 2',
        },
    };
    const router = {
        route,
    };

    // require preview form to trigger call of withSidebar mock and retrieve passed function
    const PreviewForm = require('../PreviewForm').default;
    const withSidebar = require('../../../containers/Sidebar/withSidebar');
    const Form = require('../../Form');
    const sidebarFunction = findWithHighOrderFunction(withSidebar, Form);

    // mount PreviewForm and call function that was passed to withSidebar
    const previewForm = mount(<PreviewForm locales={[]} resourceStore={resourceStore} route={route} router={router} />);
    const sidebarConfig = sidebarFunction.call(previewForm.instance());

    // check if function that was passed to withSidebar returns the correct SidebarConfig
    expect(sidebarConfig).toEqual(null);

    // check if evalSync was called with correct parameters during function call
    const jexl = require('jexl');
    expect(jexl.evalSync).toBeCalledWith( 'nodeType == 2', {testKey: 'test-value'});
});
