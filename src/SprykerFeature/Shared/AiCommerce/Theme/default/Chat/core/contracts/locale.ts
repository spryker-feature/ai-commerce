const isRangeError = (error: unknown): boolean =>
    typeof error === 'object' && error !== null && 'name' in error && error.name === 'RangeError';

export const formatWithPageLocale = <Result>(format: (locale: string | undefined) => Result): Result => {
    const language = document.documentElement.lang.replace(/_/g, '-');

    if (language !== '') {
        try {
            return format(language);
        } catch (error) {
            if (!isRangeError(error)) {
                throw error;
            }
        }
    }

    return format(undefined);
};
