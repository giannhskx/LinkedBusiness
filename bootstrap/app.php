<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Αρχικοποίηση ,ρύθμιση της εφαρμογής Laravel
return Application::configure(basePath: dirname(__DIR__))

    // Ορισμός των δρομολογήσεων
    ->withRouting(
        // Δρομολογήσεις για το web
        web: __DIR__.'/../routes/web.php',
        // Δρομολογήσεις για το API
        api: __DIR__.'/../routes/api.php',
        // Δρομολογήσεις για την κονσόλα
        commands: __DIR__.'/../routes/console.php',
        //έλεγχος της υγείας της εφαρμογής, στο URL /up
        health: '/up',
    )

    // Ορισμός middleware
    ->withMiddleware(function (Middleware $middleware) {
        
    })

    // Ορισμός εξαιρέσεων
    ->withExceptions(function (Exceptions $exceptions) {
        // 
    })

    // Δημιουργία και επιστροφή της πλήρως διαμορφωμένης εφαρμογής
    ->create();
