<?php

namespace App\Models\Concerns;

use App\Support\ProfilePhotoPath;

trait HasProfilePhoto
{
    public function getProfilePhotoUrlAttribute(): string
    {
        $path = $this->profile_picture;
        $normalized = ProfilePhotoPath::normalize($path);
        if ($normalized !== $path) {
            $this->attributes["profile_picture"] = $normalized;
        }

        return ProfilePhotoPath::url($normalized);
    }

    public function setProfilePictureAttribute($file)
    {
        // Allow assigning an UploadedFile directly
        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $stored = $file->store("profile_pictures", "public");
            $this->attributes["profile_picture"] = $stored; // stored as relative path
        } elseif ($file === null) {
            $this->attributes["profile_picture"] = null;
        } else {
            $this->attributes["profile_picture"] = ProfilePhotoPath::normalize((string) $file);
        }
    }
}
