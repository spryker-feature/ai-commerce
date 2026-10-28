// Wrapped errors repeat the original message in their own text, because browser consoles often hide `cause`.

export const describeFailure = (failure: unknown): string =>
    failure instanceof Error ? failure.message : String(failure);
