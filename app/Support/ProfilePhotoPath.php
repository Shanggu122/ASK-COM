<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProfilePhotoPath
{
    /**
     * Normalize any stored profile picture value to a public-disk relative path (profile_pictures/...).
     */
    public static function normalize(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $clean = trim($path);
        if ($clean === "") {
            return null;
        }

        // Strip query strings to avoid cache-busting suffixes blocking detection.
        $clean = explode("?", $clean, 2)[0];

        // Normalise directory separators for easier pattern checks.
        $clean = str_replace("\\", "/", $clean);

        // If an absolute URL is provided, strip the scheme + host portion.
        if (preg_match('#^https?://[^/]+/(.+)$#i', $clean, $matches)) {
            $clean = $matches[1];
        }

        // Common Laravel storage prefixes.
        if (preg_match('#^storage/(?:app/)?public/(.+)$#i', $clean, $matches)) {
            $clean = $matches[1];
        }

        // Remove any leading public/ prefix that might remain.
        if (preg_match('#^public/(.+)$#i', $clean, $matches)) {
            $clean = $matches[1];
        }

        // If path contains /profile_pictures/, strip anything before it.
        if (preg_match('#profile_pictures/.+$#i', $clean, $matches)) {
            $clean = $matches[0];
        }

        // Ensure consistent forward slashes.
        $clean = str_replace("\\", "/", $clean);
        $clean = ltrim($clean, "/");

        return $clean !== "" ? $clean : null;
    }

    /**
     * Build a browser-accessible URL for a profile photo, falling back to the default avatar.
     */
    public static function url(?string $path): string
    {
        $normalized = self::normalize($path);
        if (!$normalized) {
            return asset("images/dprof.jpg");
        }

        $disk = config("filesystems.profile_photos_disk", "public");
        /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = Storage::disk($disk);

        $relativeUrl = url("/storage/" . ltrim($normalized, "/"));

        if ($storage->exists($normalized)) {
            $generated = $storage->url($normalized);
            return self::shouldPreferRequestHost($generated) ? $relativeUrl : $generated;
        }

        // Fallback to the conventional /storage symlink even if the existence check failed (e.g. missing link on deploy).
        return $relativeUrl;
    }

    private static function shouldPreferRequestHost(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $host = strtolower($host);
        if (in_array($host, ["127.0.0.1", "localhost", "::1"], true)) {
            return true;
        }

        if (app()->bound("request")) {
            $requestHost = strtolower((string) request()->getHost());
            if ($requestHost !== "" && $requestHost !== $host) {
                return true;
            }
        }

        return false;
    }
}
