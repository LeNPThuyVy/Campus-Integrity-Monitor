
</main>
</div>
<script>
(() => {
  const routes = {
    'tong-quan': 'home.php',
    'vi-pham': 'list_vipham.php',
    'thong-ke-bao-cao': 'reporting.php',
    'sinh-vien': 'ql_sinhvien.php',
    'danh-muc': 'danh_muc.php',
    'tai-khoan-phan-quyen': 'phanquyen.php',
  };
  const navigation = document.querySelector('nav[data-active-classes]');
  if (!navigation) return;

  const activeClasses = ['bg-primary-container', 'text-on-primary', 'font-label-lg', 'shadow-sm'];
  const inactiveClasses = [
    'text-on-surface-variant',
    'hover:bg-surface-container',
    'hover:text-on-surface',
  ];
  const currentPage = window.location.pathname.split('/').pop();

  navigation.querySelectorAll('a[data-path]').forEach((link) => {
    const destination = routes[link.dataset.path];
    if (!destination) {
      link.removeAttribute('href');
      link.setAttribute('aria-disabled', 'true');
      link.title = 'Trang này chưa có trong website.';
      link.classList.add('opacity-50', 'cursor-not-allowed');
      return;
    }

    link.href = destination;
    const isCurrentPage = destination === currentPage;
    link.classList.remove(...(isCurrentPage ? inactiveClasses : activeClasses));
    link.classList.add(...(isCurrentPage ? activeClasses : inactiveClasses));
    if (isCurrentPage) {
      link.setAttribute('aria-current', 'page');
    } else {
      link.removeAttribute('aria-current');
    }
  });
})();
</script>
</body>
</html>
