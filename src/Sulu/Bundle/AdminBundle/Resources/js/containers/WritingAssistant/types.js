// @flow

export type MessageType = {|
    collapsed: boolean,
    command?: string,
    displayActions?: boolean,
    expert?: string,
    text: string,
    title?: string,
    type: 'text_line' | 'text_area' | 'text_editor',
|};

export type ExpertType = {|
    description?: string,
    name: string,
    options: {
        predefinedPrompts?: Array<{
            name: string,
            prompt: string,
        }>,
    },
    uuid: string,
|};
