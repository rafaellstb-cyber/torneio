</main>

<footer class="text-center text-muted small py-4 mt-3">
  <?php $rodapeTexto = trim(Settings::rodapeTexto()); ?>
  <?php if ($rodapeTexto !== ''): ?>
    <p class="mb-1"><?= e($rodapeTexto) ?></p>
  <?php endif; ?>
  <p class="mb-0">
    <a href="/admin/">área administrativa</a>
  </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
