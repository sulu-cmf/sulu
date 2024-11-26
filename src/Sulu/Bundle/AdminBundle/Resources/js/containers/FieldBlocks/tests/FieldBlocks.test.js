// @flow
import React from 'react';
import {mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import Router from '../../../services/Router';
import fieldTypeDefaultProps from '../../../utils/TestHelper/fieldTypeDefaultProps';
import FieldBlocks from '../FieldBlocks';
import FormInspector from '../../Form/FormInspector';
import metadataStore from '../../Form/stores/metadataStore';
import ResourceFormStore from '../../Form/stores/ResourceFormStore';
import ResourceStore from '../../../stores/ResourceStore';
import blockPreviewTransformerRegistry from '../registries/blockPreviewTransformerRegistry';
import fieldRegistry from '../../Form/registries/fieldRegistry';
import SingleSelect from '../../Form/fields/SingleSelect';
import conditionDataProviderRegistry from '../../Form/registries/conditionDataProviderRegistry';

jest.mock('../../../services/Router/Router', () => jest.fn());
jest.mock('../../Form/FormInspector', () => jest.fn(function() {
    this.isFieldModified = jest.fn();
    this.getSchemaEntryByPath = jest.fn();
}));
jest.mock('../../Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));
jest.mock('../../Form/stores/ResourceFormStore', () => jest.fn());
jest.mock('../../../stores/ResourceStore', () => jest.fn());

jest.mock('../../Form/registries/fieldRegistry', () => ({
    get: jest.fn((type) => {
        switch (type) {
            case 'checkbox':
                return function Checkbox({value}) {
                    return <input type="checkbox" value={value} />;
                };
            case 'text_line':
                return function TextLine({error, value}) {
                    return <input className={error && error.keyword} defaultValue={value} type="text" />;
                };
        }
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../registries/blockPreviewTransformerRegistry', () => ({
    has: jest.fn(),
    get: jest.fn(),
    blockPreviewTransformerKeysByPriority: [],
}));

beforeEach(() => {
    blockPreviewTransformerRegistry.has.mockClear();
    blockPreviewTransformerRegistry.get.mockClear();
    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [];
});

test('Render collapsed blocks with block previews', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                something: {
                    label: 'Something',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_area',
                },
                nothing: {
                    label: 'Nothing',
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {attributes: {icon: 'su-eye'}, name: 'sulu.block_setting_icon'},
            ],
            type: 'checkbox',
        },
        section: {
            items: {
                section_setting: {
                    tags: [
                        {attributes: {icon: 'su-hide'}, name: 'sulu.block_setting_icon'},
                    ],
                    type: 'checkbox',
                },
            },
            type: 'section',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            text2: undefined,
            something: 'Test 3',
            type: 'default',
            settings: {
                setting: true,
            },
        },
        {
            text1: 'Test 4',
            text2: undefined,
            something: 'Test 6',
            type: 'default',
            settings: {
                section_setting: true,
            },
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
        }
    });

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        fieldBlocks.update();
        expect(fieldBlocks.render()).toMatchSnapshot();
    });
});

