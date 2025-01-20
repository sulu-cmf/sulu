// @flow

import React from 'react';
import {render, fireEvent, screen} from '@testing-library/react';
import '@testing-library/jest-dom/extend-expect';
import symfonyRouting from 'fos-jsrouting/router';
import {translate} from '../../../utils';
import AiApplication from '../AiApplication';

jest.mock('fos-jsrouting/router');
jest.mock('../../../utils');
jest.mock('../../../containers');
jest.mock('../../TextEditor/adapters/CKEditor5');
jest.mock('../../WritingAssistant');
jest.mock('../../Translator');
jest.mock('../ActionOverlay');

describe('AiApplication', () => {
    let props;

    beforeEach(() => {
        props = {
            feedback: {
                enabled: true,
                formKey: 'formKey',
                route: 'feedbackRoute',
            },
            translation: {
                enabled: true,
                route: 'translationRoute',
                sourceLanguages: [{label: 'English', locale: 'en'}],
                targetLanguages: [{label: 'French', locale: 'fr'}],
            },
            writingAssistant: {
                enabled: true,
                experts: {},
                route: 'writingAssistantRoute',
            },
        };

        symfonyRouting.generate.mockImplementation((route, params: {[string]: string}) => {
            // $FlowFixMe
            return `${route}?${Object.entries(params).map(([key, value: string]) => `${key}=${value}`).join('&')}`;
        });

        // $FlowFixMe
        translate.mockImplementation((key) => key);
    });

    test('renders without crashing initially', () => {
        const {container} = render(<AiApplication {...props} />);

        expect(container).toBeEmptyDOMElement();
    });

    test('renders FeatureBadge when hasFocus is true', () => {
        render(<AiApplication {...props} />);

        // Create a mock HTMLElement
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        // Create a mock event object
        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });

        // Simulate the sulu.focus event to set hasFocus to true
        fireEvent(document, event);

        // Now, hasFocus should be true and FeatureBadge should be rendered
        expect(screen.getByTitle('sulu_admin.translator_title')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant_title')).toBeInTheDocument();
    });

    test('handles scroll and resize events', () => {
        render(<AiApplication {...props} />);

        // Create a mock HTMLElement
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const event = new Event('sulu.focus');
        Object.defineProperty(event, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(event, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, event);

        // Simulate scroll event
        fireEvent.scroll(window);

        // Check if getBoundingClientRect was called
        // $FlowFixMe
        expect(mockElement.parentElement.getBoundingClientRect).toHaveBeenCalledTimes(2);

        // Simulate resize event
        fireEvent.resize(window);

        // Check if getBoundingClientRect was called again
        // $FlowFixMe
        expect(mockElement.parentElement.getBoundingClientRect).toHaveBeenCalledTimes(3);
    });

    test('handles global click event', () => {
        render(<AiApplication {...props} />);

        // Create a mock HTMLElement
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Create a mock click event
        const clickEvent = new Event('click');
        Object.defineProperty(clickEvent, 'target', {
            // $FlowFixMe
            value: {
                matches: jest.fn().mockReturnValue(false),
                closest: jest.fn().mockReturnValue(null),
            },
        });

        // Simulate the global click event
        fireEvent(document, clickEvent);

        // Check if hasFocus is set to false
        expect(screen.getByTitle('sulu_admin.translator_title')).toBeInTheDocument();
        expect(screen.getByTitle('sulu_admin.writing_assistant_title')).toBeInTheDocument();
    });

    test('handles writing assistant close', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn().mockReturnValue('text'),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the writing assistant close action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {getValue: jest.fn().mockReturnValue('text')};
        instance.handleWritingAssistantClose();

        // Check the state changes
        expect(instance.selectedText).toBe('text');
        expect(instance.writingAssistantOpen).toBe(false);
        expect(instance.hasFocus).toBe(false);
    });

    test('handles writing assistant confirm', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the writing assistant confirm action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {setValue: jest.fn()};
        // $FlowFixMe
        instance.selectedElement = {
            focus: jest.fn(),
            selectionStart: 0,
            selectionEnd: 0,
            value: {length: 0},
        };
        instance.handleWritingAssistantConfirm('optimizedText');

        // Check the state changes
        expect(instance.selectedComponent.setValue).toHaveBeenCalledWith('optimizedText');
        expect(instance.writingAssistantOpen).toBe(false);
        expect(instance.hasFocus).toBe(true);
    });

    test('handles translate close', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn().mockReturnValue('text'),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the translate close action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {getValue: jest.fn().mockReturnValue('text')};
        instance.handleTranslateClose();

        // Check the state changes
        expect(instance.selectedText).toBe('text');
        expect(instance.translateOpen).toBe(false);
        expect(instance.hasFocus).toBe(false);
    });

    test('handles translate confirm', () => {
        render(<AiApplication {...props} />);

        // Simulate the sulu.focus event to set hasFocus to true and select the element
        const mockElement = document.createElement('div');
        Object.defineProperty(mockElement, 'parentElement', {
            // $FlowFixMe
            value: {
                getBoundingClientRect: jest.fn().mockReturnValue({
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 0,
                    width: 0,
                    height: 0,
                }),
            },
        });

        const focusEvent = new Event('sulu.focus');
        Object.defineProperty(focusEvent, 'target', {
            value: mockElement,
        });
        // $FlowFixMe
        Object.defineProperty(focusEvent, 'detail', {
            value: {
                formInspector: {locale: {get: () => 'en'}},
                getValue: jest.fn(),
                schemaPath: 'schemaPath',
                schemaType: 'text_line',
                setValue: jest.fn(),
            },
        });
        fireEvent(document, focusEvent);

        // Simulate the translate confirm action
        const instance = new AiApplication(props);
        // $FlowFixMe
        instance.selectedComponent = {setValue: jest.fn()};
        // $FlowFixMe
        instance.selectedElement = {
            focus: jest.fn(),
            selectionStart: 0,
            selectionEnd: 0,
            value: {length: 0},
        };
        instance.handleTranslateConfirm('translatedText');

        // Check the state changes
        expect(instance.selectedComponent.setValue).toHaveBeenCalledWith('translatedText');
        expect(instance.translateOpen).toBe(false);
        expect(instance.hasFocus).toBe(true);
    });
});
