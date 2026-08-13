@extends('frontend.layouts.app')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-xl p-8 max-w-md text-center border border-green-500">
            
            @if(session('success'))
                <div class="mb-6 text-green-700 dark:text-green-400 text-lg font-semibold">
                    ✅ {{ session('success') }}
                </div>
            @else
                <div class="mb-6 text-green-700 dark:text-green-400 text-lg font-semibold">
                    ✅ Operation completed successfully!
                </div>
            @endif
            <a href="{{ url('/') }}" class="inline-block mt-4 px-6 py-2 text-dark bg-green-600 hover:bg-green-700 rounded-lg shadow transition-all duration-200">
                Go to Home Page
            </a>
        </div>
    </div>
@endsection
