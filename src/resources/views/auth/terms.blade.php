@extends('layouts.minimal')

@section('title', 'Terms of Service - SoVest')

@section('styles')
<style>
    .terms-container {
        max-width: 800px;
        margin: 0 auto;
    }

    .terms-header {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(128, 128, 128, 0.3);
    }

    .terms-header img {
        width: 80px;
        margin-bottom: 1rem;
    }

    .terms-header h1 {
        font-size: 2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .terms-header p {
        color: #6c757d;
        font-size: 0.95rem;
    }

    .terms-document {
        border: 2px dashed rgba(128, 128, 128, 0.4);
        border-radius: 12px;
        padding: 2rem;
        min-height: 400px;
        background: rgba(255, 255, 255, 0.02);
    }

    .dark-mode .terms-document {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(128, 128, 128, 0.3);
    }

    .terms-document-placeholder {
        color: #6c757d;
        text-align: center;
        padding: 4rem 2rem;
    }

    .terms-document-placeholder i {
        font-size: 3rem;
        margin-bottom: 1rem;
        display: block;
        opacity: 0.5;
    }

    .terms-footer {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        border: 1px solid rgba(102, 126, 234, 0.3);
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        margin-top: 2rem;
    }

    .terms-footer p {
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .accept-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        padding: 0.875rem 3rem;
        font-size: 1.1rem;
        font-weight: 600;
        border-radius: 50px;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .accept-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        color: white;
    }

    .accept-btn:active {
        transform: translateY(0);
    }

    .last-updated {
        font-size: 0.85rem;
        color: #6c757d;
        margin-top: 1rem;
    }
</style>
@endsection

@section('content')
<div class="terms-container">
    <div class="terms-header">
        <img src="{{ asset('images/logo.png') }}" alt="SoVest Logo">
        <h1>Terms of Service</h1>
        <p>Please review and accept our terms to continue using SoVest</p>
    </div>

    <!-- Terms Document Container -->
    <div class="terms-document">
        <div class="terms-document-placeholder">
            <i class="bi bi-file-earmark-text"></i>
            <p>Terms of Service document will be displayed here</p>
        </div>
        <!-- Add your terms content here -->
    </div>

    <!-- Accept Terms Footer -->
    <div class="terms-footer">
        <p>By clicking "Accept Terms", you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</p>
        <form action="{{ route('terms.accept') }}" method="POST">
            @csrf
            <button type="submit" class="btn accept-btn">
                <i class="bi bi-check-lg me-2"></i>Accept Terms
            </button>
        </form>
        <p class="last-updated">Last updated: January 2025</p>
    </div>
</div>
@endsection
