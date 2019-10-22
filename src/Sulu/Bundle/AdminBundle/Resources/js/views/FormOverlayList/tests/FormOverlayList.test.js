// @flow
import mockReact from 'react';
import {mount} from 'enzyme';
import {observable} from 'mobx';
import FormOverlayList from '../FormOverlayList';
import List from '../../List';
import Overlay from '../../../components/Overlay';
import ResourceStore from '../../../stores/ResourceStore';
import ResourceFormStore from '../../../containers/Form/stores/ResourceFormStore';
import Form from '../../../containers/Form';
import Router from '../../../services/Router';
import type {Route} from '../../../services/Router';
import Snackbar from '../../../components/Snackbar';

const React = mockReact;

jest.mock('../../List', () => class ListMock extends mockReact.Component<*> {
    render() {
        return <div>list view mock</div>;
    }
});

jest.mock('../../../containers/Form', () => class ListMock extends mockReact.Component<*> {
    render() {
        return <div>form container mock</div>;
    }
});

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn(
    (resourceKey, itemId) => {
        return {
            id: itemId,
        };
    }
));
jest.mock('../../../containers/Form/stores/ResourceFormStore', () => jest.fn(
    (resourceStore, formKey, options, metadataOptions) => {
        return {
            id: resourceStore.id,
            metadataOptions: metadataOptions,
        };
    }
));

test('View should render with closed overlay', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {},
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    expect(formOverlayList.render()).toMatchSnapshot();
});

test('View should render with opened overlay', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                addOverlayTitle: 'app.add_overlay_title',
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    expect(formOverlayList.render()).toMatchSnapshot();
});

test('Should pass correct props to List view', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                adapters: ['table'],
                addRoute: 'addRoute',
                listKey: 'test-list-key',
                formKey: 'test-form-key',
                addOverlayTitle: 'app.add_overlay_title',
                editOverlayTitle: 'app.edit_overlay_title',
                overlaySize: 'large',
                resourceKey: 'test-resource-key',
                toolbarActions: ['sulu_admin.add'],
                routerAttributesToListRequest: {'0': 'category', 'id': 'parentId'},
                routerAttributesToFormRequest: {'0': 'category', 'id': 'parentId'},
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);
    const list = formOverlayList.find(List);

    expect(list.props()).toEqual(expect.objectContaining(formOverlayList.props()));
    expect(list.props().locale).toBeDefined();
    expect(list.props().onItemAdd).toBeDefined();
    expect(list.props().onItemClick).toBeDefined();
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-add callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                formKey: 'test-form-key',
                resourceKey: 'test-resource-key',
                routerAttributesToFormRequest: {'0': 'category', 'id': 'parentId'},
                resourceStorePropertiesToFormRequest: {'0': 'webspace', 'dimension': 'dimensionId'},
            },
        },
    }: any);

    const testResourceStore = new ResourceStore('test');
    testResourceStore.data = {
        webspace: 'test-webspace',
        dimension: 'test-dimension',
    };

    const formOverlayList = mount(<FormOverlayList resourceStore={testResourceStore} route={route} router={router} />);
    formOverlayList.find(List).props().onItemAdd();

    expect(ResourceStore).toBeCalledWith('test-resource-key', undefined, {}, {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    });
    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    }, {});
});

test('Should construct ResourceStore and ResourceFormStore with correct parameters on item-click callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                formKey: 'test-form-key',
                resourceKey: 'test-resource-key',
                routerAttributesToFormRequest: {'0': 'category', 'id': 'parentId'},
                resourceStorePropertiesToFormRequest: {'0': 'webspace', 'dimension': 'dimensionId'},
            },
        },
    }: any);

    const testResourceStore = new ResourceStore('test');
    testResourceStore.data = {
        webspace: 'test-webspace',
        dimension: 'test-dimension',
    };

    const formOverlayList = mount(<FormOverlayList resourceStore={testResourceStore} route={route} router={router} />);

    const locale = observable.box('en');
    formOverlayList.instance().locale = locale;

    formOverlayList.find(List).props().onItemClick('item-id');

    expect(ResourceStore).toBeCalledWith('test-resource-key', 'item-id', {locale}, {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    });
    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'test-form-key', {
        category: 'category-id',
        parentId: 'test-id',
        webspace: 'test-webspace',
        dimensionId: 'test-dimension',
    }, {});
});

test('Should pass metadataRequestParameters options to Form View', () => {
    const metadataRequestParameters = {
        'testParam': 'testValue',
    };

    const route: Route = ({}: any);
    const router: Router = ({
        attributes: {
            id: 'test-id',
            category: 'category-id',
        },
        route: {
            options: {
                formKey: 'test-form-key',
                resourceKey: 'test-resource-key',
                metadataRequestParameters: metadataRequestParameters,
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    formOverlayList.instance().locale = observable.box('en');
    formOverlayList.find(List).props().onItemAdd();

    expect(ResourceFormStore).toBeCalledWith(expect.anything(), 'test-form-key', {}, metadataRequestParameters);

    const formStore = formOverlayList.instance().formStore;
    expect(formStore.metadataOptions).toEqual(metadataRequestParameters);
});

test('Should open Overlay with correct props when List fires the item-add callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
                addOverlayTitle: 'app.add_overlay_title',
                overlaySize: 'large',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);
    formOverlayList.find(List).props().onItemAdd();

    formOverlayList.update();
    const overlay = formOverlayList.find(Overlay);

    expect(overlay.props()).toEqual(expect.objectContaining({
        confirmDisabled: true,
        confirmLoading: false,
        confirmText: 'sulu_admin.save',
        open: true,
        size: 'large',
        title: 'app.add_overlay_title',
    }));
});

test('Should open Overlay with correct props when List fires the item-click callback', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
                editOverlayTitle: 'app.edit_overlay_title',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);
    formOverlayList.find(List).props().onItemClick('item-id');

    formOverlayList.update();
    const overlay = formOverlayList.find(Overlay);

    expect(overlay.props()).toEqual(expect.objectContaining({
        confirmDisabled: true,
        confirmLoading: false,
        confirmText: 'sulu_admin.save',
        open: true,
        size: 'small',
        title: 'app.edit_overlay_title',
    }));
});

