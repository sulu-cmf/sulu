/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {render} from 'enzyme';
import ViewRenderer from '../ViewRenderer';
import viewRegistry from '../registries/ViewRegistry';

jest.mock('../registries/ViewRegistry', () => ({
    get: jest.fn(),
}));

test('Render view returned from ViewRegistry', () => {
    viewRegistry.get.mockReturnValue(() => (<h1>Test</h1>));
    const view = render(<ViewRenderer name="test" />);
    expect(view).toMatchSnapshot();
    expect(viewRegistry.get).toBeCalledWith('test');
});

test('Render view returned from ViewRegistry with passed router', () => {
    const router = {
        attributes: {
            value: 'Test attribute',
        },
    };

    viewRegistry.get.mockReturnValue((props) => (<h1>{props.router.attributes.value}</h1>));
    const view = render(<ViewRenderer name="test" router={router} />);
    expect(view).toMatchSnapshot();
    expect(viewRegistry.get).toBeCalledWith('test');
});

test('Render view should throw if view does not exist', () => {
    viewRegistry.get.mockReturnValue(undefined);
    expect(() => render(<ViewRenderer name="not_existing" />)).toThrow(/not_existing/);
});