test('Render collapsed blocks with block previews and sections', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                section1: {
                    label: 'Section',
                    type: 'section',
                    items: {
                        text1: {
                            label: 'Text 1',
                            tags: [
                                {name: 'sulu.block_preview'},
                            ],
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            tags: [
                                {name: 'sulu.block_preview'},
                            ],
                            type: 'text_line',
                        },
                        something: {
                            label: 'Something',
                            tags: [
                                {name: 'sulu.block_preview'},
                            ],
                            type: 'text_area',
                        },
                        nothing: {
                            label: 'Nothing',
                            type: 'text_line',
                        },
                    },
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: undefined,
            something: 'Test 2',
            type: 'default',
        },
        {
            text1: undefined,
            text2: 'Test 3',
            something: 'Test 4',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return true;
            case 'text_area':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
            case 'text_area':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
        }
    });

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Render collapsed blocks with block previews without tags and with sections', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                section1: {
                    label: 'Section',
                    type: 'section',
                    items: {
                        nothing: {
                            label: 'Nothing',
                            type: 'phone',
                        },
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'media_selection',
                        },
                        something: {
                            label: 'Text 3',
                            type: 'text_editor',
                        },
                    },
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            nothing: 'phone',
            text1: 'Test 1',
            text2: 'Test 2',
            something: 'Test 3',
            type: 'default',
        },
        {
            nothing: 'phone',
            text1: 'Test 4',
            text2: 'Test 5',
            something: 'Test 6',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'media_selection':
            case 'phone':
            case 'text_line':
            case 'text_editor':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'phone':
                return {
                    transform: function Transformer() {
                        return <p>phone</p>;
                    },
                };
            case 'media_selection':
                return {
                    transform: function Transformer() {
                        return <p>media_selection</p>;
                    },
                };
            case 'text_line':
                return {
                    transform: function Transformer() {
                        return <p>text_line</p>;
                    },
                };
            case 'text_editor':
                return {
                    transform: function Transformer() {
                        return <p>text_editor</p>;
                    },
                };
        }
    });

    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [
        'media_selection',
        'text_line',
        'text_editor',
    ];

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Render collapsed blocks with block previews without tags', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                nothing: {
                    label: 'Nothing',
                    type: 'phone',
                },
                text1: {
                    label: 'Text 1',
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    type: 'media_selection',
                },
                something: {
                    label: 'Text 3',
                    type: 'text_editor',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            nothing: 'phone',
            text1: 'Test 1',
            text2: 'Test 2',
            something: 'Test 3',
            type: 'default',
        },
        {
            nothing: 'phone',
            text1: 'Test 4',
            text2: 'Test 5',
            something: 'Test 6',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'media_selection':
            case 'phone':
            case 'text_line':
            case 'text_editor':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'phone':
                return {
                    transform: function Transformer() {
                        return <p>phone</p>;
                    },
                };
            case 'media_selection':
                return {
                    transform: function Transformer() {
                        return <p>media_selection</p>;
                    },
                };
            case 'text_line':
                return {
                    transform: function Transformer() {
                        return <p>text_line</p>;
                    },
                };
            case 'text_editor':
                return {
                    transform: function Transformer() {
                        return <p>text_editor</p>;
                    },
                };
        }
    });

    // $FlowFixMe
    blockPreviewTransformerRegistry.blockPreviewTransformerKeysByPriority = [
        'media_selection',
        'text_line',
        'text_editor',
    ];

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Render collapsed blocks with block previews', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview', priority: -100},
                    ],
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                something: {
                    label: 'Text 3',
                    tags: [
                        {name: 'sulu.block_preview', priority: 100},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: 'Test 2',
            something: 'Test 3',
            type: 'default',
        },
        {
            text1: 'Test 4',
            text2: 'Test 5',
            something: 'Test 6',
            type: 'default',
        },
    ];

    blockPreviewTransformerRegistry.has.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return true;
            default:
                return false;
        }
    });

    blockPreviewTransformerRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'text_line':
                return {
                    transform: function Transformer(value) {
                        return <p>{value}</p>;
                    },
                };
        }
    });

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Render block with schema', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text1: 'Test 1',
            text2: 'Test 2',
            type: 'default',
        },
        {
            text1: 'Test 3',
            text2: 'Test 4',
            type: 'default',
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Call not onChange on componentDidUpdate when new types are the same', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const changeSpy = jest.fn();

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={{
                default: {
                    title: 'Default',
                    form: {
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'text_line',
                        },
                    },
                },
            }}
            value={[
                {
                    text1: 'Test 1',
                    text2: 'Test 2',
                    type: 'default',
                },
                {
                    text1: 'Test 3',
                    text2: 'Test 4',
                    type: 'default',
                },
            ]}
        />
    );

    fieldBlocks.setProps({
        defaultType: 'default',
        value: [
            {
                text1: 'Test 1 a',
                text2: 'Test 2 b',
                type: 'default',
            },
            {
                text1: 'Test 3 a',
                text2: 'Test 4 c',
                type: 'default',
            },
        ],
        types: {
            default: {
                title: 'Default',
                form: {
                    text1: {
                        label: 'Text 1 a',
                        type: 'text_line',
                    },
                    text2: {
                        label: 'Text 2 b',
                        type: 'text_line',
                    },
                },
            },
        },
    });

    expect(changeSpy).not.toBeCalled();
});

