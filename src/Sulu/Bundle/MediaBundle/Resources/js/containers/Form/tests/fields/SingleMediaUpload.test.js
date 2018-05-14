// @flow
import React from 'react';
import {shallow} from 'enzyme';
import SingleMediaUpload from '../../fields/SingleMediaUpload';
import SingleMediaUploadComponent from '../../../SingleMediaUpload';
import MediaUploadStore from '../../../../stores/MediaUploadStore';

test('Pass correct props', () => {
    const schemaOptions = {
        upload_text: {
            infotext: 'Drag and drop',
        },
    };

    const singleMediaUpload = shallow(
        <SingleMediaUpload onChange={jest.fn()} schemaOptions={schemaOptions} value={undefined} />
    );

    expect(singleMediaUpload.prop('uploadText')).toEqual('Drag and drop');
});

test('Call onChange and onFinish when upload has completed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const media = {name: 'test.jpg'};

    const singleMediaUpload = shallow(
        <SingleMediaUpload onChange={changeSpy} onFinish={finishSpy} value={undefined} />
    );

    singleMediaUpload.find(SingleMediaUploadComponent).simulate('uploadComplete', media);

    expect(changeSpy).toBeCalledWith(media);
    expect(finishSpy).toBeCalledWith();
});

test('Create a MediaUploadStore when constructed', () => {
    const singleMediaUpload = shallow(
        <SingleMediaUpload onChange={jest.fn()} value={undefined} />
    );

    expect(singleMediaUpload.instance().mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.instance().mediaUploadStore.resourceStore.resourceKey).toEqual('media');
});
