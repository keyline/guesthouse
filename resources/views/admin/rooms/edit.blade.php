@extends('admin.layouts.app')

@section('title', 'Edit Room')
@section('eyebrow', 'Room Inventory')
@section('page-title', 'Edit Room '.$room->room_number)

@section('header-actions')
    <a href="{{ route('admin.rooms.index') }}" class="inline-flex h-10 items-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm">Back to Rooms</a>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.rooms.update', $room) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('admin.rooms._form')
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.rooms.index') }}" class="inline-flex h-11 items-center rounded-lg border border-slate-300 bg-white px-5 text-sm font-bold text-slate-700">Cancel</a>
            <button class="h-11 rounded-lg bg-sky-600 px-5 py-2 text-sm font-bold text-white hover:bg-sky-700 transition">Save Changes</button>
        </div>
    </form>

    @if (session('status'))
        <div id="success_modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 opacity-0 transition-opacity duration-300" style="pointer-events: none;">
            <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 scale-95 transition-transform duration-300 relative" id="modal_content" style="position: relative;">
                <!-- Close Button (X) -->
                <button type="button" id="success_modal_close" class="w-8 h-8 rounded-full bg-slate-300 hover:bg-slate-400 text-white transition flex items-center justify-center z-10" style="position: absolute; top: 16px; right: 16px; cursor: pointer;" aria-label="Close success modal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>

                <!-- Success Icon -->
                <div class="flex items-center justify-center mb-4">
                    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center animate-pulse">
                        <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Content -->
                <h3 class="text-xl font-bold text-center text-slate-900 mb-2">Success!</h3>
                <p class="text-center text-slate-600 mb-6">{{ session('status') }}</p>

                <!-- Auto-close progress bar -->
                <div class="mb-4 h-1 bg-slate-200 rounded-full overflow-hidden">
                    <div id="progress_bar" class="h-full bg-green-600 transition-all" style="width: 100%;"></div>
                </div>

                <!-- Action Button -->
                <button type="button" onclick="closeModal()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                    Got it
                </button>
            </div>
        </div>

        <script>
            let closeTimer = null;
            let progressInterval = null;

            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('success_modal');
                const modalContent = document.getElementById('modal_content');
                const closeButton = document.getElementById('success_modal_close');

                if (!modal || !modalContent) {
                    return;
                }

                if (closeButton) {
                    closeButton.addEventListener('click', closeModal);
                }

                // Show modal with animation
                setTimeout(() => {
                    modal.style.pointerEvents = 'auto';
                    modal.classList.remove('opacity-0');
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95');
                    modalContent.classList.add('scale-100');
                }, 100);

                // Click outside modal to close
                modal.addEventListener('click', function(e) {
                    if (!modalContent.contains(e.target)) {
                        closeModal();
                    }
                });

                // ESC key to close
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeModal();
                    }
                });

                // Start auto-close timer and progress bar
                startAutoClose();
            });

            function closeModal() {
                clearTimeout(closeTimer);
                clearInterval(progressInterval);

                const modal = document.getElementById('success_modal');
                const modalContent = document.getElementById('modal_content');

                if (!modal || !modalContent) {
                    return;
                }

                modal.classList.add('opacity-0');
                modalContent.classList.remove('scale-100');
                modalContent.classList.add('scale-95');

                setTimeout(() => {
                    modal.style.pointerEvents = 'none';
                    modal.style.display = 'none';
                }, 300);
            }

            function startAutoClose() {
                let timeLeft = 3000;
                const progressBar = document.getElementById('progress_bar');

                progressInterval = setInterval(() => {
                    timeLeft -= 30;
                    const percentage = (timeLeft / 3000) * 100;
                    progressBar.style.width = percentage + '%';
                }, 30);

                closeTimer = setTimeout(() => {
                    closeModal();
                }, 3000);
            }
        </script>
    @endif
@endsection
