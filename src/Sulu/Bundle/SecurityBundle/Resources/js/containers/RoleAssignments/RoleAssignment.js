// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {MultiSelect} from 'sulu-admin-bundle/components';
import type {Localization} from 'sulu-admin-bundle/stores';
import classNames from 'classnames';
import roleAssignmentStyle from './roleAssignment.scss';

type Props = {|
    disabled: boolean,
    localizations: Array<Localization>,
    onChange: (value: Object) => void,
    value: Object,
|};

@observer
class RoleAssignment extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    handleChange = (newLocalizations: Array<string>) => {
        const newValue = {...this.props.value};
        newValue.locales = newLocalizations;

        this.props.onChange(newValue);
    };

    render() {
        const {disabled, localizations, value} = this.props;

        const roleAssignmentClass = classNames(
            roleAssignmentStyle.roleAssignment,
            {
                [roleAssignmentStyle.disabled]: disabled,
            }
        );

        return (
            <tr className={roleAssignmentClass}>
                <td>{value.role.name}</td>
                <td>{value.role.system}</td>
                <td className={roleAssignmentStyle.locale}>
                    <MultiSelect
                        disabled={disabled}
                        onChange={this.handleChange}
                        values={value.locales}
                    >
                        {localizations.map((localization, index) => (
                            <MultiSelect.Option key={index} value={localization.locale}>
                                {localization.locale}
                            </MultiSelect.Option>
                        ))}
                    </MultiSelect>
                </td>
            </tr>
        );
    }
}

export default RoleAssignment;
