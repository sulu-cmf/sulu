// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed, toJS} from 'mobx';
import {CroppedText, Icon} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {MapContainer, Marker, TileLayer, Tooltip} from 'react-leaflet';
import {Map} from 'leaflet';
import equals from 'fast-deep-equal';
import classNames from 'classnames';
import locationStyles from './location.scss';
import LocationOverlay from './LocationOverlay';
import type {Location as LocationValue} from '../../types';

type Props = {|
    disabled: boolean,
    locale: string,
    onChange: (value: ?LocationValue) => void,
    value: ?LocationValue,
|};

@observer
class Location extends React.Component<Props> {
    @observable overlayOpen: boolean = false;

    map: ?(typeof Map);

    @computed get label() {
        const {value} = this.props;

        if (value) {
            return translate('sulu_location.latitude') + ': ' + value.lat + ', '
                + translate('sulu_location.longitude') + ': ' + value.long + ', '
                + translate('sulu_location.zoom') + ': ' + value.zoom;
        }

        return translate('sulu_location.select_location');
    }

    @computed get hasAdditionalInformation() {
        const {value} = this.props;

        if (!value) {
            return false;
        }

        return value.code || value.country || value.number || value.street || value.title || value.town;
    }

    componentDidUpdate(prevProps: Props) {
        const prevValue = toJS(prevProps.value);
        const newValue = toJS(this.props.value);

        if (!equals(prevValue, newValue) && newValue && this.map) {
            this.map.setView([newValue.lat || 0, newValue.long || 0], newValue.zoom || 1);
        }
    }

    setLeafletMap = (map: typeof Map) => {
        this.map = map;
    };

    @action handleEditButtonClick = () => {
        this.overlayOpen = true;
    };

    @action handleOverlayConfirm = (newValue: ?LocationValue) => {
        this.overlayOpen = false;
        this.props.onChange(newValue);
    };

    @action handleOverlayClose = () => {
        this.overlayOpen = false;
    };

    render() {
        const {
            disabled,
            value,
            locale,
        } = this.props;

        const locationClass = classNames(
            locationStyles.locationContainer,
            {
                [locationStyles.disabled]: disabled,
            }
        );

        return (
            <div className={locationClass}>
                <div className={locationStyles.locationHeader}>
                    <button
                        className={locationStyles.locationHeaderButton}
                        onClick={this.handleEditButtonClick}
                        type="button"
                    >
                        <Icon name="su-map-pin" />
                    </button>
                    <div className={locationStyles.locationHeaderLabel}>
                        <CroppedText>{this.label}</CroppedText>
                    </div>
                </div>
                {value &&
                    <MapContainer
                        attributionControl={false}
                        center={[value.lat, value.long]}
                        className={locationStyles.locationMap}
                        doubleClickZoom={false}
                        dragging={false}
                        keyboard={false}
                        scrollWheelZoom={false}
                        tap={false}
                        whenCreated={this.setLeafletMap}
                        zoom={value.zoom}
                        zoomControl={false}
                    >
                        <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                        <Marker interactive={false} position={[value.lat, value.long]}>
                            {this.hasAdditionalInformation &&
                                <Tooltip className={locationStyles.locationMapTooltip} permanent={true}>
                                    <div><b>{value.title}</b></div>
                                    <div>{value.street} {value.number}</div>
                                    <div>{value.code} {value.town}</div>
                                    <div>{value.country}</div>
                                </Tooltip>
                            }
                        </Marker>
                    </MapContainer>
                }
                <LocationOverlay
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    value={value}
                />
            </div>
        );
    }
}

export default Location;
