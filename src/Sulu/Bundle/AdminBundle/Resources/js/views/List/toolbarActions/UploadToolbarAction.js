// @flow
import React from 'react';
import log from 'loglevel';
import {action, computed, isArrayLike, observable} from 'mobx';
import Dropzone from 'react-dropzone';
import symfonyRouting from 'fos-jsrouting/router';
import {translate, transformBytesToReadableString} from '../../../utils';
import ResourceStore from '../../../stores/ResourceStore';
import Router from '../../../services/Router';
import List from '../../../views/List/List';
import ListStore from '../../../containers/List/stores/ListStore';
import {Requester} from '../../../services';
import AbstractListToolbarAction from './AbstractListToolbarAction';
import type {ElementRef} from 'react';

const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
};

export default class UploadToolbarAction extends AbstractListToolbarAction {
    @observable dropzoneRef: ?ElementRef<typeof Dropzone>;
    @observable errors: string[] = [];

    constructor(
        listStore: ListStore,
        list: List,
        router: Router,
        locales?: Array<string>,
        resourceStore?: ResourceStore,
        options: {[key: string]: mixed}
    ) {
        if (options.routeName) {
            // @deprecated
            log.warn(
                'The "routeName" option is deprecated and will be removed. ' +
                'Use the "route_name" option instead.'
            );

            if (!options.route_name) {
                options.route_name = options.routeName;
            }
        }

        if (options.minSize) {
            // @deprecated
            log.warn(
                'The "minSize" option is deprecated and will be removed. ' +
                'Use the "min_size" option instead.'
            );

            if (!options.min_size) {
                options.min_size = options.minSize;
            }
        }

        if (options.maxSize) {
            // @deprecated
            log.warn(
                'The "maxSize" option is deprecated and will be removed. ' +
                'Use the "max_size" option instead.'
            );

            if (!options.max_size) {
                options.max_size = options.maxSize;
            }
        }

        if (options.requestPropertyName) {
            // @deprecated
            log.warn(
                'The "requestPropertyName" option is deprecated and will be removed. ' +
                'Use the "request_property_name" option instead.'
            );

            if (!options.request_property_name) {
                options.request_property_name = options.requestPropertyName;
            }
        }

        if (options.requestParameters) {
            // @deprecated
            log.warn(
                'The "requestParameters" option is deprecated and will be removed. ' +
                'Use the "request_parameters" option instead.'
            );

            if (!options.request_parameters) {
                options.request_parameters = options.requestParameters;
            }
        }

        if (options.routerAttributesToRequest) {
            // @deprecated
            log.warn(
                'The "routerAttributesToRequest" option is deprecated and will be removed. ' +
                'Use the "router_attributes_to_request" option instead.'
            );

            if (!options.router_attributes_to_request) {
                options.router_attributes_to_request = options.routerAttributesToRequest;
            }
        }

        if (options.errorCodeMapping) {
            // @deprecated
            log.warn(
                'The "errorCodeMapping" option is deprecated and will be removed. ' +
                'The API should return a specific error message in the "detail" property of the response instead.'
            );
        }

        super(listStore, list, router, locales, resourceStore, options);
    }

    @action setDropzoneRef = (ref: ?ElementRef<typeof Dropzone>) => {
        this.dropzoneRef = ref;
    };

    @action handleClick = () => {
        const {dropzoneRef} = this;

        if (dropzoneRef) {
            dropzoneRef.open();
            this.removeErrors();
        }
    };

    removeErrors = () => {
        for (const error of this.errors) {
            this.removeError(error);
        }
    };

    @action removeError = (errorToRemove: string) => {
        this.errors = this.errors.filter((existingError) => errorToRemove !== existingError);
        this.list.errors = this.list.errors.filter((existingError) => errorToRemove !== existingError);
    };

    @action addError = (error: string) => {
        this.removeError(error);

        this.errors = [...this.errors, error];
        this.list.errors = [...this.list.errors, error];
    };

    handleError = (fileRejections: any[]) => {
        for (const fileRejection of fileRejections) {
            for (const {code} of fileRejection.errors) {
                let error;
                switch (code) {
                    case 'file-invalid-type':
                        error = translate('sulu_admin.dropzone_error_file-invalid-type', {
                            fileName: fileRejection.file.name,
                            allowedTypes: this.accept ? Object.keys(this.accept).join(', ') : undefined,
                        });
                        break;
                    case 'file-too-large':
                        error = translate('sulu_admin.dropzone_error_file-too-large', {
                            fileName: fileRejection.file.name,
                            maxSize: this.maxSize ? transformBytesToReadableString(this.maxSize) : undefined,
                        });
                        break;
                    case 'file-too-small':
                        error = translate('sulu_admin.dropzone_error_file-too-small', {
                            fileName: fileRejection.file.name,
                            minSize: this.minSize ? transformBytesToReadableString(this.minSize) : undefined,
                        });
                        break;
                    case 'too-many-files':
                        error = translate('sulu_admin.dropzone_error_too-many-files', {
                            fileName: fileRejection.file.name,
                            maxFiles: this.maxFiles,
                        });
                        break;
                    default:
                        error = translate('sulu_admin.unexpected_upload_error', {
                            fileName: fileRejection.file.name,
                        });
                }

                this.addError(error);
            }
        }
    };

