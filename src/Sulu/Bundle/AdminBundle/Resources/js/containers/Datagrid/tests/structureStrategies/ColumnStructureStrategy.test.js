// @flow
import ColumnStructureStrategy from '../../structureStrategies/ColumnStructureStrategy';

test('Should return the active items', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(0, []);
    columnStructureStrategy.rawData.set(1, []);

    expect(columnStructureStrategy.activeItems).toEqual([0, 1]);
});

test('Should return the data in a column format', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}]);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1}],
        [{id: 2}],
        [{id: 3}],
    ]);
});

test('Should return a new array for a given parent in getData and keep previous columns', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    const column1 = [{id: 1}];
    columnStructureStrategy.rawData.set(undefined, column1);
    const column2 = [{id: 2}];
    columnStructureStrategy.rawData.set(1, column2);
    const column3 = [{id: 3}];
    columnStructureStrategy.rawData.set(2, column3);

    expect(columnStructureStrategy.getData(1)).toEqual([]);
    expect(columnStructureStrategy.data).toEqual([
        [{id: 1}],
        [],
    ]);
});

test('Should return a item by id', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}, {id: 4}]);
    columnStructureStrategy.rawData.set(3, [{id: 5}, {id: 6}]);

    expect(columnStructureStrategy.findById(4)).toEqual({id: 4});
});

test('Should return undefined if item with given id does not exist', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1}]);
    columnStructureStrategy.rawData.set(1, [{id: 2}]);
    columnStructureStrategy.rawData.set(2, [{id: 3}, {id: 4}]);
    columnStructureStrategy.rawData.set(3, [{id: 5}, {id: 6}]);

    expect(columnStructureStrategy.findById(7)).toEqual(undefined);
});

test('Should be empty after clear was called', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData = new Map();
    columnStructureStrategy.rawData.set(0, []);
    columnStructureStrategy.rawData.set(1, []);

    expect(columnStructureStrategy.data).toHaveLength(2);
    columnStructureStrategy.clear();
    expect(columnStructureStrategy.data).toHaveLength(0);
});

test('Should not enhance the items', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    expect(columnStructureStrategy.enhanceItem({id: 1})).toEqual({id: 1});
});
