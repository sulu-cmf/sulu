/* eslint-disable flowtype/require-valid-file-annotation */
import Requester from '../Requester';

test('Should execute GET request and return JSON', () => {
    const request = {
        json: jest.fn(),
    };
    request.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(request));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const requestPromise = Requester.get('/some-url').then((data) => {
        expect(data).toBe('test');
    });

    expect(window.fetch).toBeCalledWith('/some-url', {credentials: 'same-origin'});

    return requestPromise;
});

test('Should execute DELETE request and return JSON', () => {
    const request = {
        json: jest.fn(),
    };
    request.json.mockReturnValue('test');
    const promise = new Promise((resolve) => resolve(request));

    window.fetch = jest.fn();
    window.fetch.mockReturnValue(promise);

    const requestPromise = Requester.delete('/some-url').then((data) => {
        expect(data).toBe('test');
    });

    expect(window.fetch).toBeCalledWith('/some-url', {method: 'DELETE', credentials: 'same-origin'});

    return requestPromise;
});
