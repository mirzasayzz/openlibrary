    </div><!-- End .container -->
    
    <footer class="bg-white py-4 mt-5 border-top">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="text-muted">An online repository of free books.</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none">Home</a></li>
                        <li><a href="search.php" class="text-decoration-none">Browse Books</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="my-books.php" class="text-decoration-none">My Books</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="text-decoration-none">Login</a></li>
                            <li><a href="register.php" class="text-decoration-none">Sign Up</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>GitHub</h6>
                    <div class="d-flex gap-3">
                        <a href="https://github.com/mirzasayzz" class="text-decoration-none" target="_blank"><i class="fab fa-github fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between">
                <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 