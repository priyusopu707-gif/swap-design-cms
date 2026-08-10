<?php
/**
 * Swap Design - Admin Footer Component
 *
 * Closes the admin layout: main, sidebar wrapper, and
 * loads global admin JavaScript.
 *
 * @package SwapDesign
 */

defined('SWAP_ROOT') or die('Access denied');
?>

            </main><!-- end .admin-content -->
        </div><!-- end .admin-main -->
    </div><!-- end .admin-layout -->

    <!-- Admin JavaScript -->
    <script src="/admin/assets/js/admin.js" defer></script>

    <!-- Inline CSRF helper for AJAX requests -->
    <script>
        window.csrfToken = '<?php echo esc(csrfToken()); ?>';
    </script>

</body>
</html>
