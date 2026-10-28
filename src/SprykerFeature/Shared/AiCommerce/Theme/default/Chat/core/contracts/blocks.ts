export interface ChatBlockSpec<Reference extends string = string> {
    readonly name: string;
    readonly references: readonly Reference[];
}

export interface ChatBlockInstance<Reference extends string = string> {
    readonly root: HTMLElement;
    readonly references: Readonly<Record<Reference, HTMLElement>>;
    find(reference: string): HTMLElement | null;
}

export interface ChatBlockTemplate<Reference extends string = string> {
    readonly name: string;
    create(): ChatBlockInstance<Reference>;
}

export interface ChatBlockResolver {
    require<Reference extends string>(spec: ChatBlockSpec<Reference>): ChatBlockTemplate<Reference>;
}

export const CORE_ZONE = {
    headerStart: 'headerStart',
    headerActions: 'headerActions',
    views: 'views',
    welcome: 'welcome',
    composerStart: 'composerStart',
    composerActions: 'composerActions',
    footer: 'footer',
    moreMenu: 'moreMenu',
} as const;

export type ChatCoreZone = (typeof CORE_ZONE)[keyof typeof CORE_ZONE];

export type ChatZonePosition = 'start' | 'end';

export interface ChatZoneDirectory {
    insert(zone: string, element: Element, position?: ChatZonePosition): void;
    // An insert into a missing zone throws.
    has(zone: string): boolean;
}
