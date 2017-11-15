/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import pretty from 'pretty';
import React from 'react';
import MediaCard from '../MediaCard';

test('Render a simple MediaCard component', () => {
    expect(render(
        <MediaCard
            title="Test"
            meta="Test/Test"
            image="http://lorempixel.com/300/200"
        />
    )).toMatchSnapshot();
});

test('Render a MediaCard with download list', () => {
    const imageSizes = [
        {
            url: 'http://lorempixel.com/300/200',
            label: '300/200',
        },
        {
            url: 'http://lorempixel.com/600/300',
            label: '600/300',
        },
        {
            url: 'http://lorempixel.com/150/200',
            label: '150/200',
        },
    ];

    const masonry = mount(
        <MediaCard
            title="Test"
            meta="Test/Test"
            imageSizes={imageSizes}
            downloadCopyText="Copy URL"
            downloadUrl="http://lorempixel.com/300/200"
            downloadText="Direct download"
            image="http://lorempixel.com/300/200"
        />
    );

    masonry.instance().openDownloadList();
    expect(pretty(document.body.innerHTML)).toMatchSnapshot();
});

test('Clicking on an item should call the responsible handler on the MediaCard component', () => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    const mediaCard = mount(
        <MediaCard
            id={itemId}
            title="Test"
            meta="Test/Test"
            onClick={clickSpy}
            onSelectionChange={selectionSpy}
            image="http://lorempixel.com/300/200"
        />
    );

    mediaCard.find('MediaCard .media').simulate('click');
    expect(clickSpy).toHaveBeenCalledWith(itemId, true);

    mediaCard.find('MediaCard .description').simulate('click');
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
