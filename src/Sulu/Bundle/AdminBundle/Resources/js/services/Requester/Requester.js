// @flow
import {isObservableArray} from 'mobx';
import RequestPromise from './RequestPromise';
import type {HandleResponseHook} from './types';

const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

function transformResponseObject(data: Object) {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        const value = data[key];

        if (value === null) {
            transformedData[key] = undefined;

            return transformedData;
        }

        if (Array.isArray(value) || isObservableArray(value)) {
            transformedData[key] = transformResponseArray(value);

            return transformedData;
        }

        if (value instanceof Object) {
            transformedData[key] = transformResponseObject(value);

            return transformedData;
        }

        transformedData[key] = value;

        return transformedData;
    }, {});
}

function transformResponseArray(data: Array<Object>) {
    return data.map((value) => {
        if (value instanceof Object) {
            return transformResponseObject(value);
        }

        return value;
    });
}

function transformRequestObject(data: Object): Object {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        const value = data[key];

        if (value === undefined || value === null) {
            transformedData[key] = null;

            return transformedData;
        }

        if (Array.isArray(value) || isObservableArray(value)) {
            transformedData[key] = transformRequestArray(value);

            return transformedData;
        }

        if (value instanceof Object) {
            transformedData[key] = transformRequestObject(value);

            return transformedData;
        }

        transformedData[key] = value;

        return transformedData;
    }, {});
}

function transformRequestArray(data) {
    return data.map((value) => {
        if (Array.isArray(value) || isObservableArray(value)) {
            return transformRequestArray(value);
        }

        if (value instanceof Object) {
            return transformRequestObject(value);
        }

        return value;
    });
}

function transformRequestData(data: Object | Array<Object>) {
    if (Array.isArray(data) || isObservableArray(data)) {
        return transformRequestArray(data);
    }

    return transformRequestObject(data);
}

function handleResponse(response: Response, options: ?Object): Response {
    for (const handleResponseHook of Requester.handleResponseHooks) {
        handleResponseHook(response, options);
    }

    return response;
}

function handleJsonResponse(response: Response, options: ?Object): Promise<Object | Array<Object>> {
    response = handleResponse(response, options);

    if (!response.ok) {
        return Promise.reject(response);
    }

    if (response.status === 204) {
        // Return empty object if status code says that there is no content
        return Promise.resolve({});
    }

    return response.json().then((data) => {
        if (Array.isArray(data) || isObservableArray(data)) {
            return transformResponseArray(data);
        }

        return transformResponseObject(data);
    });
}

function handleObjectResponse(response: Response, options: ?Object): Promise<Object> {
    return handleJsonResponse(response, options).then((response) => {
        if (Array.isArray(response) || isObservableArray(response)) {
            throw Error('Response was expected to be an object, but an array was given');
        }

        return response;
    });
}

function createAbortableFetchCall(input: RequestInfo, init?: RequestOptions): RequestPromise<*> {
    let promiseResolve, promiseReject;
    const requestPromise = new RequestPromise(function(resolve, reject) {
        promiseResolve = resolve;
        promiseReject = reject;
    });

    const abortController = new AbortController();
    requestPromise.setAbortController(abortController);

    fetch(input, {...defaultOptions, ...init, signal: abortController.signal})
        .then(promiseResolve)
        .catch(promiseReject);

    return requestPromise;
}

export default class Requester {
    static handleResponseHooks: Array<HandleResponseHook> = [];

    static fetch(input: RequestInfo, init?: RequestOptions): RequestPromise<Response> {
        return createAbortableFetchCall(input, init)
            .then((response) => handleResponse(response, init));
    }

    static get(url: string): RequestPromise<Object> {
        const options = {method: 'GET'};
        return createAbortableFetchCall(url, options)
            .then((response) => handleObjectResponse(response, options));
    }

    static post(url: string, data: ?Object): RequestPromise<Object> {
        const options = {
            ...defaultOptions,
            method: 'POST',
            body: data ? JSON.stringify(transformRequestData(data)) : undefined,
        };

        return createAbortableFetchCall(
            url,
            options
        ).then((response) => handleObjectResponse(response, options));
    }

    static put(url: string, data: Object): RequestPromise<Object> {
        const options = {
            ...defaultOptions,
            method: 'PUT',
            body: data ? JSON.stringify(transformRequestData(data)) : undefined,
        };

        return createAbortableFetchCall(
            url,
            options
        ).then((response) => handleObjectResponse(response, options));
    }

    static patch(url: string, data: Array<Object> | Object): RequestPromise<Array<Object> | Object> {
        const options = {method: 'PATCH', body: JSON.stringify(transformRequestData(data))};

        return createAbortableFetchCall(url, options)
            .then((response) => handleJsonResponse(response, options));
    }

    static delete(url: string): RequestPromise<Object> {
        const options = {method: 'DELETE'};

        return createAbortableFetchCall(url, options)
            .then((response) => handleObjectResponse(response, options));
    }
}
