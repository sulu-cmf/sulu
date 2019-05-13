// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import log from 'loglevel';
import Url from '../Url';

jest.mock('loglevel', () => ({
    warn: jest.fn(),
}));

test('Render the component as disabled', () => {
    expect(render(
        <Url disabled={true} onChange={jest.fn()} protocols={['http://', 'https://']} value={undefined} />
    )).toMatchSnapshot();
});

test('Render the component with an error', () => {
    expect(render(
        <Url
            defaultProtocol="http://"
            onChange={jest.fn()}
            protocols={['http://', 'https://']}
            valid={false}
            value={undefined}
        />
    )).toMatchSnapshot();
});

test('Set the correct values for protocol and path when initializing', () => {
    const url = shallow(<Url onChange={jest.fn()} value="https://www.sulu.io" />);

    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.io');
});

test('Set the correct values for protocol and path when updating', () => {
    const url = shallow(<Url onChange={jest.fn()} value="https://www.sulu.io" />);

    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.io');

    url.setProps({value: 'http://sulu.at'});

    expect(url.find('SingleSelect').prop('value')).toEqual('http://');
    expect(url.find('input').prop('value')).toEqual('sulu.at');
});

test('Should log a warning if a not available protocol has been given', () => {
    const url = shallow(<Url onChange={jest.fn()} protocols={['http://']} value="https://www.sulu.io" />);

    expect(url.find('input').prop('value')).toEqual('https://www.sulu.io');
    expect(log.warn).toBeCalled();
});

test('Show error when invalid URL was passed via updated prop', () => {
    const url = shallow(<Url onChange={jest.fn()} value={undefined} />);
    expect(url.find('.error')).toHaveLength(0);

    url.setProps({value: 'http://su lu.at'});

    expect(url.find('.error')).toHaveLength(1);
});

test('Should not reset value of protocol select when undefined value is passed', () => {
    const url = shallow(<Url onChange={jest.fn()} value="https://" />);
    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('');

    url.setProps({value: undefined});
    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('');
});

test('Remove error when valid URL was passed via updated prop', () => {
    const url = shallow(<Url onChange={jest.fn()} value="http://su lu.at" />);
    expect(url.find('.error')).toHaveLength(1);

    url.setProps({value: 'http://sulu.at'});

    expect(url.find('.error')).toHaveLength(0);
});

test('Remove error when valid URL was changed using the text field', () => {
    const url = shallow(<Url onChange={jest.fn()} value="http://su lu.at" />);
    expect(url.find('.error')).toHaveLength(1);

    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });
    url.find('input').prop('onBlur')();

    expect(url.find('.error')).toHaveLength(0);
});

test('Call onChange callback with the first protocol if none was selected', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith('http://sulu.at');
});

test('Call onChange callback when protocol was changed', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value="https://www.sulu.io" />);
    url.find('SingleSelect').prop('onChange')('http://');

    expect(changeSpy).toBeCalledWith('http://www.sulu.io');
});

test('Call onChange callback when path was changed', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith('https://sulu.at');
});

test('Call onChange callback when path was changed but not blurred', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.at',
        },
    });

    expect(changeSpy).toBeCalledWith('https://sulu.at');
});

test('Call onChange callback with undefined when path was changed to invalid url but not blurred', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'sulu.a',
        },
    });

    expect(changeSpy).toBeCalledWith(undefined);
});

test('Call onChange callback with undefined if URL is not valid but leave the current value', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value="https://www.sulu.io" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'su lu.at',
        },
    });
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith(undefined);
    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('su lu.at');
    expect(url.find('.error')).toHaveLength(1);
});

test('Call onChange callback with correct mail address', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['mailto:']} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'test@example.com',
        },
    });
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith('mailto:test@example.com');
    expect(url.find('SingleSelect').prop('value')).toEqual('mailto:');
    expect(url.find('input').prop('value')).toEqual('test@example.com');
    expect(url.find('.error')).toHaveLength(0);
});

test('Call onChange callback with undefined if incorrect mail address is entered', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} protocols={['mailto:']} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'example.com',
        },
    });
    url.find('input').prop('onBlur')();

    expect(changeSpy).toBeCalledWith(undefined);
    expect(url.find('SingleSelect').prop('value')).toEqual('mailto:');
    expect(url.find('input').prop('value')).toEqual('example.com');
    expect(url.find('.error')).toHaveLength(1);
});

test('Should remove the protocol from path and set it on the protocol select', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value={undefined} />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'http://www.sulu.at',
        },
    });

    expect(url.find('SingleSelect').prop('value')).toEqual('http://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.at');
});

test('Should remove the protocol from path and set it on the protocol select if protocol is already selected', () => {
    const changeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} value="http://www.sulu.at" />);
    url.find('input').prop('onChange')({
        currentTarget: {
            value: 'https://www.sulu.io',
        },
    });

    expect(url.find('SingleSelect').prop('value')).toEqual('https://');
    expect(url.find('input').prop('value')).toEqual('www.sulu.io');
});

test('Call onBlur callback when protocol was changed', () => {
    const blurSpy = jest.fn();
    const url = shallow(<Url onBlur={blurSpy} onChange={jest.fn()} value="https://www.sulu.io" />);
    url.find('SingleSelect').prop('onChange')('http://');

    expect(blurSpy).toBeCalledWith();
});

test('Call onBlur callback when path was changed', () => {
    const blurSpy = jest.fn();
    const url = shallow(<Url onBlur={blurSpy} onChange={jest.fn()} value="https://www.sulu.io" />);
    url.find('input').prop('onBlur')();

    expect(blurSpy).toBeCalledWith();
});

test('Should call onProtocolChange with default protocol', () => {
    const protocolChangeSpy = jest.fn();
    shallow(
        <Url defaultProtocol="http://" onChange={jest.fn()} onProtocolChange={protocolChangeSpy} value={undefined} />
    );

    expect(protocolChangeSpy).toBeCalledWith('http://');
});

test('Should call onProtocolChange with initial value', () => {
    const protocolChangeSpy = jest.fn();
    shallow(<Url onChange={jest.fn()} onProtocolChange={protocolChangeSpy} value="http://www.google.at" />);

    expect(protocolChangeSpy).toBeCalledWith('http://');
});

test('Should call onProtocolChange when protocol is changed', () => {
    const changeSpy = jest.fn();
    const protocolChangeSpy = jest.fn();
    const url = shallow(<Url onChange={changeSpy} onProtocolChange={protocolChangeSpy} value={undefined} />);

    url.find('SingleSelect').prop('onChange')('https://');

    expect(protocolChangeSpy).toHaveBeenLastCalledWith('https://');
});
