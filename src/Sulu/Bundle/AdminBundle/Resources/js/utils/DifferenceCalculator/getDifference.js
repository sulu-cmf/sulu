// @flow
import equals from 'fast-deep-equal';

export default function getDifference(
    target: any,
    source: any
): { [key: string]: any } {
    // if target and source are not the same data structure
    if (typeof target !== typeof source ||
        target === null || source === null ||
        Array.isArray(target) !== Array.isArray(source)
    ) {
        return target;
    }

    // get all unique keys from target and source
    // this is necessary because the keys of the target and source can be different
    const keys = new Set(
        [
            ...Object.keys(target),
            ...Object.keys(source),
        ]
    );

    // iterate over all keys and compare the values
    const result = {};
    for (const key of keys) {
        const targetValue = target[key] || null;
        const sourceValue = source[key] || null;

        // if the values are not equal, add the value to the result
        if (!equals(targetValue, sourceValue)) {
            result[key] = targetValue;
        }
    }

    return Object.keys(result).length > 0 ? result : {};
}
