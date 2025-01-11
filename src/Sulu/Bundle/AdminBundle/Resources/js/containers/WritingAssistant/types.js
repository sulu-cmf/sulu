// @flow

export type MessageType = {|
    collapsed: boolean,
    command?: string,
    displayActions?: boolean,
    expert?: string,
    text: string,
    title?: string,
    type: string,
|};

export type ExpertType = {|
    description: string,
    name: string,
    options: {
        predefinedPrompts?: {
            name: string,
            prompt: string,
            type: string,
        }[],
    },
    uuid: string,
|};