test('Call onChange on componentDidUpdate when type not longer exist', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const changeSpy = jest.fn();

    // use mount instead of shallow here to test if component is correctly rendered
    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            onChange={changeSpy}
            types={{
                default: {
                    title: 'Default',
                    form: {
                        text1: {
                            label: 'Text 1',
                            type: 'text_line',
                        },
                        text2: {
                            label: 'Text 2',
                            type: 'text_line',
                        },
                    },
                },
            }}
            value={[
                {
                    text1: 'Test 1',
                    text2: 'Test 2',
                    type: 'default',
                },
                {
                    text1: 'Test 3',
                    text2: 'Test 4',
                    type: 'default',
                },
            ]}
        />
    );

    fieldBlocks.setProps({
        defaultType: 'new',
        value: [
            {
                text1: 'Test 1',
                text2: 'Test 2',
                type: 'not-exist',
            },
            {
                text1: 'Test 3',
                text2: 'Test 4',
                type: 'default',
            },
        ],
        types: {
            new: {
                title: 'Default',
                form: {
                    text1: {
                        label: 'Text 1',
                        type: 'text_line',
                    },
                    text2: {
                        label: 'Text 2',
                        type: 'text_line',
                    },
                },
            },
        },
    });

    expect(changeSpy).toBeCalledWith([
        {
            text1: 'Test 1',
            text2: 'Test 2',
            type: 'new',
        },
        {
            text1: 'Test 3',
            text2: 'Test 4',
            type: 'new',
        },
    ]);
});

test('Render block with schema and error on fields already being modified', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text: 'Test1',
            type: 'default',
        },
        {
            text: 'T2',
            type: 'default',
        },
        {
            text: 'T3',
            type: 'default',
        },
    ];

    const error = [
        undefined,
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
    ];

    formInspector.isFieldModified.mockImplementation((dataPath) => {
        return dataPath === '/block/0/text' || dataPath === '/block/1/text';
    });

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath="/block"
            defaultType="editor"
            error={error}
            formInspector={formInspector}
            schemaPath="/block"
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(2).simulate('click');

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Render block with schema and error on fields already being modified', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const value = [
        {
            text: 'Test1',
            type: 'default',
        },
        {
            text: 'T2',
            type: 'default',
        },
        {
            text: 'T3',
            type: 'default',
        },
    ];

    const error = [
        undefined,
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
        {
            text: {
                keyword: 'minLength',
                parameters: {},
            },
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            error={error}
            formInspector={formInspector}
            showAllErrors={true}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(2).simulate('click');

    fieldBlocks.find('Block').at(0).find('Field').at(0).prop('onFinish')('text');
    fieldBlocks.find('Block').at(1).find('Field').at(0).prop('onFinish')('text');

    expect(fieldBlocks.render()).toMatchSnapshot();
});

test('Should correctly pass props to the BlockCollection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [];

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={1}
            schemaOptions={{
                add_button_text: {name: 'add_button_text', title: 'custom-add-text'},
                paste_button_text: {name: 'paste_button_text', title: 'custom-paste-text'}}
            }
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.find('BlockCollection').props()).toEqual(expect.objectContaining({
        addButtonText: 'custom-add-text',
        pasteButtonText: 'custom-paste-text',
        collapsable: true,
        disabled: true,
        maxOccurs: 2,
        minOccurs: 1,
        movable: true,
        types: {
            default: 'Default',
        },
        value,
    }));
});

