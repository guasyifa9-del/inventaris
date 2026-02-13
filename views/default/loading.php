<!-- Loading Spinner Overlay -->
<div id="loading-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <span class="text-light ms-3 fs-5">Memuat...</span>
    </div>
</div>

<script>
// Show loading
function showLoading() {
    document.getElementById('loading-overlay').classList.remove('d-none');
}

// Hide loading
function hideLoading() {
    document.getElementById('loading-overlay').classList.add('d-none');
}

// Auto hide on page load
window.addEventListener('load', function() {
    hideLoading();
});
</script>
