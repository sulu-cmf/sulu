// @flow
import {mount, render, shallow} from 'enzyme';
import React from 'react';
import SingleMediaDropzone from '../SingleMediaDropzone';

test('Render a SingleMediaDropzone', () => {
    expect(render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the default empty icon', () => {
    expect(render(<SingleMediaDropzone image={undefined} onDrop={jest.fn()} />)).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the passed empty icon', () => {
    expect(render(<SingleMediaDropzone emptyIcon="su-user" image={undefined} onDrop={jest.fn()} />)).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with an error text', () => {
    expect(render(
        <SingleMediaDropzone
            emptyIcon="su-user"
            errorText="some-custom-error-message"
            image={undefined}
            onDrop={jest.fn()}
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with a loader if image has not been loaded yet', () => {
    const singleMediaDropzone = mount(<SingleMediaDropzone emptyIcon="su-user" image="test.jpg" onDrop={jest.fn()} />);
    expect(singleMediaDropzone.render()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone without a loader if image has been loaded after an error occured before', () => {
    const singleMediaDropzone = mount(<SingleMediaDropzone emptyIcon="su-user" image="test.jpg" onDrop={jest.fn()} />);
    singleMediaDropzone.instance().imageError = true;

    singleMediaDropzone.instance().image.onload();

    expect(singleMediaDropzone.render()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone without a loader if image has been loaded yet', () => {
    const singleMediaDropzone = mount(<SingleMediaDropzone emptyIcon="su-user" image="test.jpg" onDrop={jest.fn()} />);

    singleMediaDropzone.instance().image.onload();

    expect(singleMediaDropzone.render()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with a MimeTypeIndicator if an error appeared during image loading', () => {
    const singleMediaDropzone = mount(
        <SingleMediaDropzone emptyIcon="su-user" image="test.jpg" mimeType="video/x-m4v" onDrop={jest.fn()} />
    );

    singleMediaDropzone.instance().image.onerror();
    singleMediaDropzone.update();

    expect(singleMediaDropzone.render()).toMatchSnapshot();
});

test('Render a SingleMediaDropzone in disabled state', () => {
    expect(render(
        <SingleMediaDropzone
            disabled={true}
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaDropzone with the round skin', () => {
    expect(render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            skin="round"
            uploading={false}
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaDropzone while uploading', () => {
    expect(render(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={50}
            uploading={true}
        />
    )).toMatchSnapshot();
});

test('Render img tag with key to avoid keeping old image on new upload', () => {
    const singleMediaDropzone = mount(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    expect(singleMediaDropzone.find('img').key()).not.toBe(null);
});

test('Component pass correct props to Dropzone component', () => {
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            accept="application/json"
            disabled={true}
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            uploading={false}
        />
    );

    expect(singleMediaDropzone.find('Dropzone').props()).toEqual(expect.objectContaining({
        accept: {'application/json': []},
        disabled: true,
        noClick: false,
        multiple: false,
    }));
});

test('Dragging a file over the area will show the upload indicator', () => {
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    singleMediaDropzone.instance().handleDragEnter();
    expect(singleMediaDropzone.instance().uploadIndicatorVisibility).toBe(true);
});

test('Dragging a file outside of the area will hide the upload indicator', () => {
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={jest.fn()}
            progress={0}
            uploading={false}
        />
    );

    singleMediaDropzone.instance().handleDragLeave();
    expect(singleMediaDropzone.instance().uploadIndicatorVisibility).toBe(false);
});

test('Dropping a file on the area will hide the upload indicator and call the "onDrop" handler', () => {
    const dropSpy = jest.fn();
    const testFileData = {name: 'test-file'};
    const singleMediaDropzone = shallow(
        <SingleMediaDropzone
            image="http://lorempixel.com/400/400"
            onDrop={dropSpy}
            progress={0}
            uploading={false}
        />
    );

    singleMediaDropzone.instance().handleDrop([testFileData]);
    expect(singleMediaDropzone.instance().uploadIndicatorVisibility).toBe(false);
    expect(dropSpy).toBeCalledWith(testFileData);
});
