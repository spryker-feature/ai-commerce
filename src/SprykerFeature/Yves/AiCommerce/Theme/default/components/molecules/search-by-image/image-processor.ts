const MAX_COMPRESSION_QUALITY = 0.9;

function getErrorMessageByKey(errorKey) {
    const parentContainer = <HTMLElement>document.querySelector('search-by-image');
    return parentContainer.getAttribute(errorKey) ?? '';
}

export function fileToCanvas(file: File): Promise<HTMLCanvasElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        const objectUrl = URL.createObjectURL(file);

        image.onload = () => {
            URL.revokeObjectURL(objectUrl);

            const canvas = document.createElement('canvas');
            canvas.width = image.width;
            canvas.height = image.height;

            const context = canvas.getContext('2d');

            if (!context) {
                reject(new Error('Failed to get canvas context.'));

                return;
            }

            context.drawImage(image, 0, 0);
            resolve(canvas);
        };

        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error(getErrorMessageByKey('data-error-invalid-file-size')));
        };

        image.src = objectUrl;
    });
}

export function canvasToJpegFile(canvas: HTMLCanvasElement, quality = MAX_COMPRESSION_QUALITY): Promise<File> {
    const JPEG_MIME_TYPE = 'image/jpeg';
    const JPEG_FILE_NAME = 'photo.jpg';

    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    reject(new Error(getErrorMessageByKey('data-error-invalid-file-size')));

                    return;
                }

                resolve(new File([blob], JPEG_FILE_NAME, { type: JPEG_MIME_TYPE }));
            },
            JPEG_MIME_TYPE,
            quality,
        );
    });
}

export async function processImage(file: File, maxFileSizeBytes: number): Promise<File> {
    const MIN_COMPRESSION_QUALITY = 0.5;
    const COMPRESSION_QUALITY_STEP = 0.1;
    const COMPRESSION_QUALITY_PRECISION_FACTOR = 10;

    if (file.size > maxFileSizeBytes) {
        throw new Error(getErrorMessageByKey('data-error-invalid-file-size'));
    }

    const canvas = await fileToCanvas(file);
    let quality = MAX_COMPRESSION_QUALITY;
    let result = await canvasToJpegFile(canvas, quality);

    while (quality > MIN_COMPRESSION_QUALITY) {
        quality =
            Math.round((quality - COMPRESSION_QUALITY_STEP) * COMPRESSION_QUALITY_PRECISION_FACTOR) /
            COMPRESSION_QUALITY_PRECISION_FACTOR;
        result = await canvasToJpegFile(canvas, quality);
    }

    return result;
}
