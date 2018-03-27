// @flow
import React from 'react';
import {render} from 'enzyme';
import Column from '../Column';

test('Should render column with toolbar', () => {
    const buttonsConfig = [
        {
            icon: 'fa-heart',
            onClick: () => {},
        },
        {
            icon: 'fa-pencil',
            onClick: () => {},
        },
    ];

    expect(render(<Column index={0} buttons={buttonsConfig} />)).toMatchSnapshot();
});
