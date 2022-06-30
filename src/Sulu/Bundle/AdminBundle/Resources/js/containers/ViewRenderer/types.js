// @flow
import Router, {Route} from '../../services/Router';
import type {Component, Element} from 'react';
import type {AttributeMap} from '../../services/Router';

export type ViewProps = {
    children?: (?Object) => Element<*> | null,
    isRootView?: boolean,
    route: Route,
    router: Router,
};

interface GetDerivedRouteAttributesInterface {
    +getDerivedRouteAttributes?: (route: Route, attributes: AttributeMap) => Object,
}

interface RemountViewOnLoginInterface {
    +remountViewOnLogin?: boolean,
}

export type View = Class<Component<ViewProps & *>> & GetDerivedRouteAttributesInterface & RemountViewOnLoginInterface;

export type ViewConfig = {
    rootSpaceless?: boolean,
};
