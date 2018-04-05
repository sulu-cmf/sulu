// @flow
import type {ComponentType} from 'react';

export type SidebarViewOptions = {};

export type SidebarView = ComponentType<SidebarViewOptions>;

export type Size = 'small' | 'medium' | 'large';

export type SidebarConfig = {
    view?: string,
    props?: Object,
    sizes?: Array<Size>,
    defaultSize?: Size,
};
