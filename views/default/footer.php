    <!-- Footer -->
    <footer class="mt-5 py-3 bg-light border-top">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6 text-muted small">
                    &copy; <?= date('Y') ?> Inventaris Kantor V3. All rights reserved.
                </div>
                <div class="col-md-6 text-end text-muted small">
                    Version 3.0 | <a href="#" class="text-decoration-none">Documentation</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="/inventaris/assets/js/main.js"></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/inventaris/service-worker.js')
                .then(function(registration) {
                    console.log('ServiceWorker registration successful');
                })
                .catch(function(err) {
                    console.log('ServiceWorker registration failed: ', err);
                });
        });
    }
    </script>
    
    <!-- Additional JS -->
    <?php if (isset($additional_js)): ?>
        <?= $additional_js ?>
    <?php endif; ?>
    
    <!-- Page-specific scripts can be added here -->
</body>
</html>
