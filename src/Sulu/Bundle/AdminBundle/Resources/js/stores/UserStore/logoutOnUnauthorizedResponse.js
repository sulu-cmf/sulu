// @flow
import type {HandleResponseHook} from '../../services/Requester/types';
import userStore from './UserStore';

const logoutOnUnauthorizedResponse: HandleResponseHook = function (response: Response) {
    if (response.status === 401) {
        userStore.setLoggedIn(false);
    }
};

export default logoutOnUnauthorizedResponse;