test('Should pass collapsable and movable props to the BlockCollection', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };
    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={1}
            schemaOptions={{movable: {name: 'movable', value: false}, collapsable: {name: 'collapsable', value: false}}}
            types={types}
            value={[]}
        />
    );

    expect(fieldBlocks.find('BlockCollection').props()).toEqual(expect.objectContaining({
        collapsable: false,
        movable: false,
    }));
});

test('Should pass new value to the BlockCollection if value prop is updated', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };

    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            disabled={true}
            formInspector={formInspector}
            label="Test"
            maxOccurs={2}
            minOccurs={1}
            types={types}
            value={[]}
        />
    );
    expect(fieldBlocks.find('BlockCollection').props().value).toEqual([]);

    fieldBlocks.setProps({value: [{type: 'default', text: 'One'}]});
    expect(fieldBlocks.find('BlockCollection').props().value).toEqual([{type: 'default', text: 'One'}]);

    fieldBlocks.setProps({value: observable([{type: 'default', text: 'Two'}])});
    expect(fieldBlocks.find('BlockCollection').props().value).toEqual([{type: 'default', text: 'Two'}]);

    fieldBlocks.setProps({value: observable([{type: 'default', text: 'Three'}])});
    expect(fieldBlocks.find('BlockCollection').props().value).toEqual([{type: 'default', text: 'Three'}]);
});

test('Should pass correct data and value and router to FieldRenderer', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const router = new Router();

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    type: 'text_line',
                },
            },
        },
    };
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const data = {
        title: 'Test',
    };

    const value = [
        {
            title: 'Test 1',
            type: 'default',
        },
        {
            title: 'Test 2',
            type: 'default',
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            data={data}
            dataPath=""
            defaultType="editor"
            formInspector={formInspector}
            router={router}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('SortableBlockList').prop('onExpand')(0);
    fieldBlocks.find('SortableBlockList').prop('onExpand')(1);
    fieldBlocks.update();

    expect(fieldBlocks.find('FieldRenderer').at(0).prop('data')).toEqual(data);
    expect(fieldBlocks.find('FieldRenderer').at(0).prop('value')).toEqual(value[0]);
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('data')).toEqual(data);
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('value')).toEqual(value[1]);
});

test('Should pass correct schemaPath and router to FieldRenderer', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const router = new Router();

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    type: 'text_line',
                },
            },
        },
    };
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="editor"
            formInspector={formInspector}
            router={router}
            schemaPath=""
            types={types}
            value={[{type: 'default'}, {type: 'default'}]}
        />
    );

    fieldBlocks.find('SortableBlockList').prop('onExpand')(0);
    fieldBlocks.find('SortableBlockList').prop('onExpand')(1);
    fieldBlocks.update();

    expect(fieldBlocks.find('FieldRenderer').at(0).prop('schemaPath')).toEqual('/types/default/form');
    expect(fieldBlocks.find('FieldRenderer').at(0).prop('router')).toEqual(router);
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('schemaPath')).toEqual('/types/default/form');
    expect(fieldBlocks.find('FieldRenderer').at(1).prop('router')).toEqual(router);
});

test('Should call onFinish when a field from the child renderer has finished editing', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const finishSpy = jest.fn();
    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="editor"
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onFinish={finishSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').simulate('click');
    fieldBlocks.find('FieldRenderer').prop('onFieldFinish')();

    expect(finishSpy).toBeCalledWith();
});

test ('Should set nested properties in handleBlockChange and call onChange with new values', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const changeSpy = jest.fn();
    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            dataPath=""
            defaultType="editor"
            fieldTypeOptions={{}}
            formInspector={formInspector}
            onChange={changeSpy}
            schemaPath=""
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').simulate('click');
    fieldBlocks.find('FieldRenderer').prop('onChange')(0, 'options/test1', 'value1');

    const expectedArray1 = [{type: 'default', options: {test1: 'value1'}}];
    expect(changeSpy).toBeCalledWith(expectedArray1);

    fieldBlocks.find('FieldRenderer').prop('onChange')(0, 'options/test2/test3', 'value2');
    const expectedArray2 = [{type: 'default', options: {test1: 'value1', test2: {test3: 'value2'}}}];
    expect(changeSpy).toBeCalledWith(expectedArray2);
});

