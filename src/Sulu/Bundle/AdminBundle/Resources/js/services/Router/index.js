// @flow
import Router from './Router';
import Route from './Route';
import getViewKeyFromRoute from './getViewKeyFromRoute';
import routeRegistry from './registries/routeRegistry';
import resourceViewRegistry from './registries/resourceViewRegistry';
import type {AttributeMap} from './types';

export default Router;
export {getViewKeyFromRoute, Route, routeRegistry, resourceViewRegistry};
export type {AttributeMap};
