// @flow
export type RouteConfig = {|
    name: string,
    parent?: string,
    view: string,
    path: string,
    options: Object,
    attributeDefaults: AttributeMap,
    rerenderAttributes: Array<string>,
|};

export type Route = {|
    name: string,
    parent: ?Route,
    children: Array<Route>,
    view: string,
    path: string,
    options: Object,
    attributeDefaults: AttributeMap,
    rerenderAttributes: Array<string>,
|};

export type AttributeMap = {[string]: string};

export type RouteMap = {[string]: Route};
