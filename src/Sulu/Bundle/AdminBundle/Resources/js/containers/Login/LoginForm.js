// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Input from '../../components/Input/index';
import formStyles from './form.scss';
import Header from './Header';
import type {ElementRef} from 'react';
import type {LoginFormData} from './types';

type Props = {|
    error: boolean,
    loading: boolean,
    mode: string,
    onChangeForm: () => void,
    onSubmit: (data: LoginFormData) => void,
|};

@observer
class LoginForm extends React.Component<Props> {
    static defaultProps = {
        error: false,
        loading: false,
        mode: 'username_password',
    };

    @observable inputRef: ?ElementRef<*>;

    @observable user: ?string;
    @observable password: ?string;

    @computed get submitButtonDisabled(): boolean {
        return !(this.user && this.password)
            && !((this.user || this.password)
                && this.props.mode !== 'username_password');
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    componentDidMount() {
        if (this.inputRef) {
            this.inputRef.focus();
        }
    }

    @action handleUserChange = (user: ?string) => {
        this.user = user;
    };

    @action handlePasswordChange = (password: ?string) => {
        this.password = password;
    };

    @action handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (this.user && this.props.mode !== 'username_password') {
            const {onSubmit} = this.props;

            onSubmit({
                username: this.user,
                password: this.password ?? '',
            });

            if (this.user && this.password) {
                this.user = undefined;
                this.password = undefined;
            }

            return;
        }

        if (!this.user || !this.password) {
            return;
        }

        const {onSubmit} = this.props;

        onSubmit({
            username: this.user,
            password: this.password,
        });
    };

    render() {
        const {error} = this.props;

        const inputFieldClass = classNames(
            formStyles.inputField,
            {
                [formStyles.error]: error,
            }
        );

        return (
            <Fragment>
                <Header small={error}>
                    {translate(error ? 'sulu_admin.login_error' : 'sulu_admin.welcome')}
                </Header>
                <form className={formStyles.form} onSubmit={this.handleSubmit}>
                    <fieldset>
                        {(this.props.mode !== 'password_only') && (
                            <label className={inputFieldClass}>
                                <div className={formStyles.labelText}>
                                    {translate('sulu_admin.username_or_email')}
                                </div>
                                <Input
                                    autocomplete="username"
                                    autoFocus={this.props.mode === 'username_only'}
                                    icon="su-user"
                                    inputRef={this.setInputRef}
                                    onChange={this.handleUserChange}
                                    valid={!this.props.error}
                                    value={this.user}
                                />
                            </label>
                        )}
                        {(this.props.mode !== 'username_only') && (
                            <label className={inputFieldClass}>
                                <div className={formStyles.labelText}>
                                    {translate('sulu_admin.password')}
                                </div>
                                <Input
                                    autocomplete="current-password"
                                    autoFocus={this.props.mode === 'password_only'}
                                    icon="su-lock"
                                    onChange={this.handlePasswordChange}
                                    type="password"
                                    valid={!this.props.error}
                                    value={this.password}
                                />
                            </label>
                        )}
                        <div className={formStyles.buttons}>
                            <Button onClick={this.props.onChangeForm} skin="link">
                                {translate('sulu_admin.forgot_password')}
                            </Button>
                            <Button
                                disabled={this.submitButtonDisabled}
                                loading={this.props.loading}
                                skin="primary"
                                type="submit"
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

export default LoginForm;
