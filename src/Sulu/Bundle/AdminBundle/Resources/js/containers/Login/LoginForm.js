// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Input from '../../components/Input/index';
import formStyles from './form.scss';
import Header from './Header';

type Props = {
    error: boolean,
    loading: boolean,
    onChangeForm: () => void,
    onPasswordChange: (user: ?string) => void,
    onSubmit: (event: SyntheticEvent<HTMLFormElement>) => void,
    onUserChange: (user: ?string) => void,
    password: ?string,
    user: ?string,
};

@observer
class LoginForm extends React.Component<Props> {
    static defaultProps = {
        error: false,
        loading: false,
    };

    @observable inputRef: ?ElementRef<*>;

    @computed get submitButtonDisabled(): boolean {
        return this.props.error || !(this.props.user && this.props.password);
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    componentDidMount() {
        if (this.inputRef) {
            this.inputRef.focus();
        }
    }

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
                <form className={formStyles.form} onSubmit={this.props.onSubmit}>
                    <fieldset>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input
                                icon="su-user"
                                inputRef={this.setInputRef}
                                onChange={this.props.onUserChange}
                                valid={!this.props.error}
                                value={this.props.user}
                            />
                        </label>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.password')}
                            </div>
                            <Input
                                icon="su-lock"
                                onChange={this.props.onPasswordChange}
                                type="password"
                                valid={!this.props.error}
                                value={this.props.password}
                            />
                        </label>
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
