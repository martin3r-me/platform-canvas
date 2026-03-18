<?php

use Platform\Canvas\Http\Controllers\CanvasPdfController;
use Platform\Canvas\Livewire\Canvas\Index;
use Platform\Canvas\Livewire\Canvas\Show;

Route::get('/', Index::class)->name('canvas.dashboard');
Route::get('/canvases', Index::class)->name('canvas.canvases.index');
Route::get('/canvases/{canvas}', Show::class)->name('canvas.canvases.show');
Route::get('/canvases/{canvas}/pdf', CanvasPdfController::class)->name('canvas.canvases.pdf');
