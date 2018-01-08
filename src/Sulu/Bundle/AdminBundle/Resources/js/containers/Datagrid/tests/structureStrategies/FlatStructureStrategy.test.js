// @flow
import {toJS} from 'mobx';
import FlatStructureStrategy from '../../structureStrategies/FlatStructureStrategy';

test('Should be empty after intialization', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    expect(toJS(flatStructureStrategy.data)).toEqual([]);
});

test('Should return the array on a getData call', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    flatStructureStrategy.data = [{id: 1}];
    expect(flatStructureStrategy.getData()).toBe(flatStructureStrategy.data);
});

test('Should be empty after clear was called', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    flatStructureStrategy.data = [{id: 1}];

    flatStructureStrategy.clear();
    expect(toJS(flatStructureStrategy.data)).toEqual([]);
});

test('Should not enhance the items', () => {
    const flatStructureStrategy = new FlatStructureStrategy();
    expect(flatStructureStrategy.enhanceItem({id: 1})).toEqual({id: 1});
});
