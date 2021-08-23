// @flow
import React from 'react';
import log from 'loglevel';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../../components/Icon/index';
import {translate} from '../../utils/index';
import Loader from '../../components/Loader/Loader';
import Router from '../../services/Router';
import userStore from '../../stores/userStore';
import ForgotPasswordForm from './ForgotPasswordForm';
import LoginForm from './LoginForm';
import ResetPasswordForm from './ResetPasswordForm';
import loginStyles from './login.scss';
import type {FormTypes} from './types';

const BACK_LINK_ARROW_LEFT_ICON = 'su-angle-left';

type Props = {|
    backLink: string,
    initialized: boolean,
    onLoginSuccess: () => void,
    router: Router,
|};

@observer
class Login extends React.Component<Props> {
    static defaultProps = {
        backLink: '/',
        initialized: false,
    };

    @observable visibleForm: FormTypes = this.props.router.attributes.forgotPasswordToken ? 'reset-password' : 'login';

    @computed get loginFormVisible(): boolean {
        return this.visibleForm === 'login';
    }

    @computed get forgotPasswordFormVisible(): boolean {
        return this.visibleForm === 'forgot-password';
    }

    @computed get resetPasswordFormVisible(): boolean {
        return this.visibleForm === 'reset-password';
    }

    @action clearState = () => {
        if (this.loginFormVisible) {
            userStore.setLoginError(false);
        } else if (this.forgotPasswordFormVisible) {
            userStore.setForgotPasswordSuccess(false);
        }
    };

    @action handleChangeToLoginForm = () => {
        this.props.router.reset();
        this.visibleForm = 'login';
    };

    @action handleChangeToForgotPasswordForm = () => {
        this.visibleForm = 'forgot-password';
    };

    handleLoginFormSubmit = (data: string | Object, password?: string) => {
        if (typeof data === 'string') {
            log.warn(
                'The "handleLoginFormSubmit" method called with user and password string'
                + 'is deprecated give object instead.'
            );

            data = {
                username: data,
                password,
            };
        }

        userStore.login(data).then(() => {
            this.props.onLoginSuccess();
        });
    };

    handleForgotPasswordFormSubmit = (data: string | Object) => {
        if (typeof data === 'string') {
            log.warn(
                'The "handleForgotPasswordFormSubmit" method called with user and password string'
                + 'is deprecated give object instead.'
            );

            data = {
                user: data,
            };
        }

        userStore.forgotPassword(data);
    };

    handleResetPasswordFormSubmit = (data: string | Object) => {
        const {
            onLoginSuccess,
            router,
        } = this.props;

        const {forgotPasswordToken} = router.attributes;

        if (typeof forgotPasswordToken !== 'string') {
            throw new Error('The "forgotPasswordToken" router attribute must be a string!');
        }

        if (typeof data === 'string') {
            log.warn(
                'The "handleResetPasswordFormSubmit" method called with user and password string'
                + 'is deprecated give object instead.'
            );

            data = {
                password: data,
            };
        }

        userStore.resetPassword({
            token: forgotPasswordToken,
            ...data,
        })
            .then(() => {
                router.reset();
                onLoginSuccess();
            });
    };

    render() {
        const {backLink, initialized} = this.props;

        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.loginContainer}>
                    <div className={loginStyles.formContainer}>
                        <div className={loginStyles.logoContainer}>
                            <Icon name="su-sulu" />
                        </div>
                        {!initialized &&
                            <div className={loginStyles.loaderContainer}>
                                <Loader size={20} />
                            </div>
                        }
                        {initialized && this.loginFormVisible &&
                            <LoginForm
                                error={userStore.loginError}
                                loading={userStore.loading}
                                onChangeForm={this.handleChangeToForgotPasswordForm}
                                onSubmit={this.handleLoginFormSubmit}
                            />
                        }
                        {initialized && this.forgotPasswordFormVisible &&
                            <ForgotPasswordForm
                                loading={userStore.loading}
                                onChangeForm={this.handleChangeToLoginForm}
                                onSubmit={this.handleForgotPasswordFormSubmit}
                                success={userStore.forgotPasswordSuccess}
                            />
                        }
                        {initialized && this.resetPasswordFormVisible &&
                            <ResetPasswordForm
                                loading={userStore.loading}
                                onChangeForm={this.handleChangeToLoginForm}
                                onSubmit={this.handleResetPasswordFormSubmit}
                            />
                        }
                    </div>
                    <div className={loginStyles.backLinkContainer}>
                        {initialized &&
                            <a className={loginStyles.backLink} href={backLink}>
                                <Icon className={loginStyles.backLinkIcon} name={BACK_LINK_ARROW_LEFT_ICON} />
                                {translate('sulu_admin.back_to_website')}
                            </a>
                        }
                    </div>
                </div>
            </div>
        );
    }
}

export default Login;
