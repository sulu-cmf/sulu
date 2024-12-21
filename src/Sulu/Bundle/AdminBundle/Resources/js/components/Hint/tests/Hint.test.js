// @flow
import React from 'react';
import {render} from '@testing-library/react';
import Hint from '../Hint';

test('Render PermissionHint', () => {
    const {container} = render(<Hint icon="su-lock" title="Hint Text" />);
    expect(container).toMatchSnapshot();
});
