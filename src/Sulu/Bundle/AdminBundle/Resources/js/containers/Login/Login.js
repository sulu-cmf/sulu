// @flow
import React from 'react';
import {action, autorun, computed, observable} from 'mobx';
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
import TwoFactorForm from './TwoFactorForm';
import type {FormTypes, ForgotPasswordFormData, LoginFormData, ResetPasswordFormData, TwoFactorFormData} from './types';

const BACK_LINK_ARROW_LEFT_ICON = 'su-angle-left';

type Props = {|
    backLink: string,
    initialized: boolean,
    onLoginSuccess: () => void,
    router: Router,
|};

@observer
class Login extends React.Component<Props> {
    redirectDisposer: () => void;

    static defaultProps = {
        backLink: '/',
        initialized: false,
    };

    constructor(props: Props) {
        super(props);

        this.redirectDisposer = autorun(() => {
            if (userStore.redirectUrl !== '') {
                window.location.href = userStore.redirectUrl;
            }
        });
    }

    componentWillUnmount() {
        this.redirectDisposer();
    }

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

    @computed get twoFactorVisible(): boolean {
        return this.visibleForm === 'two-factor';
    }

    @action clearState = () => {
        if (this.loginFormVisible) {
            userStore.setLoginError(false);
        } else if (this.forgotPasswordFormVisible) {
            userStore.setForgotPasswordSuccess(false);
        } else if (this.twoFactorVisible) {
            userStore.setTwoFactorMethods([]);
            userStore.setTwoFactorError(false);
        }
    };

    @action handleChangeToLoginForm = () => {
        this.props.router.reset();
        this.visibleForm = 'login';
    };

    @action handleChangeToForgotPasswordForm = () => {
        this.visibleForm = 'forgot-password';
    };

    handleLoginFormSubmit = (data: LoginFormData) => {
        userStore.login(data).then(() => {
            if (userStore.loginMethod === 'json_login') {
                return;
            }

            if (userStore.twoFactorMethods && userStore.twoFactorMethods.length > 0) {
                action(() => {
                    this.visibleForm = 'two-factor';
                })();

                return;
            }

            this.props.onLoginSuccess();
        });
    };

    handleForgotPasswordFormSubmit = (data: ForgotPasswordFormData) => {
        userStore.forgotPassword(data).then(() => {
            this.props.onLoginSuccess();
        });
    };

    handleTwoFactorFormSubmit = (data: TwoFactorFormData) => {
        userStore.twoFactorLogin(data).then(() => {
            this.props.onLoginSuccess();
        });
    };

    handleResetPasswordFormSubmit = (data: ResetPasswordFormData) => {
        const {
            onLoginSuccess,
            router,
        } = this.props;

        const {forgotPasswordToken} = router.attributes;

        if (typeof forgotPasswordToken !== 'string') {
            throw new Error('The "forgotPasswordToken" router attribute must be a string!');
        }

        userStore.resetPassword({
            ...data,
            token: forgotPasswordToken,
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
                                mode={userStore.hasSingleSignOn() ? (
                                    userStore.loginMethod === 'json_login' ? 'password_only' : 'username_only'
                                ) : 'username_password'}
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
                        {initialized && this.twoFactorVisible &&
                            <TwoFactorForm
                                error={userStore.twoFactorError}
                                loading={userStore.loading}
                                methods={userStore.twoFactorMethods}
                                onChangeForm={this.handleChangeToLoginForm}
                                onSubmit={this.handleTwoFactorFormSubmit}
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
