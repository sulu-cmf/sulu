// @flow
export type Media = {|
    id: number,
    mimeType: string,
    thumbnails: {[key: string]: string},
    url: string,
|};
