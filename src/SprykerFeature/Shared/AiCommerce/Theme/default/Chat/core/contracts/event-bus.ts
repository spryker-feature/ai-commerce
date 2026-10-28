import type { ChatEventListener, ChatEventMap, ChatEventName, ChatEventSubscriber } from './context';

export interface ChatEventBus extends ChatEventSubscriber {
    emit<Name extends ChatEventName>(name: Name, detail: ChatEventMap[Name]): void;
    scope(): ChatScopedSubscriber;
    clear(): void;
}

export interface ChatScopedSubscriber extends ChatEventSubscriber {
    release(): void;
}

export const createEventBus = (): ChatEventBus => {
    const listenersByName = new Map<ChatEventName, Set<ChatEventListener<ChatEventName>>>();

    const on = <Name extends ChatEventName>(name: Name, listener: ChatEventListener<Name>): (() => void) => {
        const listeners = listenersByName.get(name) ?? new Set<ChatEventListener<ChatEventName>>();
        const storedListener = listener as ChatEventListener<ChatEventName>;

        listeners.add(storedListener);
        listenersByName.set(name, listeners);

        return () => {
            listeners.delete(storedListener);
        };
    };

    return {
        on: on,
        emit: <Name extends ChatEventName>(name: Name, detail: ChatEventMap[Name]): void => {
            for (const listener of [...(listenersByName.get(name) ?? [])]) {
                try {
                    listener(detail);
                } catch (error) {
                    reportError(error);
                }
            }
        },
        scope: (): ChatScopedSubscriber => {
            const unsubscribers = new Set<() => void>();

            return {
                on: <Name extends ChatEventName>(name: Name, listener: ChatEventListener<Name>): (() => void) => {
                    const unsubscribe = on(name, listener);
                    const release = (): void => {
                        unsubscribe();
                        unsubscribers.delete(release);
                    };

                    unsubscribers.add(release);

                    return release;
                },
                release: (): void => {
                    for (const unsubscribe of [...unsubscribers]) {
                        unsubscribe();
                    }
                },
            };
        },
        clear: (): void => listenersByName.clear(),
    };
};
