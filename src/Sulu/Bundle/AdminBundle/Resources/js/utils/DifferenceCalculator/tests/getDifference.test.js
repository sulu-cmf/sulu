// @flow
import getDifference from '../getDifference';

test('Should return target object if source is null', () => {
    const target = {a: 1, b: 2};
    const source = null;

    const result = getDifference(target, source);

    expect(result).toEqual(target);
});

test('Should return target object if target and source are different data structures', () => {
    const target = {a: 1, b: 2};
    const source = [1, 2, 3];

    const result = getDifference(target, source);

    expect(result).toEqual(target);
});

test('Should return empty object if target and source are equal', () => {
    const target = {a: 1, b: 2};
    const source = {a: 1, b: 2};

    const result = getDifference(target, source);

    expect(result).toEqual({});
});

test('Should return differences between target and source objects', () => {
    const target = {a: 1, b: 2, c: {x: 3, y: 4}};
    const source = {a: 1, b: 3, c: {x: 3, y: 5}};

    const result = getDifference(target, source);

    expect(result).toEqual({b: 2, c: {x: 3, y: 4}});
});

test('Should handle arrays correctly', () => {
    const target = {a: [1, 2, 3], b: {c: [4, 5]}};
    const source = {a: [1, 2, 4], b: {c: [4, 6]}};

    const result = getDifference(target, source);

    expect(result).toEqual({a: [1, 2, 3], b: {c: [4, 5]}});
});

test('Should handle null values in objects', () => {
    const target = {a: 1, b: null, c: 3};
    const source = {a: 1, b: 2, c: null};

    const result = getDifference(target, source);

    expect(result).toEqual({b: null, c: 3});
});

test('Should handle undefined values and missing properties', () => {
    const target = {a: 1, b: undefined, d: 4};
    const source = {a: 1, b: 2, c: 3};

    const result = getDifference(target, source);

    expect(result).toEqual({b: null, c: null, d: 4});
});

test('Should return empty object when comparing empty objects', () => {
    const target = {};
    const source = {};

    const result = getDifference(target, source);

    expect(result).toEqual({});
});

test('Should handle objects with different number of properties', () => {
    const target = {a: 1, b: 2, c: 3};
    const source = {a: 1};

    const result = getDifference(target, source);

    expect(result).toEqual({b: 2, c: 3});
});