    @action handleConfirm = (files: File[]) => {
        const {multiple, requestPropertyName} = this;
        const formData = new FormData();

        for (const file of files) {
            if (!multiple) {
                formData.append(requestPropertyName, file);

                break;
            }

            formData.append(requestPropertyName + '[]', file);
        }

        Requester.fetch(this.url, {...defaultOptions, method: 'POST', body: formData}).then((response) => {
            if (!response.ok) {
                const translatedErrorMessage = translate(
                    this.errorCodeMapping[response.status] || 'sulu_admin.unexpected_upload_error',
                    {statusText: response.statusText}
                );

                response.json().then((error) => {
                    this.addError(error.detail || error.title || translatedErrorMessage);
                }).catch(() => {
                    this.addError(translatedErrorMessage);
                });

                return;
            }

            this.listStore.reload();
        });
    };

    @computed get label(): string {
        const {label = 'sulu_admin.upload'} = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string!');
        }

        return translate(label);
    }

    @computed get icon(): string {
        const {icon = 'su-upload'} = this.options;

        if (typeof icon !== 'string') {
            throw new Error('The "icon" option must be a string!');
        }

        return icon;
    }

    @computed get url(): string {
        const {route_name: routeName} = this.options;

        if (typeof routeName !== 'string') {
            throw new Error('The "route_name" option must be a string!');
        }

        return symfonyRouting.generate(routeName, this.requestParameters);
    }

    @computed get errorCodeMapping(): $ReadOnly<Object> {
        const {errorCodeMapping = {}} = this.options;

        if (typeof errorCodeMapping !== 'object') {
            throw new Error('The "errorCodeMapping" option must be an object!');
        }

        return errorCodeMapping;
    }

    @computed get requestParameters(): $ReadOnly<Object> {
        const {
            options: {
                request_parameters: attributesToRequest = {},
                router_attributes_to_request: routerAttributesToRequest = {},
            },
            router: {
                attributes: routerAttributes,
            },
        } = this;

        if (!attributesToRequest || typeof attributesToRequest !== 'object') {
            throw new Error('The "request_parameters" option must be an object!');
        }

        if (!routerAttributesToRequest || typeof routerAttributesToRequest !== 'object') {
            throw new Error('The "router_attributes_to_request" option must be an object!');
        }

        const requestParameters = {};
        Object.keys(routerAttributesToRequest)
            .forEach((routerAttributeKey) => {
                const requestAttributeKey = routerAttributesToRequest[routerAttributeKey];

                if (typeof requestAttributeKey !== 'string') {
                    throw new Error('The "routerAttributesToRequest" option must contain strings!');
                }

                const attributeName = isNaN(routerAttributeKey)
                    ? routerAttributeKey
                    : requestAttributeKey;

                requestParameters[requestAttributeKey] = routerAttributes[attributeName];
            });

        return {...requestParameters, ...attributesToRequest};
    }

    @computed get accept(): ?{[key: string]: string[]} {
        const {accept} = this.options;

        if (accept === undefined || accept === null) {
            return undefined;
        }

        if (!isArrayLike(accept)) {
            throw new Error('The "accept" option must be an array!');
        }

        if (accept.length === 0) {
            return undefined;
        }

        const dropzoneObjectOption = {};
        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
        accept.forEach((type) => {
            dropzoneObjectOption[type] = [];
        });

        return dropzoneObjectOption;
    }

    @computed get minSize(): ?number {
        const {min_size: minSize} = this.options;

        if (minSize === undefined || minSize === null) {
            return undefined;
        }

        if (typeof minSize !== 'number') {
            throw new Error('The "min_size" option must be a number!');
        }

        return minSize;
    }

    @computed get maxSize(): ?number {
        const {max_size: maxSize} = this.options;

        if (maxSize === undefined || maxSize === null) {
            return undefined;
        }

        if (typeof maxSize !== 'number') {
            throw new Error('The "max_size" option must be a number!');
        }

        return maxSize;
    }

    @computed get multiple(): boolean {
        const {multiple = false} = this.options;

        if (typeof multiple !== 'boolean') {
            throw new Error('The "multiple" option must be a boolean!');
        }

        return multiple;
    }

    @computed get maxFiles(): ?number {
        return this.multiple ? undefined : 1;
    }

    @computed get requestPropertyName(): string {
        const {request_property_name: requestPropertyName} = this.options;

        if (!requestPropertyName) {
            return this.multiple ? 'files' : 'file';
        }

        if (typeof requestPropertyName !== 'string') {
            throw new Error('The "request_property_name" option must be a string!');
        }

        return requestPropertyName;
    }

    getToolbarItemConfig() {
        return {
            type: 'button',
            label: this.label,
            icon: this.icon,
            onClick: this.handleClick,
        };
    }

    getNode() {
        return (
            <Dropzone
                accept={this.accept}
                key="sulu_admin.upload"
                maxFiles={this.maxFiles}
                maxSize={this.maxSize}
                minSize={this.minSize}
                multiple={this.multiple}
                noClick={true}
                noDrag={true}
                noKeyboard={true}
                onDropAccepted={this.handleConfirm}
                onDropRejected={this.handleError}
                ref={this.setDropzoneRef}
            >
                {({getRootProps, getInputProps}) => {
                    return (
                        <div {...getRootProps()}>
                            <input {...getInputProps()} />
                        </div>
                    );
                }}
            </Dropzone>
        );
    }
}
