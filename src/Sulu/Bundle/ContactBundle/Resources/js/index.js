// @flow
import React from 'react';
import {when} from 'mobx';
import {CardCollection, fieldRegistry} from 'sulu-admin-bundle/containers';
import {initializer} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import {listToolbarActionRegistry} from 'sulu-admin-bundle/views';
import AddressCardPreview from './components/AddressCardPreview';
import BankCardPreview from './components/BankCardPreview';
import ContactDetails from './containers/Form/fields/ContactDetails';
import Bic from './containers/Form/fields/Bic';
import ContactAccountSelection from './containers/Form/fields/ContactAccountSelection';
import Iban from './containers/Form/fields/Iban';
import Email from './components/ContactDetails/Email';
import Fax from './components/ContactDetails/Fax';
import Phone from './components/ContactDetails/Phone';
import SocialMedia from './components/ContactDetails/SocialMedia';
import Website from './components/ContactDetails/Website';
import AddContactToolbarAction from './views/List/toolbarActions/AddContactToolbarAction';
import AddMediaToolbarAction from './views/List/toolbarActions/AddMediaToolbarAction';
import DeleteMediaToolbarAction from './views/List/toolbarActions/DeleteMediaToolbarAction';

fieldRegistry.add('contact_details', ContactDetails);
fieldRegistry.add('iban', Iban);
fieldRegistry.add('bic', Bic);
fieldRegistry.add('contact_account_selection', ContactAccountSelection);

listToolbarActionRegistry.add('sulu_contact.add_contact', AddContactToolbarAction);
listToolbarActionRegistry.add('sulu_contact.add_media', AddMediaToolbarAction);
listToolbarActionRegistry.add('sulu_contact.delete_media', DeleteMediaToolbarAction);

