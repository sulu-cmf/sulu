// @flow
import React from 'react';
import {render} from 'enzyme';
import Section from '../Section';

test('Render section with given colSpan', () => {
    expect(render(
        <Section label="Test">
            <p>Test</p>
        </Section>
    )).toMatchSnapshot();
});

test('Render section without label', () => {
    expect(render(
        <Section colSpan={8}>
            <div>Test</div>
        </Section>
    )).toMatchSnapshot();
});

test('Render section without label but with divider', () => {
    expect(render(
        <Section>
            <p>Test</p>
        </Section>
    )).toMatchSnapshot();
});
