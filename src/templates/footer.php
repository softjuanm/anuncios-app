</div><!-- /.container -->
</main>

<footer class="page-footer blue-grey darken-4">
    <div class="container">
        <div class="row" style="margin-bottom:0; padding: 20px 0;">
            <div class="col s12 center-align white-text">
                <p style="margin:0">
                    &copy; <?= date('Y') ?> <?= h(APP_NAME) ?> —
                Universidad de Manizales - Juan Manuel Pinzón
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Materialize JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        M.AutoInit();
    });
</script>
</body>
</html>