initializer.addUpdateConfigHook('sulu_contact', (config: Object, initialized: boolean) => {
    if (initialized) {
        return;
    }

    when(
        () => !!initializer.initializedTranslationsLocale,
        (): void => {
            Email.types = config.emailTypes
                .map((emailType) => ({label: translate(emailType.name), value: emailType.id}));
            Fax.types = config.faxTypes
                .map((faxType) => ({label: translate(faxType.name), value: faxType.id}));
            Phone.types = config.phoneTypes
                .map((phoneType) => ({label: translate(phoneType.name), value: phoneType.id}));
            SocialMedia.types = config.socialMediaTypes
                .map((socialMediaType) => ({label: socialMediaType.name, value: socialMediaType.id}));
            Website.types = config.websiteTypes
                .map((urlType) => ({label: translate(urlType.name), value: urlType.id}));

            fieldRegistry.add(
                'addresses',
                CardCollection,
                {
                    addOverlayTitle: 'sulu_contact.add_address',
                    editOverlayTitle: 'sulu_contact.edit_address',
                    renderCardContent: function AddressCard(card) {
                        const addressType = config.addressTypes
                            .find((addressType) => card.addressType === addressType.id);

                        return (
                            <AddressCardPreview
                                billingAddress={card.billingAddress}
                                city={card.city}
                                country={card.countryCode ? config.countries[card.countryCode] : undefined}
                                deliveryAddress={card.deliveryAddress}
                                number={card.number}
                                primaryAddress={card.primaryAddress}
                                state={card.state}
                                street={card.street}
                                title={card.title}
                                type={translate(addressType.name)}
                                zip={card.zip}
                            />
                        );
                    },
                    schema: {
                        title: {
                            label: translate('sulu_admin.title'),
                            type: 'text_line',
                        },
                        addresTypeInformation: {
                            items: {
                                addressType: {
                                    options: {
                                        default_value: {
                                            value: config.addressTypes[0].id,
                                        },
                                        values: {
                                            value: config.addressTypes.map((addressType) => ({
                                                name: addressType.id,
                                                title: translate(addressType.name),
                                            })),
                                        },
                                    },
                                    colSpan: 6,
                                    type: 'single_select',
                                },
                                primaryAddress: {
                                    options: {
                                        label: {
                                            title: translate('sulu_contact.primary_address'),
                                        },
                                    },
                                    colSpan: 6,
                                    type: 'checkbox',
                                },
                                deliveryAddress: {
                                    options: {
                                        label: {
                                            title: translate('sulu_contact.delivery_address'),
                                        },
                                    },
                                    colSpan: 6,
                                    type: 'checkbox',
                                },
                                billingAddress: {
                                    options: {
                                        label: {
                                            title: translate('sulu_contact.billing_address'),
                                        },
                                    },
                                    colSpan: 6,
                                    type: 'checkbox',
                                },
                            },
                            type: 'section',
                        },
                        address: {
                            items: {
                                street: {
                                    label: translate('sulu_contact.street'),
                                    colSpan: 8,
                                    type: 'text_line',
                                },
                                number: {
                                    label: translate('sulu_contact.number'),
                                    colSpan: 4,
                                    type: 'text_line',
                                },
                                addition: {
                                    label: translate('sulu_contact.address_line'),
                                    type: 'text_line',
                                },
                                zip: {
                                    label: translate('sulu_contact.zip'),
                                    colSpan: 4,
                                    type: 'text_line',
                                },
                                city: {
                                    label: translate('sulu_contact.city'),
                                    colSpan: 8,
                                    type: 'text_line',
                                },
                                state: {
                                    label: translate('sulu_contact.state'),
                                    type: 'text_line',
                                },
                                countryCode: {
                                    label: translate('sulu_contact.country'),
                                    options: {
                                        values: {
                                            value: Object.keys(config.countries).map((countryCode) => ({
                                                name: countryCode,
                                                title: config.countries[countryCode],
                                            })),
                                        },
                                    },
                                    type: 'single_select',
                                },
                            },
                            type: 'section',
                        },
                        postbox: {
                            items: {
                                postboxNumber: {
                                    label: translate('sulu_contact.postbox_number'),
                                    type: 'text_line',
                                },
                                postboxPostcode: {
                                    label: translate('sulu_contact.postbox_zip'),
                                    colSpan: 4,
                                    type: 'text_line',
                                },
                                postboxCity: {
                                    label: translate('sulu_contact.postbox_city'),
                                    colSpan: 8,
                                    type: 'text_line',
                                },
                            },
                            type: 'section',
                        },
                        coordinates: {
                            items: {
                                latitude: {
                                    label: translate('sulu_contact.latitude'),
                                    colSpan: 6,
                                    type: 'number',
                                },
                                longitude: {
                                    label: translate('sulu_contact.longitude'),
                                    colSpan: 6,
                                    type: 'number',
                                },
                            },
                            type: 'section',
                        },
                        note: {
                            items: {
                                note: {
                                    label: translate('sulu_contact.note'),
                                    type: 'text_area',
                                },
                            },
                            type: 'section',
                        },
                    },
                }
            );

            fieldRegistry.add(
                'bankAccounts',
                CardCollection,
                {
                    addOverlayTitle: 'sulu_contact.add_bank_account',
                    editOverlayTitle: 'sulu_contact.edit_bank_account',
                    jsonSchema: {
                        required: ['iban'],
                    },
                    renderCardContent: function BankCard(card) {
                        return (
                            <BankCardPreview
                                bankName={card.bankName}
                                bic={card.bic}
                                iban={card.iban}
                            />
                        );
                    },
                    schema: {
                        bankName: {
                            label: translate('sulu_contact.bank'),
                            type: 'text_line',
                        },
                        iban: {
                            label: translate('sulu_contact.iban'),
                            required: true,
                            colSpan: 8,
                            type: 'iban',
                        },
                        bic: {
                            label: translate('sulu_contact.bic'),
                            colSpan: 4,
                            type: 'bic',
                        },
                    },
                }
            );
        }
    );
});