test('Should call onFinish when the order of the blocks has changed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const finishSpy = jest.fn();
    const fieldBlocks = shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onFinish={finishSpy}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('BlockCollection').prop('onSortEnd')(0, 2);

    expect(finishSpy).toBeCalledWith();
});

test('Should open and close block settings overlay close button is clicked', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.exists('FormOverlay')).toEqual(false);

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(0).find('Icon[name="su-cog"]').simulate('click');
    expect(fieldBlocks.find('FormOverlay').prop('open')).toEqual(true);

    fieldBlocks.find('FormOverlay header Icon[name="su-times"]').simulate('click');
    expect(fieldBlocks.exists('FormOverlay')).toEqual(false);
});

test('Should open and close block settings overlay when confirm button is clicked with changed data', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [
        {type: 'default', settings: {setting: false}},
        {type: 'default', settings: {setting: false}},
    ];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(metadataStore.getSchema).toBeCalledWith('page_block_settings', undefined, undefined);
    expect(metadataStore.getJsonSchema).toBeCalledWith('page_block_settings', undefined, undefined);
    expect(fieldBlocks.exists('FormOverlay')).toEqual(false);

    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(1).find('Icon[name="su-cog"]').simulate('click');
    expect(fieldBlocks.find('FormOverlay').prop('open')).toEqual(true);

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        fieldBlocks.update();
        expect(changeSpy).not.toBeCalled();
        expect(fieldBlocks.exists('FormOverlay')).toEqual(true);

        fieldBlocks.find('Checkbox[dataPath="/setting"]').prop('onChange')(true);
        // should not change value of fieldBlocks until overlay is confirmed
        expect(changeSpy).not.toBeCalled();
        expect(fieldBlocks.instance().value[1].settings.setting).toEqual(false);

        fieldBlocks.find('FormOverlay Button[children="sulu_admin.apply"]').simulate('click');
        expect(fieldBlocks.exists('FormOverlay')).toEqual(false);
        expect(changeSpy).toBeCalledWith(
            [{type: 'default', settings: {setting: false}}, {type: 'default', settings: {setting: true}}]
        );
        expect(fieldBlocks.instance().value[1].settings.setting).toEqual(true);
    });
});

test('Should destroy create new formstore when block settings overlay is opened for another block', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [
        {type: 'default'},
        {type: 'default'},
    ];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(fieldBlocks.exists('FormOverlay')).toEqual(false);

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(0).find('Icon[name="su-cog"]').simulate('click');
    expect(fieldBlocks.find('FormOverlay').prop('open')).toEqual(true);
    const firstFormStore = fieldBlocks.find('FormOverlay').prop('formStore');

    fieldBlocks.find('FormOverlay header Icon[name="su-times"]').simulate('click');
    expect(fieldBlocks.exists('FormOverlay')).toEqual(false);

    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(1).find('Icon[name="su-cog"]').simulate('click');
    expect(fieldBlocks.find('FormOverlay').prop('open')).toEqual(true);
    expect(fieldBlocks.find('FormOverlay').prop('formStore')).not.toBe(firstFormStore);
});

test('Should not close block settings overlay when confirm button is clicked with invalid data', () => {
    const changeSpy = jest.fn();
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                    visible: true,
                },
            },
        },
    };
    const value = [
        {type: 'default'},
        {type: 'default'},
    ];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            mandatory: true,
            tags: [],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({type: 'object', required: ['setting']});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            onChange={changeSpy}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    expect(metadataStore.getSchema).toBeCalledWith('page_block_settings', undefined, undefined);
    expect(metadataStore.getJsonSchema).toBeCalledWith('page_block_settings', undefined, undefined);
    expect(fieldBlocks.exists('FormOverlay')).toEqual(false);

    fieldBlocks.find('Block').at(1).simulate('click');
    fieldBlocks.find('Block').at(1).find('Icon[name="su-cog"]').simulate('click');
    expect(fieldBlocks.find('FormOverlay').prop('open')).toEqual(true);

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        fieldBlocks.update();

        fieldBlocks.find('Overlay Button[children="sulu_admin.apply"]').simulate('click');
        expect(fieldBlocks.find('FormOverlay').prop('open')).toEqual(true);
        expect(changeSpy).not.toBeCalled();
    });
});

