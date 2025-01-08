// @flow
import React from 'react';
import {observable} from 'mobx';
import {mount} from 'enzyme';
import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor';
import CKEditor5 from '../CKEditor5';
import configRegistry from '../registries/configRegistry';
import pluginRegistry from '../registries/pluginRegistry';

jest.mock('../registries/pluginRegistry', () => ({
    plugins: [],
}));

jest.mock('../registries/configRegistry', () => ({
    configs: [],
}));

jest.mock('@ckeditor/ckeditor5-editor-classic/src/classiceditor', () => ({
    create: jest.fn(),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const defaultEditor = {
    editing: {
        view: {
            document: {
                on: jest.fn(),
            },
        },
    },
    model: {
        document: {
            on: jest.fn(),
        },
    },
    ui: {
        element: {
            classList: {
                add: jest.fn(),
                remove: jest.fn(),
            },
        },
    },
    getData: jest.fn(),
    setData: jest.fn(),
    isReadOnly: false,
    enableReadOnlyMode: () => {
    },
    disableReadOnlyMode: () => {
    },
};

test('Create a CKEditor5 instance', () => {
    const editor = {
        ...defaultEditor,
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    const locale = observable.box('en');

    mount(<CKEditor5 locale={locale} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toBeCalledWith(expect.anything(), expect.objectContaining({
        heading: {
            options: [
                {
                    class: 'ck-heading_paragraph',
                    model: 'paragraph',
                    title: 'sulu_admin.paragraph',
                },
                {
                    class: 'ck-heading_heading2',
                    model: 'heading2',
                    title: 'sulu_admin.heading2',
                    view: 'h2',
                },
                {
                    class: 'ck-heading_heading3',
                    model: 'heading3',
                    title: 'sulu_admin.heading3',
                    view: 'h3',
                },
                {
                    class: 'ck-heading_heading4',
                    model: 'heading4',
                    title: 'sulu_admin.heading4',
                    view: 'h4',
                },
                {
                    class: 'ck-heading_heading5',
                    model: 'heading5',
                    title: 'sulu_admin.heading5',
                    view: 'h5',
                },
                {
                    class: 'ck-heading_heading6',
                    model: 'heading6',
                    title: 'sulu_admin.heading6',
                    view: 'h6',
                },
            ],
        },
        sulu: {
            locale: 'en',
        },
    }));
});

test('Create a CKEditor5 instance with an additional plugin', () => {
    const Plugin = class {};
    pluginRegistry.plugins = [Plugin];

    const config = jest.fn((config) => ({
        toolbar: [...config.toolbar, 'plugin1', 'plugin2'],
    }));
    configRegistry.configs = [config];

    const editor = {
        ...defaultEditor,
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toBeCalledWith(expect.anything(), expect.objectContaining({
        plugins: expect.arrayContaining([Plugin]),
        toolbar: expect.arrayContaining(['bold', 'italic', 'underline', 'plugin1', 'plugin2']),
    }));
});

test('Create a CKEditor5 instance with given formats', () => {
    const editor = {
        ...defaultEditor,
    };
    ClassicEditor.create.mockReturnValue(Promise.resolve(editor));

    mount(<CKEditor5 formats={['h1', 'h2', 'h3']} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    expect(ClassicEditor.create).toBeCalledWith(expect.anything(), expect.objectContaining({
        heading: {
            options: [
                {
                    class: 'ck-heading_paragraph',
                    model: 'paragraph',
                    title: 'sulu_admin.paragraph',
                },
                {
                    class: 'ck-heading_heading1',
                    model: 'heading1',
                    title: 'sulu_admin.heading1',
                    view: 'h1',
                },
                {
                    class: 'ck-heading_heading2',
                    model: 'heading2',
                    title: 'sulu_admin.heading2',
                    view: 'h2',
                },
                {
                    class: 'ck-heading_heading3',
                    model: 'heading3',
                    title: 'sulu_admin.heading3',
                    view: 'h3',
                },
            ],
        },
        sulu: {
            locale: undefined,
        },
    }));
});

test('Set data on editor when value is updated', () => {
    const editor = {
        ...defaultEditor,
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        ckeditor.setProps({value: '<p>Test</p>'});

        expect(editor.setData).toBeCalledWith('<p>Test</p>');
    });
});

test('Do not set data on editor when value is not changed when props change', () => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('<p>Test</p>'),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value="<p>Test</p>" />);

    return editorPromise.then(() => {
        editor.setData.mockClear();
        ckeditor.setProps({value: '<p>Test</p>'});

        expect(editor.setData).not.toBeCalled();
    });
});

test('Do not set data on editor when value and editorData is undefined', () => {
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue(),
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    const ckeditor = mount(<CKEditor5 onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        editor.setData.mockClear();
        ckeditor.setProps({});

        expect(editor.setData).not.toBeCalled();
    });
});

test('Set disabled class and isReadOnly property to CKEditor5', () => {
    const editor = {
        ...defaultEditor,
        isReadOnly: false,
        enableReadOnlyMode: () => {
            editor.isReadOnly = true;
        },
        disableReadOnlyMode: () => {
            editor.isReadOnly = false;
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 disabled={true} onBlur={jest.fn()} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        expect(ClassicEditor.create).toBeCalled();
        expect(editor.ui.element.classList.add).toBeCalledWith('disabled');
        expect(editor.isReadOnly).toEqual(true);
    });
});

test('Call onChange prop when something changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([{}]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toBeCalledWith('test');
    });
});

test('Call onChange prop with undefined if editor is empty', () => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue(''),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([{}]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).toBeCalledWith(undefined);
    });
});

test('Do not call onChange prop when nothing changed', () => {
    const changeSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={jest.fn()} onChange={changeSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.model.document.on.mock.calls[0][1]();
        expect(changeSpy).not.toBeCalled();
    });
});

test('Call onBlur prop when CKEditor5 fires its blur event', () => {
    const blurSpy = jest.fn();
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onBlur={blurSpy} onChange={jest.fn()} value={undefined} />);

    return editorPromise.then(() => {
        editor.editing.view.document.on.mock.calls[0][1]();
        expect(blurSpy).toBeCalled();
    });
});

test('Call onFocus prop when CKEditor5 fires its focus event', () => {
    const focusSpy = jest.fn();
    const target = new EventTarget();
    const querySelectorSpy = jest.fn().mockReturnValue(target);
    const editor = {
        ...defaultEditor,
        getData: jest.fn().mockReturnValue('test'),
        model: {
            document: {
                on: jest.fn(),
                differ: {
                    getChanges: jest.fn().mockReturnValue([]),
                },
            },
        },
        ui: {
            element: {
                querySelector: querySelectorSpy,
            },
        },
    };

    const editorPromise = Promise.resolve(editor);
    ClassicEditor.create.mockReturnValue(editorPromise);

    mount(<CKEditor5 onChange={jest.fn()} onFocus={focusSpy} value={undefined} />);

    return editorPromise.then(() => {
        editor.editing.view.document.on.mock.calls[0][1]();
        expect(focusSpy).toBeCalledWith({target});
        expect(querySelectorSpy).toBeCalledWith('div[contenteditable="true"]');
    });
});
