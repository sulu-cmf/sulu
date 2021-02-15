// @flow
import React from 'react';
import {action, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {Form, Input, Number, Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {Map, Marker, TileLayer} from 'react-leaflet';
import {SingleAutoComplete} from 'sulu-admin-bundle/containers';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import type {Location as LocationValue} from '../../types';
import locationOverlayStyles from './locationOverlay.scss';

type Props = {
    onClose: () => void,
    onConfirm: (?LocationValue) => void,
    open: boolean,
    value: ?LocationValue,
};

@observer
class LocationOverlay extends React.Component<Props> {
    @observable mapLat: number;
    @observable mapLong: number;
    @observable mapZoom: number;

    @observable markerLat: ?number;
    @observable markerLong: ?number;

    @observable title: ?string;
    @observable street: ?string;
    @observable number: ?string;
    @observable code: ?string;
    @observable town: ?string;
    @observable country: ?string;

    geolocatorSelectionStore: SingleSelectionStore<string>;
    updateDataOnGeolocatorSelectDisposer: () => *;
    updateDataOnOpenDisposer: () => *;

    constructor(props: Props) {
        super(props);

        this.geolocatorSelectionStore = new SingleSelectionStore('geolocator_locations');

        this.updateDataOnGeolocatorSelectDisposer = reaction(
            () => this.geolocatorSelectionStore.item,
            this.handleAutoCompleteChange
        );

        this.updateDataOnOpenDisposer = reaction(() => this.props.open, (newOpenValue) => {
            if (newOpenValue === true) {
                this.mapLat = this.props.value ? this.props.value.lat : 0;
                this.mapLong = this.props.value ? this.props.value.long : 0;
                this.mapZoom = this.props.value ? this.props.value.zoom : 1;

                this.markerLat = this.props.value ? this.props.value.lat : null;
                this.markerLong = this.props.value ? this.props.value.long : null;

                this.title = this.props.value ? this.props.value.title : null;
                this.street = this.props.value ? this.props.value.street : null;
                this.number = this.props.value ? this.props.value.number : null;
                this.code = this.props.value ? this.props.value.code : null;
                this.town = this.props.value ? this.props.value.town : null;
                this.country = this.props.value ? this.props.value.country : null;
            }
        }, {fireImmediately: true});
    }

    componentWillUnmount() {
        this.updateDataOnGeolocatorSelectDisposer();
        this.updateDataOnOpenDisposer();
    }

    handleConfirm = () => {
        const {onConfirm} = this.props;
        const {title, street, number, code, town, country, markerLat, markerLong, mapZoom} = this;

        if (markerLat === null || markerLat === undefined || markerLong === null || markerLong === undefined) {
            onConfirm(null);

            return;
        }

        onConfirm({
            title,
            street,
            number,
            code,
            town,
            country,
            lat: markerLat,
            long: markerLong,
            zoom: mapZoom,
        });
    };

    @action handleAutoCompleteChange = (data: Object) => {
        if (!data) {
            return;
        }

        this.mapLat = data.latitude || 0;
        this.mapLong = data.longitude || 0;

        this.markerLat = data.latitude;
        this.markerLong = data.longitude;

        this.title = data.displayTitle;
        this.street = data.street;
        this.number = data.number;
        this.code = data.code;
        this.town = data.town;
        this.country = data.country;
    };

    @action handleMapZoom = (event: Object) => {
        this.mapZoom = event.zoom;
    };

    @action handleMarkerDrag = (event: Object) => {
        this.markerLong = event.latlng.lng;
        this.markerLat = event.latlng.lat;
    };

    @action handleMarkerDragEnd = () => {
        this.mapLong = this.markerLong || 0;
        this.mapLat = this.markerLat || 0;
    };

    @action handleResetLocation = () => {
        this.mapLat = 0;
        this.mapLong = 0;
        this.mapZoom = 1;

        this.markerLong = null;
        this.markerLat = null;

        this.title = null;
        this.street = null;
        this.number = null;
        this.code = null;
        this.town = null;
        this.country = null;
    };

    @action handleTitleChange = (title: ?string) => {
        this.title = title;
    };

    @action handleStreetChange = (street: ?string) => {
        this.street = street;
    };

    @action handleNumberChange = (number: ?string) => {
        this.number = number;
    };

    @action handleCodeChange = (code: ?string) => {
        this.code = code;
    };

    @action handleTownChange = (town: ?string) => {
        this.town = town;
    };

    @action handleCountryChange = (country: ?string) => {
        this.country = country;
    };

    @action handleLatChange = (lat: ?number) => {
        this.mapLat = lat || 0;
        this.markerLat = lat;
    };

    @action handleLongChange = (long: ?number) => {
        this.mapLong = long || 0;
        this.markerLong = long;
    };

    @action handleZoomChange = (zoom: ?number) => {
        this.mapZoom = zoom || 1;
    };

    render() {
        const {
            onClose,
            open,
        } = this.props;

        // enable confirm button if all marker properties are set or no property is set in case of a reset
        const confirmEnabled = (this.markerLat !== null && this.markerLong !== null)
            || (this.markerLat === null && this.markerLong === null);

        return (
            <Overlay
                actions={[
                    {
                        title: translate('sulu_admin.reset'),
                        onClick: this.handleResetLocation,
                    },
                ]}
                confirmDisabled={!confirmEnabled}
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="small"
                title={translate('sulu_location.select_location')}
            >
                <div className={locationOverlayStyles.container}>
                    <Form>
                        <Form.Field>
                            <SingleAutoComplete
                                displayProperty="displayTitle"
                                searchProperties={['displayTitle']}
                                selectionStore={this.geolocatorSelectionStore}
                            />
                        </Form.Field>

                        <Form.Field>
                            <Map
                                attributionControl={false}
                                center={[this.mapLat, this.mapLong]}
                                className={locationOverlayStyles.map}
                                onZoomAnim={this.handleMapZoom}
                                zoom={this.mapZoom}
                            >
                                <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                                <Marker
                                    draggable={true}
                                    onDrag={this.handleMarkerDrag}
                                    onDragEnd={this.handleMarkerDragEnd}
                                    position={[this.markerLat || 0, this.markerLong || 0]}
                                />
                            </Map>
                        </Form.Field>

                        <Form.Field colSpan={4} label={translate('sulu_location.latitude')} required={true}>
                            <Number onChange={this.handleLatChange} step={0.001} value={this.markerLat} />
                        </Form.Field>
                        <Form.Field colSpan={4} label={translate('sulu_location.longitude')} required={true}>
                            <Number onChange={this.handleLongChange} step={0.001} value={this.markerLong} />
                        </Form.Field>
                        <Form.Field colSpan={4} label={translate('sulu_location.zoom')} required={true}>
                            <Number max={18} min={0} onChange={this.handleZoomChange} value={this.mapZoom} />
                        </Form.Field>

                        <Form.Section label={translate('sulu_location.additional_information')}>
                            <Form.Field label={translate('sulu_location.title')}>
                                <Input onChange={this.handleTitleChange} value={this.title} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.street')}>
                                <Input onChange={this.handleStreetChange} value={this.street} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.number')}>
                                <Input onChange={this.handleNumberChange} value={this.number} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.code')}>
                                <Input onChange={this.handleCodeChange} value={this.code} />
                            </Form.Field>
                            <Form.Field colSpan={6} label={translate('sulu_location.town')}>
                                <Input onChange={this.handleTownChange} value={this.town} />
                            </Form.Field>
                            <Form.Field label={translate('sulu_location.country')}>
                                <Input onChange={this.handleCountryChange} value={this.country} />
                            </Form.Field>
                        </Form.Section>
                    </Form>
                </div>
            </Overlay>
        );
    }
}

export default LocationOverlay;