test('Should display and update correct icons based on block settings data and schema', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {attributes: {icon: 'su-hide'}, name: 'sulu.block_setting_icon'},
            ],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            type: 'default',
            settings: {
                setting: true,
            },
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');
    fieldBlocks.find('Block').at(0).find('Icon[name="su-cog"]').simulate('click');

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        fieldBlocks.update();
        expect(fieldBlocks.find('Block').at(0).find('Icon[name="su-hide"]').exists()).toBe(true);

        fieldBlocks.find('Checkbox[dataPath="/setting"]').prop('onChange')(false);
        fieldBlocks.find('FormOverlay Button[children="sulu_admin.apply"]').simulate('click');

        expect(fieldBlocks.find('Block').at(0).find('Icon[name="su-hide"]').exists()).toBe(false);
    });
});

test('Should display correct icons based on visibleCondition', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    conditionDataProviderRegistry.add(() => ({__locale: 'de'}));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {
                    attributes: {
                        icon: 'su-hide',
                        visibleCondition: '__locale == "de" && text1 == "Test 1"',
                    },
                    name: 'sulu.block_setting_icon',
                },
            ],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            type: 'default',
            settings: {
                setting: true,
            },
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        fieldBlocks.update();
        expect(fieldBlocks.find('Block').at(0).find('Icon[name="su-hide"]').exists()).toBe(true);

        fieldBlocks.setProps(
            {
                value: [
                    {
                        text1: 'Test 2',
                        type: 'default',
                        settings: {
                            setting: true,
                        },
                    },
                ],
            }
        );

        fieldBlocks.update();
        expect(fieldBlocks.find('Block').at(0).find('Icon[name="su-hide"]').exists()).toBe(false);
    });
});

test('Should recalculate visibleCondition only of changed blocks', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                text1: {
                    label: 'Text 1',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
                text2: {
                    label: 'Text 2',
                    tags: [
                        {name: 'sulu.block_preview'},
                    ],
                    type: 'text_line',
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const schemaPromise = Promise.resolve({
        setting: {
            tags: [
                {
                    attributes: {
                        icon: 'su-hide',
                        visibleCondition: '(__provider.text1 != "disabled" && text1 == "Test 1") || text2 == "Test 2"',
                    },
                    name: 'sulu.block_setting_icon',
                },
            ],
            type: 'checkbox',
        },
    });
    const jsonSchemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const value = [
        {
            text1: 'Test 1',
            type: 'default',
        },
        {
            text2: 'Test 2',
            type: 'default',
        },
    ];

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
            value={value}
        />
    );

    return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
        fieldBlocks.update();
        expect(fieldBlocks.find('Block').at(0).find('Icon[name="su-hide"]').exists()).toBe(true);
        expect(fieldBlocks.find('Block').at(1).find('Icon[name="su-hide"]').exists()).toBe(true);

        // add a provider to make the visibleCondition of the first block = false
        conditionDataProviderRegistry.add(() => ({
            __provider: {
                'text1': 'disabled',
            },
        }));

        fieldBlocks.setProps(
            {
                value: [
                    {
                        text1: 'Test 1',
                        type: 'default',
                    },
                    {
                        text2: 'Test 4',
                        type: 'default',
                    },
                ],
            }
        );

        fieldBlocks.update();
        // first block is still visible, because it was not reevaluated as only the second block changed
        expect(fieldBlocks.find('Block').at(0).find('Icon[name="su-hide"]').exists()).toBe(true);
        expect(fieldBlocks.find('Block').at(1).find('Icon[name="su-hide"]').exists()).toBe(false);
    });
});

