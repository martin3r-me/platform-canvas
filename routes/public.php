<?php

use Platform\Canvas\Livewire\Canvas\PublicShow;

Route::get('/public/{token}', PublicShow::class)
    ->name('canvas.public.show');
