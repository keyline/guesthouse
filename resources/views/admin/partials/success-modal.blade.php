<div
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 opacity-0 transition-opacity duration-300"
    style="pointer-events: none;"
    data-success-modal
>
    <div
        class="relative mx-4 w-full max-w-md scale-95 rounded-2xl bg-white p-8 shadow-2xl transition-transform duration-300"
        style="position: relative;"
        data-success-modal-content
    >
        <button
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-300 text-white transition hover:bg-slate-400"
            style="position: absolute; top: 16px; right: 16px; z-index: 20; cursor: pointer;"
            aria-label="Close success modal"
            data-success-modal-close
        >
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
            </svg>
        </button>

        <div class="mb-4 flex items-center justify-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                <svg class="h-8 w-8 text-green-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>

        <h3 class="mb-2 text-center text-xl font-bold text-slate-900">Success!</h3>
        <p class="mb-6 text-center text-slate-600">{{ $message }}</p>

        <button
            type="button"
            class="w-full rounded-lg bg-green-600 px-4 py-2 font-bold text-white transition hover:bg-green-700"
            data-success-modal-close
        >
            Got it
        </button>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-success-modal]').forEach(function(modal) {
                const modalContent = modal.querySelector('[data-success-modal-content]');

                if (!modalContent) {
                    return;
                }

                function closeSuccessModal() {
                    modal.classList.add('opacity-0');
                    modal.classList.remove('opacity-100');
                    modalContent.classList.remove('scale-100');
                    modalContent.classList.add('scale-95');

                    setTimeout(function() {
                        modal.style.pointerEvents = 'none';
                        modal.style.display = 'none';
                    }, 300);
                }

                setTimeout(function() {
                    modal.style.pointerEvents = 'auto';
                    modal.classList.remove('opacity-0');
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95');
                    modalContent.classList.add('scale-100');
                }, 100);

                modal.querySelectorAll('[data-success-modal-close]').forEach(function(button) {
                    button.addEventListener('click', function(event) {
                        event.preventDefault();
                        closeSuccessModal();
                    });
                });

                modal.addEventListener('click', function(event) {
                    if (!modalContent.contains(event.target)) {
                        closeSuccessModal();
                    }
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && modal.style.display !== 'none') {
                        closeSuccessModal();
                    }
                });
            });
        });
    </script>
@endonce
