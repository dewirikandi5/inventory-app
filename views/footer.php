<?php
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Forbidden');
}
?>
        </main>
        <footer class="footer">
            <?= e(APP_NAME) ?> &middot; Mini Project 3 &ndash; Pemrograman Web 4
        </footer>
    </div>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
