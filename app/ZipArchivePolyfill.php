<?php

/**
 * Polyfill for ZipArchive constants when the zip extension is not available.
 * This allows packages like spatie/laravel-backup to load their config
 * without the zip extension being installed.
 */
if (!class_exists('ZipArchive')) {
    class ZipArchive
    {
        public const int CM_DEFAULT = -1;
        public const int CM_STORE = 0;
        public const int CM_DEFLATE = 8;
        public const int CM_BZIP2 = 12;
        public const int CM_LZMA = 14;
        public const int CM_XZ = 95;
        public const int CM_ZSTD = 93;
        
        // Dummy methods to satisfy type hints
        public function open(string $filename, int $flags = 0): bool { return false; }
        public function close(): bool { return true; }
        public function addFile(string $filename, string $localname = ''): bool { return false; }
        public function addFromString(string $localname, string $contents): bool { return false; }
        public function extractTo(string $destination, array|string|null $entries = null): bool { return false; }
        public function setCompressionIndex(int $index, int $method, int $level = -1): bool { return false; }
        public function setCompressionName(string $name, int $method, int $level = -1): bool { return false; }
        public function getStatusString(): string { return ''; }
        public function getArchiveComment(int $flags = 0): string|false { return false; }
        public function setArchiveComment(string $comment): bool { return false; }
        public function getCommentIndex(int $index, int $flags = 0): string|false { return false; }
        public function getCommentName(string $name, int $flags = 0): string|false { return false; }
        public function setCommentIndex(int $index, string $comment): bool { return false; }
        public function setCommentName(string $name, string $comment): bool { return false; }
        public function deleteIndex(int $index): bool { return false; }
        public function deleteName(string $name): bool { return false; }
        public function renameIndex(int $index, string $newname): bool { return false; }
        public function renameName(string $name, string $newname): bool { return false; }
        public function setPassword(string $password): bool { return false; }
        public function setEncryptionIndex(int $index, int $method, string $password): bool { return false; }
        public function setEncryptionName(string $name, int $method, string $password): bool { return false; }
        public function getArchiveFlag(int $flag = 0, int $flags = 0): int { return 0; }
        public function registerProgressCallback(float $rate, callable $callback): bool { return false; }
        public function registerCancelCallback(callable $callback): bool { return false; }
    }
}
