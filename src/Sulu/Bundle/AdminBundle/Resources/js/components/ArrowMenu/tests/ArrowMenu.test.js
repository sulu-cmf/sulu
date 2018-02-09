// @flow
import React from 'react';
import {mount} from 'enzyme';
import pretty from 'pretty';
import ArrowMenu from '../ArrowMenu';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Render ArrowMenu closed', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = false;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                title="Webspaces"
                value={value1}
                onChange={handleChangeSection1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-checkmark"
                title="Columns"
                value={value2}
                onChange={handleChangeSection2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
        </ArrowMenu>
    );

    expect(arrowMenu.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('Render ArrowMenu open', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = true;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                title="Webspaces"
                value={value1}
                onChange={handleChangeSection1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-checkmark"
                title="Columns"
                value={value2}
                onChange={handleChangeSection2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
        </ArrowMenu>
    );

    expect(arrowMenu.render()).toMatchSnapshot();
    expect(pretty(document.body ? document.body.innerHTML : '')).toMatchSnapshot();
});

test('Events should be called correctly', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = true;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                title="Webspaces"
                value={value1}
                onChange={handleChangeSection1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="check"
                title="Columns"
                value={value2}
                onChange={handleChangeSection2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
        </ArrowMenu>
    );

    arrowMenu.find('SingleItemSection').at(0).find('Item').at(1).simulate('click');
    expect(handleChangeSection1).toBeCalledWith('sulu_blog');

    arrowMenu.find('SingleItemSection').at(1).find('Item').at(0).simulate('click');
    expect(handleChangeSection2).toBeCalledWith('title');

    arrowMenu.find('Backdrop').simulate('click');
    expect(handleClose).toBeCalled();
});
