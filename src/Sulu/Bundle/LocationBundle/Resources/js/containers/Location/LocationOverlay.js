// @flow
import React from 'react';
import {action, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {Form, Input, Number, Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {MapContainer, Marker, TileLayer} from 'react-leaflet';
import {Map} from 'leaflet';
import {SingleAutoComplete} from 'sulu-admin-bundle/containers';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import locationOverlayStyles from './locationOverlay.scss';
import type {Location as LocationValue} from '../../types';

type Props = {
    onClose: () => void,
    onConfirm: (?LocationValue) => void,
    open: boolean,
    value: ?LocationValue,
};

@observer
class LocationOverlay extends React.Component<Props> {
    @observable lat: ?number;
    @observable long: ?number;
    @observable zoom: number;

    @observable title: ?string;
    @observable street: ?string;
    @observable number: ?string;
    @observable code: ?string;
    @observable town: ?string;
    @observable country: ?string;

    map: ?(typeof Map);
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
                this.lat = this.props.value ? this.props.value.lat : null;
                this.long = this.props.value ? this.props.value.long : null;
                this.zoom = this.props.value ? this.props.value.zoom : 1;
                this.updateMapToData();

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

    setLeafletMap = (map: typeof Map) => {
        this.map = map;

        if (map) {
            map.on('zoomanim', this.handleMapZoom);
        }
    };

    updateMapToData = () => {
        if (this.map) {
            this.map.setView([this.lat || 0, this.long || 0], this.zoom || 1);
        }
    };

    handleConfirm = () => {
        const {onConfirm} = this.props;
        const {title, street, number, code, town, country, lat, long, zoom} = this;

        if (lat === null || lat === undefined || long === null || long === undefined) {
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
            lat,
            long,
            zoom,
        });
    };

    @action handleAutoCompleteChange = (data: Object) => {
        if (!data) {
            return;
        }

        this.lat = data.latitude;
        this.long = data.longitude;
        this.updateMapToData();

        this.title = data.displayTitle;
        this.street = data.street;
        this.number = data.number;
        this.code = data.code;
        this.town = data.town;
        this.country = data.country;
    };

    @action handleMapZoom = (event: Object) => {
        this.zoom = event.zoom;
    };

    @action handleMarkerDrag = (event: Object) => {
        this.long = event.latlng.lng;
        this.lat = event.latlng.lat;
    };

    @action handleMarkerDragEnd = () => {
        this.updateMapToData();
    };

    @action handleResetLocation = () => {
        this.long = null;
        this.lat = null;
        this.zoom = 1;
        this.updateMapToData();

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
        this.lat = lat;
        this.updateMapToData();
    };

    @action handleLongChange = (long: ?number) => {
        this.long = long;
        this.updateMapToData();
    };

    @action handleZoomChange = (zoom: ?number) => {
        this.zoom = zoom || 1;
        this.updateMapToData();
    };

    render() {
        const {
            onClose,
            open,
        } = this.props;

        // enable confirm button if all marker properties are set or no property is set in case of a reset
        const confirmEnabled = (this.lat !== null && this.long !== null)
            || (this.lat === null && this.long === null);

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
                            <MapContainer
                                attributionControl={false}
                                center={[this.lat || 0, this.long || 0]}
                                className={locationOverlayStyles.map}
                                ref={this.setLeafletMap}
                                zoom={this.zoom}
                            >
                                <TileLayer url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" />
                                <Marker
                                    draggable={true}
                                    eventHandlers={{
                                        drag: this.handleMarkerDrag,
                                        dragend: this.handleMarkerDragEnd,
                                    }}
                                    position={[this.lat || 0, this.long || 0]}
                                />
                            </MapContainer>
                        </Form.Field>

                        <Form.Field colSpan={4} label={translate('sulu_location.latitude')} required={true}>
                            <Number onChange={this.handleLatChange} step={0.001} value={this.lat} />
                        </Form.Field>
                        <Form.Field colSpan={4} label={translate('sulu_location.longitude')} required={true}>
                            <Number onChange={this.handleLongChange} step={0.001} value={this.long} />
                        </Form.Field>
                        <Form.Field colSpan={4} label={translate('sulu_location.zoom')} required={true}>
                            <Number max={18} min={0} onChange={this.handleZoomChange} value={this.zoom} />
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