test('Should submit Form container when Overlay is confirmed', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    const submitSpy = jest.fn();
    formOverlayList.find(Form).instance().submit = submitSpy;

    formOverlayList.find(Overlay).props().onConfirm();

    expect(submitSpy).toBeCalled();
});

test('Should destroy ResourceFormStore without saving when Overlay is closed', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    const saveSpy = jest.fn();
    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.save = saveSpy;
    formOverlayList.instance().formStore.destroy = destroySpy;

    formOverlayList.find(Overlay).props().onClose();
    formOverlayList.update();

    expect(saveSpy).not.toBeCalled();
    expect(destroySpy).toBeCalled();
    expect(formOverlayList.find(Overlay).exists()).toBeFalsy();
});

test('Should save ResoureFormStore, close overlay and reload List view on submit of Form', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    const savePromise = Promise.resolve();
    const saveSpy = jest.fn(() => savePromise);
    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.save = saveSpy;
    formOverlayList.instance().formStore.destroy = destroySpy;

    const reloadSpy = jest.fn();
    formOverlayList.find(List).instance().reload = reloadSpy;

    formOverlayList.find(Form).props().onSubmit();

    return savePromise.finally(() => {
        expect(saveSpy).toBeCalled();
        expect(destroySpy).toBeCalled();
        expect(reloadSpy).toBeCalled();

        formOverlayList.update();
        expect(formOverlayList.find(Overlay).exists()).toBeFalsy();
    });
});

test('Should display Snackbar if an error happens during saving of ResourceFormStore', (done) => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    const savePromise = Promise.reject('error');
    const saveSpy = jest.fn(() => savePromise);
    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.save = saveSpy;
    formOverlayList.instance().formStore.destroy = destroySpy;

    const reloadSpy = jest.fn();
    formOverlayList.find(List).instance().reload = reloadSpy;

    formOverlayList.find(Form).props().onSubmit();

    // wait until rejection of savePromise was handled by component with setTimeout
    setTimeout(() => {
        expect(saveSpy).toBeCalled();
        expect(destroySpy).not.toBeCalled();
        expect(reloadSpy).not.toBeCalled();

        formOverlayList.update();
        expect(formOverlayList.find(Overlay).exists()).toBeTruthy();
        expect(formOverlayList.find(Snackbar).exists).toBeTruthy();
        expect(formOverlayList.find(Snackbar).prop('visible')).toBeTruthy();
        expect(formOverlayList.find(Snackbar).prop('message')).toEqual('sulu_admin.form_save_server_error');

        done();
    });
});

test('Should display Snackbar if an form is not valid', (done) => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    const destroySpy = jest.fn();

    const reloadSpy = jest.fn();
    formOverlayList.find(List).instance().reload = reloadSpy;

    formOverlayList.find(Form).props().onError();

    // wait until rejection of savePromise was handled by component with setTimeout
    setTimeout(() => {
        expect(destroySpy).not.toBeCalled();
        expect(reloadSpy).not.toBeCalled();

        formOverlayList.update();
        expect(formOverlayList.find(Overlay).exists()).toBeTruthy();
        expect(formOverlayList.find(Snackbar).exists).toBeTruthy();
        expect(formOverlayList.find(Snackbar).prop('visible')).toBeTruthy();
        expect(formOverlayList.find(Snackbar).prop('message')).toEqual('sulu_admin.form_contains_invalid_values');

        done();
    });
});

test('Should hide Snackbar when closeClick callback of Snackbar is fired', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    formOverlayList.instance().formErrors.push('error 1');
    formOverlayList.update();
    expect(formOverlayList.find(Snackbar).props().visible).toBeTruthy();

    formOverlayList.find(Snackbar).props().onCloseClick();
    formOverlayList.update();
    expect(formOverlayList.find(Snackbar).props().visible).toBeFalsy();
});

test('Should destroy ResourceFormStore when component is unmounted', () => {
    const route: Route = ({}: any);
    const router: Router = ({
        route: {
            options: {
                formKey: 'test-form-key',
            },
        },
    }: any);

    const formOverlayList = mount(<FormOverlayList route={route} router={router} />);

    // open form overlay for new item
    formOverlayList.find(List).props().onItemAdd();
    formOverlayList.update();

    const destroySpy = jest.fn();
    formOverlayList.instance().formStore.destroy = destroySpy;

    formOverlayList.unmount();
    expect(destroySpy).toBeCalled();
});
