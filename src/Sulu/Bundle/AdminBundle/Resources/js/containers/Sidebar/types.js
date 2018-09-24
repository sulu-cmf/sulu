// @flow
export type Size = 'small' | 'medium' | 'large';

export type SidebarConfig = {|
    view: string,
    props?: Object,
    sizes?: Array<Size>,
    defaultSize?: Size,
|};
