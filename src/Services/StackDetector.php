<?php

namespace Mustafa\Lara2fa\Services;

use Illuminate\Support\Facades\File;

class StackDetector
{
    public static function detectFrontendStack(): string
    {
        // Check for Inertia.js (React)
        if (File::exists(base_path('package.json')) && str_contains(File::get(base_path('package.json')), '"@inertiajs/react"')) {
            return 'react';
        }

        // Check for Inertia.js (Vue)
        if (File::exists(base_path('package.json')) && str_contains(File::get(base_path('package.json')), '"@inertiajs/vue3"')) {
            return 'vue';
        }

        // Check for Livewire
        if (File::exists(base_path('composer.json')) && str_contains(File::get(base_path('composer.json')), '"livewire/livewire"')) {
            return 'livewire';
        }

        // Default to Blade if no specific frontend stack is detected
        return 'blade';
    }
}