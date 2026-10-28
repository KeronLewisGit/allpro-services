<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ThanksController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/client-services', [PageController::class, 'clients'])->name('clients');

Route::get('/quote', [QuoteController::class, 'create'])->name('quote.create');
Route::get('/careers', [CareerController::class, 'index'])->name('careers.index');
Route::get('/careers/apply', [CareerController::class, 'create'])->name('careers.apply');
Route::get('/careers/{job}', [CareerController::class, 'show'])->name('careers.show');

Route::middleware('throttle:forms')->group(function () {
    Route::post('/quote', [QuoteController::class, 'store'])->name('quote.store');
    Route::post('/careers/apply', [CareerController::class, 'store'])->name('careers.store');
    Route::post('/contact', [EnquiryController::class, 'store'])->name('contact.store');
});
Route::get('/thanks', ThanksController::class)->name('thanks');

// Signed links only: each client site's private service desk.
Route::middleware('signed')->group(function () {
    Route::get('/service-desk/{account}/{key}', [PortalController::class, 'show'])->name('portal.show');
    Route::post('/service-desk/{account}/{key}', [PortalController::class, 'store'])->middleware('throttle:forms')->name('portal.store');
});

Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap', [PageController::class, 'sitemapPage'])->name('sitemap.page');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

// Addresses from the old WordPress site (allpro.ie, 2016) that may still be bookmarked.
foreach ((array) config('allpro.legacy_redirects', []) as $from => $to) {
    Route::permanentRedirect($from, $to);
}

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('area:servicedesk')->group(function () {
        Route::get('/tickets', [Admin\TicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/export', [Admin\TicketController::class, 'export'])->name('tickets.export');
        Route::get('/tickets/{ticket}', [Admin\TicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [Admin\TicketController::class, 'update'])->name('tickets.update');
        Route::post('/tickets/{ticket}/notes', [Admin\TicketController::class, 'note'])->name('tickets.note');
        Route::get('/tickets/{ticket}/photos/{photo}', [Admin\TicketController::class, 'photo'])->name('tickets.photo');
        Route::get('/clients', [Admin\ClientAccountController::class, 'index'])->name('accounts.index');
        Route::post('/clients', [Admin\ClientAccountController::class, 'store'])->name('accounts.store');
        Route::get('/clients/{account}', [Admin\ClientAccountController::class, 'show'])->name('accounts.show');
        Route::patch('/clients/{account}', [Admin\ClientAccountController::class, 'update'])->name('accounts.update');
        Route::post('/clients/{account}/share', [Admin\ClientAccountController::class, 'share'])->name('accounts.share');
        Route::post('/clients/{account}/revoke', [Admin\ClientAccountController::class, 'revoke'])->name('accounts.revoke');
    });

    Route::middleware('area:quotes')->group(function () {
        Route::get('/quotes', [Admin\QuoteController::class, 'index'])->name('quotes.index');
        Route::get('/quotes/export', [Admin\QuoteController::class, 'export'])->name('quotes.export');
        Route::get('/quotes/{quote}', [Admin\QuoteController::class, 'show'])->name('quotes.show');
        Route::patch('/quotes/{quote}', [Admin\QuoteController::class, 'update'])->name('quotes.update');
        Route::post('/quotes/{quote}/notes', [Admin\QuoteController::class, 'note'])->name('quotes.note');
        Route::get('/quotes/{quote}/files/{file}', [Admin\QuoteController::class, 'file'])->name('quotes.file');
    });

    Route::middleware('area:careers')->group(function () {
        Route::get('/jobs', [Admin\JobPostingController::class, 'index'])->name('jobs.index');
        Route::get('/jobs/create', [Admin\JobPostingController::class, 'create'])->name('jobs.create');
        Route::post('/jobs', [Admin\JobPostingController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/{job}/edit', [Admin\JobPostingController::class, 'edit'])->name('jobs.edit');
        Route::put('/jobs/{job}', [Admin\JobPostingController::class, 'update'])->name('jobs.update');
        Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/export', [Admin\ApplicationController::class, 'export'])->name('applications.export');
        Route::get('/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/applications/{application}', [Admin\ApplicationController::class, 'update'])->name('applications.update');
        Route::patch('/applications/{application}/checks', [Admin\ApplicationController::class, 'checks'])->name('applications.checks');
        Route::post('/applications/{application}/staff', [Admin\ApplicationController::class, 'convert'])->middleware('area:staff')->name('applications.convert');
        Route::post('/applications/{application}/notes', [Admin\ApplicationController::class, 'note'])->name('applications.note');
        Route::get('/applications/{application}/cv', [Admin\ApplicationController::class, 'cv'])->name('applications.cv');
    });

    Route::middleware('area:staff')->group(function () {
        Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
        Route::get('/staff/export', [Admin\StaffController::class, 'export'])->name('staff.export');
        Route::get('/staff/create', [Admin\StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}', [Admin\StaffController::class, 'show'])->name('staff.show');
        Route::get('/staff/{staff}/edit', [Admin\StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [Admin\StaffController::class, 'update'])->name('staff.update');
        Route::post('/staff/{staff}/notes', [Admin\StaffController::class, 'note'])->name('staff.note');
    });

    Route::middleware('area:enquiries')->group(function () {
        Route::get('/enquiries', [Admin\EnquiryController::class, 'index'])->name('enquiries.index');
        Route::get('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'show'])->name('enquiries.show');
        Route::patch('/enquiries/{enquiry}', [Admin\EnquiryController::class, 'update'])->name('enquiries.update');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
    });
});
