<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LandingPageController extends Controller
{
    /**
     * Display the landing page.
     */
    public function index(): Response
    {
        return Inertia::render('Landing/Index', [
            'hero' => [
                'title' => 'Welcome to Our Platform',
                'subtitle' => 'Build amazing things with our powerful tools',
                'cta_text' => 'Get Started',
                'cta_url' => route('register'),
            ],
            'features' => [
                [
                    'title' => 'Fast & Reliable',
                    'description' => 'Built with performance in mind',
                    'icon' => 'lightning',
                ],
                [
                    'title' => 'Secure',
                    'description' => 'Enterprise-grade security',
                    'icon' => 'shield',
                ],
                [
                    'title' => 'Scalable',
                    'description' => 'Grows with your business',
                    'icon' => 'chart',
                ],
            ],
            'cta' => [
                'title' => 'Ready to get started?',
                'description' => 'Join thousands of satisfied customers',
                'button_text' => 'Sign Up Now',
                'button_url' => route('register'),
            ],
        ]);
    }
}
