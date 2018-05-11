// @flow
import React, {Fragment} from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils';
import Button from '../../components/Button';
import Input from '../../components/Input';
import formStyles from './form.scss';
import Header from './Header';

type Props = {
    loading: boolean,
    user: ?string,
    password: ?string,
    onSubmit: (event: SyntheticEvent<HTMLFormElement>) => void,
    onChangeForm: () => void,
    onUserChange: (user: ?string) => void,
    onPasswordChange: (user: ?string) => void,
    error: boolean,
};

@observer
export default class LoginForm extends React.Component<Props> {
    static defaultProps = {
        loading: false,
        error: false,
    };

    @computed get submitButtonDisabled(): boolean {
        return this.props.error || !(this.props.user && this.props.password);
    }

    renderHeader() {
        if (this.props.error) {
            return (
                <Header small={true}>
                    {translate('sulu_admin.login_error')}
                </Header>
            );
        }

        return (
            <Header>
                {translate('sulu_admin.welcome')}
            </Header>
        );
    }

    render() {
        const loginFormClass = classNames(
            formStyles.form,
            formStyles.loginForm
        );

        const inputFieldClass = classNames(
            formStyles.inputField,
            {
                [formStyles.error]: this.props.error,
            }
        );

        return (
            <Fragment>
                {this.renderHeader()}
                <form className={loginFormClass} onSubmit={this.props.onSubmit}>
                    <fieldset>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input
                                valid={!this.props.error}
                                icon="su-user"
                                value={this.props.user}
                                onChange={this.props.onUserChange}
                            />
                        </label>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.password')}
                            </div>
                            <Input
                                valid={!this.props.error}
                                icon="su-lock"
                                type="password"
                                value={this.props.password}
                                onChange={this.props.onPasswordChange}
                            />
                        </label>
                        <div className={formStyles.buttons}>
                            <Button skin="link" onClick={this.props.onChangeForm}>
                                {translate('sulu_admin.forgot_password')}
                            </Button>
                            <Button
                                disabled={this.submitButtonDisabled}
                                type="submit"
                                skin="primary"
                                loading={this.props.loading}
                            >
                                {translate('sulu_admin.login')}
                            </Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}
