/**
 * Client-side image compression utility.
 * Compresses images before upload to bypass server upload size limits.
 * Uses Canvas API to resize and compress images.
 */
window.ImageCompressor = {
    /**
     * Compress an image file client-side.
     * @param {File} file - The image file to compress
     * @param {Object} options - Compression options
     * @param {number} options.maxWidth - Maximum width in pixels (default: 1920)
     * @param {number} options.maxHeight - Maximum height in pixels (default: 1920)
     * @param {number} options.quality - JPEG quality 0-1 (default: 0.82)
     * @param {number} options.maxSizeMB - Maximum output size in MB (default: 1.5)
     * @returns {Promise<File>} - Compressed image file
     */
    async compress(file, options = {}) {
        const {
            maxWidth = 1920,
            maxHeight = 1920,
            quality = 0.9,
            maxSizeMB = 2
        } = options;

        // Skip non-image files
        if (!file.type.startsWith('image/')) {
            return file;
        }

        // Skip GIFs (preserve animation)
        if (file.type === 'image/gif') {
            return file;
        }

        // Skip if already small enough
        if (file.size <= maxSizeMB * 1024 * 1024) {
            // Still try to compress if over 500KB
            if (file.size <= 500 * 1024) {
                return file;
            }
        }

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;

                    // Calculate new dimensions
                    if (width > maxWidth || height > maxHeight) {
                        const ratio = Math.min(maxWidth / width, maxHeight / height);
                        width = Math.round(width * ratio);
                        height = Math.round(height * ratio);
                    }

                    // Create canvas
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');

                    // Draw image
                    ctx.drawImage(img, 0, 0, width, height);

                    // Determine output format
                    let outputType = 'image/jpeg';
                    if (file.type === 'image/png' && file.size < 100 * 1024) {
                        outputType = 'image/png';
                    }

                    // Compress
                    let currentQuality = quality;
                    let blob = null;

                    const tryCompress = () => {
                        canvas.toBlob((b) => {
                            if (!b) {
                                reject(new Error('Compression failed'));
                                return;
                            }

                            // If still too large, reduce quality
                            if (b.size > maxSizeMB * 1024 * 1024 && currentQuality > 0.3) {
                                currentQuality -= 0.1;
                                tryCompress();
                                return;
                            }

                            // Create new file
                            // Preserve original filename, replacing spaces with hyphens
                            const originalName = file.name.replace(/\.[^.]+$/, '').replace(/\s+/g, '-').replace(/[^a-zA-Z0-9\-_]/g, '');
                            const safeName = (originalName || 'image') + '.jpg';
                            
                            const newFile = new File([b], safeName, {
                                type: outputType,
                                lastModified: Date.now()
                            });

                            resolve(newFile);
                        }, outputType, currentQuality);
                    };

                    tryCompress();
                };
                img.onerror = () => reject(new Error('Failed to load image'));
                img.src = e.target.result;
            };
            reader.onerror = () => reject(new Error('Failed to read file'));
            reader.readAsDataURL(file);
        });
    },

    /**
     * Compress multiple files.
     * @param {FileList|File[]} files - Files to compress
     * @param {Object} options - Compression options
     * @returns {Promise<File[]>} - Compressed files
     */
    async compressAll(files, options = {}) {
        const compressed = [];
        for (const file of files) {
            try {
                const result = await this.compress(file, options);
                compressed.push(result);
            } catch (e) {
                console.warn('Compression failed for ' + file.name + ': ' + e.message);
                compressed.push(file); // Fallback to original
            }
        }
        return compressed;
    }
};