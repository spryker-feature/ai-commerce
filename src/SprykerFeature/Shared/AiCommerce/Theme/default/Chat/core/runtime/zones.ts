import { CHAT_TAG_NAME } from '../contracts/access';
import { CORE_ZONE } from '../contracts/blocks';
import type { ChatZoneDirectory, ChatZonePosition } from '../contracts/blocks';

export { CORE_ZONE };

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/zones.ts';
const ZONE_ATTRIBUTE = 'data-zone';

export interface ChatZoneLedger {
    forOwner(owner: object, ownerName: string): ChatZoneDirectory;
    release(owner: object): void;
}

export const createZoneLedger = (chat: HTMLElement): ChatZoneLedger => {
    const insertedByOwner = new Map<object, Element[]>();

    const findZone = (zone: string): HTMLElement | null =>
        Array.from(chat.querySelectorAll<HTMLElement>(`[${ZONE_ATTRIBUTE}]`)).find(
            (element) => element.getAttribute(ZONE_ATTRIBUTE) === zone && element.closest(CHAT_TAG_NAME) === chat,
        ) ?? null;

    return {
        forOwner: (owner: object, ownerName: string): ChatZoneDirectory =>
            Object.freeze({
                insert: (zone: string, element: Element, position: ChatZonePosition = 'end'): void => {
                    const container = findZone(zone);

                    if (container === null) {
                        throw new Error(
                            `<${ownerName}> inserts into the chat zone "${zone}", but no element with ` +
                                `${ZONE_ATTRIBUTE}="${zone}" is rendered inside <${CHAT_TAG_NAME}> (reported by ` +
                                `${SOURCE_FILE}). Use one of the core zones (${Object.values(CORE_ZONE).join(', ')}), ` +
                                `or keep ${ZONE_ATTRIBUTE}="${zone}" in the block override that should host it.`,
                        );
                    }

                    if (position === 'start') {
                        container.prepend(element);
                    } else {
                        container.append(element);
                    }

                    insertedByOwner.set(owner, [...(insertedByOwner.get(owner) ?? []), element]);
                },
                has: (zone: string): boolean => findZone(zone) !== null,
            }),
        release: (owner: object): void => {
            for (const element of insertedByOwner.get(owner) ?? []) {
                element.remove();
            }

            insertedByOwner.delete(owner);
        },
    };
};