test('Should destroy the block settings form-store on unmount', () => {
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
    };

    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: 'page_block_settings'}}}
            types={types}
        />
    );

    const destroySpy = jest.fn();
    fieldBlocks.instance().blockSettingsFormStore.destroy = destroySpy;

    fieldBlocks.unmount();

    expect(destroySpy).toBeCalledWith();
});

test('Should show correct value in type select after type is changed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    const types = {
        default: {
            title: 'Default',
            form: {
                text: {
                    label: 'Text',
                    type: 'text_line',
                },
            },
        },
        other: {
            title: 'Other',
            form: {
                other_text: {
                    label: 'Other Text',
                    type: 'text_line',
                },
            },
        },
    };
    const value = [{type: 'default'}];
    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            types={types}
            value={value}
        />
    );

    fieldBlocks.find('BlockCollection Block').at(0).simulate('click');
    fieldBlocks.find('BlockCollection').prop('onChange')([{type: 'other'}]);

    fieldBlocks.update();
    expect(fieldBlocks.find('BlockCollection Block').at(0).find('SingleSelect').prop('value')).toEqual('other');
});

test('Should set correct default values for multiple single_select in blocks', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                position_center: {
                    label: 'Position Center',
                    type: 'single_select',
                    options: {
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
                position_left: {
                    label: 'Position Left',
                    type: 'single_select',
                    options: {
                        default_value: {
                            name: 'default_value',
                            type: 'string',
                            value: 'left',
                        },
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
                position_right: {
                    label: 'Position Right',
                    type: 'single_select',
                    options: {
                        default_value: {
                            name: 'default_value',
                            type: 'string',
                            value: 'right',
                        },
                        values: {
                            name: 'values',
                            type: 'collection',
                            value: [
                                {
                                    name: 'left',
                                    title: 'Left',
                                },
                                {
                                    name: 'center',
                                    title: 'Center',
                                },
                                {
                                    name: 'right',
                                    title: 'Right',
                                },
                            ],
                        },
                    },
                },
            },
        },
    };

    formInspector.getSchemaEntryByPath.mockReturnValue({types});

    fieldRegistry.get.mockReturnValue(SingleSelect);

    const changeSpy = jest.fn();

    const fieldBlocks = mount(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="default"
            formInspector={formInspector}
            minOccurs={1}
            onChange={changeSpy}
            types={types}
            value={observable([{type: 'default'}])}
        />
    );

    fieldBlocks.find('Block').at(0).simulate('click');

    expect(changeSpy).toBeCalledWith(
        [
            {
                'position_left': 'left',
                'position_right': 'right',
                'type': 'default',
            },
        ]
    );
});

test('Throw error if no default type are passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    )).toThrow('The "block" field type needs a defaultType!');
});

test('Throw error if no types are passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if empty type array is passed', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));
    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            value={[]}
        />
    )).toThrow('The "block" field type needs at least one type to be configured!');
});

test('Throw error if passed settings_form_key schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                nothing: {
                    label: 'Nothing',
                    type: 'phone',
                },
            },
        },
    };

    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{settings_form_key: {name: 'settings_form_key', value: []}}}
            types={types}
            value={[]}
        />
    )).toThrow('The "block" field types only accepts strings as "settings_form_key" schema option!');
});

test('Throw error if passed add_button_text schema option is not a string', () => {
    const formInspector = new FormInspector(new ResourceFormStore(new ResourceStore('test'), 'test'));

    const types = {
        default: {
            title: 'Default',
            form: {
                nothing: {
                    label: 'Nothing',
                    type: 'phone',
                },
            },
        },
    };

    expect(() => shallow(
        <FieldBlocks
            {...fieldTypeDefaultProps}
            defaultType="editor"
            formInspector={formInspector}
            schemaOptions={{add_button_text: {name: 'add_button_text', title: ([]: any)}}}
            types={types}
            value={[]}
        />
    )).toThrow('The "block" field types only accepts strings as "add_button_text" schema option!');
});
