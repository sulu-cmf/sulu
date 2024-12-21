// @flow
import {FormatValidator} from 'ajv';
import validateEmail from '../../Email/validateEmail';

const idnEmailValidator: typeof FormatValidator = (data: string): boolean => {
    return validateEmail(data);
};

export default idnEmailValidator;
