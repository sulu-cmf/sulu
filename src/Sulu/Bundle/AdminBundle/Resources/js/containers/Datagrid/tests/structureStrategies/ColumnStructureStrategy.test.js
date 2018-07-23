// @flow
import ColumnStructureStrategy from '../../structureStrategies/ColumnStructureStrategy';

test('Should return the active items', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(0, []);
    columnStructureStrategy.rawData.set(1, []);

    expect(columnStructureStrategy.activeItems).toEqual([undefined, 0, 1]);
});

test('Should return the data in a column format', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [{id: 2, hasChildren: true}]);
    columnStructureStrategy.rawData.set(2, [{id: 3, hasChildren: false}]);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1, hasChildren: true}],
        [{id: 2, hasChildren: true}],
        [{id: 3, hasChildren: false}],
    ]);
});

test('Should return the visible data', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [
        {id: 2, hasChildren: true},
        {id: 3, hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set(2, [{id: 4, hasChildren: true}]);

    expect(columnStructureStrategy.visibleItems).toEqual([
        {id: 1, hasChildren: true},
        {id: 2, hasChildren: true},
        {id: 3, hasChildren: true},
        {id: 4, hasChildren: true},
    ]);
});

test('Should return the column for a given parent in getData', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    const column1 = [{id: 1, hasChildren: true}];
    columnStructureStrategy.rawData.set(undefined, column1);
    const column2 = [{id: 2, hasChildren: true}];
    columnStructureStrategy.rawData.set(1, column2);
    const column3 = [{id: 3, hasChildren: true}];
    columnStructureStrategy.rawData.set(2, column3);

    expect(columnStructureStrategy.getData(1)).toEqual([{id: 2, hasChildren: true}]);
});

test('Should remove the columns after the activated item', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    const column1 = [{id: 1, hasChildren: true}];
    columnStructureStrategy.rawData.set(undefined, column1);
    const column2 = [{id: 2, hasChildren: true}];
    columnStructureStrategy.rawData.set(1, column2);
    const column3 = [{id: 3, hasChildren: true}];
    columnStructureStrategy.rawData.set(2, column3);
    const column4 = [{id: 4, hasChildren: true}];
    columnStructureStrategy.rawData.set(3, column4);

    columnStructureStrategy.activate(2);
    expect(columnStructureStrategy.data).toEqual([
        [{id: 1, hasChildren: true}],
        [{id: 2, hasChildren: true}],
        [],
    ]);
});

test('Should return a item by id', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [{id: 2, hasChildren: true}]);
    columnStructureStrategy.rawData.set(2, [
        {id: 3, hasChildren: true},
        {id: 4, hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set(3, [
        {id: 5, hasChildren: true},
        {id: 6, hasChildren: true},
    ]);

    expect(columnStructureStrategy.findById(4)).toEqual({id: 4, hasChildren: true});
});

test('Should return undefined if item with given id does not exist', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [{id: 2, hasChildren: true}]);
    columnStructureStrategy.rawData.set(2, [
        {id: 3, hasChildren: true},
        {id: 4, hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set(3, [
        {id: 5, hasChildren: true},
        {id: 6, hasChildren: true},
    ]);

    expect(columnStructureStrategy.findById(7)).toEqual(undefined);
});

test('Should be empty after clear was called', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData = new Map();
    columnStructureStrategy.rawData.set(0, []);
    columnStructureStrategy.rawData.set(1, []);

    expect(columnStructureStrategy.data).toHaveLength(2);
    columnStructureStrategy.clear();
    expect(columnStructureStrategy.data).toHaveLength(1);
    expect(columnStructureStrategy.getData(undefined)).toEqual([]);
});

test('Should not enhance the items', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    expect(columnStructureStrategy.enhanceItem({id: 1, hasChildren: true})).toEqual({id: 1, hasChildren: true});
});

test('Should remove an entry', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [
        {id: 2, hasChildren: true},
        {id: 4, hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set(2, [
        {id: 3, hasChildren: true},
        {id: 5, hasChildren: true},
    ]);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1, hasChildren: true}],
        [{id: 2, hasChildren: true}, {id: 4, hasChildren: true}],
        [{id: 3, hasChildren: true}, {id: 5, hasChildren: true}],
    ]);

    columnStructureStrategy.remove(3);
    columnStructureStrategy.remove(4);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1, hasChildren: true}],
        [{id: 2, hasChildren: true}],
        [{id: 5, hasChildren: true}],
    ]);
});

test('Should remove an entry with the following columns if the entry was active', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 10, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [
        {id: 2, hasChildren: true},
        {id: 4, hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set(4, [
        {id: 3, hasChildren: true},
        {id: 5, hasChildren: true},
    ]);

    columnStructureStrategy.remove(4);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 10, hasChildren: true}],
        [{id: 2, hasChildren: true}],
    ]);
});

test('Should remove an entry with the following columns if the entry was active and string ids are used', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 'z1', hasChildren: true}]);
    columnStructureStrategy.rawData.set('z1', [
        {id: 'y2', hasChildren: true},
        {id: 'w4', hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set('w4', [
        {id: 'x3', hasChildren: true},
        {id: 'v5', hasChildren: true},
    ]);

    columnStructureStrategy.remove('w4');

    expect(columnStructureStrategy.data).toEqual([
        [{id: 'z1', hasChildren: true}],
        [{id: 'y2', hasChildren: true}],
    ]);
});

test('Should remove the last entry of the last column and leave the last column if an item is left', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [{id: 2, hasChildren: true}]);
    columnStructureStrategy.rawData.set(2, [
        {id: 3, hasChildren: true},
        {id: 4, hasChildren: true},
    ]);
    columnStructureStrategy.rawData.set(4, []);

    columnStructureStrategy.remove(4);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1, hasChildren: true}],
        [{id: 2, hasChildren: true}],
        [{id: 3, hasChildren: true}],
    ]);
});

test('Should change hasChildren flag of parent if last item was deleted', () => {
    const columnStructureStrategy = new ColumnStructureStrategy();
    columnStructureStrategy.rawData.set(undefined, [{id: 1, hasChildren: true}]);
    columnStructureStrategy.rawData.set(1, [{id: 2, hasChildren: true}]);
    columnStructureStrategy.rawData.set(2, []);

    columnStructureStrategy.remove(2);

    expect(columnStructureStrategy.data).toEqual([
        [{id: 1, hasChildren: false}],
        [],
    ]);
});
