/* Animasi masuk halus & cepat untuk ASIKKEKKU (arah "A").
   Reveal bertahap kartu/baris via IntersectionObserver. Count-up angka
   dashboard sudah ditangani di views/dashboard/index.php. */
(function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  function markReveal() {
    var sel = [
      '.content-wrapper .card',
      '.content-wrapper .flat-stat-box',
      '.dash-panels .ew-list > li',
      '.dash-panels #todoList > li',
      '.timeline > div'
    ].join(',');
    var i = 0;
    document.querySelectorAll(sel).forEach(function (el) {
      if (el.classList.contains('asik-reveal')) return;
      el.classList.add('asik-reveal');
      el.style.setProperty('--asik-delay', (Math.min(i, 8) * 45) + 'ms');
      i++;
    });
  }

  var io = ('IntersectionObserver' in window) ? new IntersectionObserver(function (entries) {
    entries.forEach(function (e) {
      if (!e.isIntersecting) return;
      var el = e.target;
      el.style.animationDelay = el.style.getPropertyValue('--asik-delay') || '0ms';
      el.classList.add('in');
      io.unobserve(el);
    });
  }, { threshold: 0.06, rootMargin: '0px 0px -4% 0px' }) : null;

  function scan() {
    markReveal();
    document.querySelectorAll('.asik-reveal:not(.in)').forEach(function (el) {
      if (io) io.observe(el); else el.classList.add('in');
    });
  }

  if (document.readyState !== 'loading') scan();
  else document.addEventListener('DOMContentLoaded', scan);

  // DataTables menggambar ulang tbody -> reveal baris baru juga.
  document.addEventListener('draw.dt', function () { setTimeout(scan, 30); });
  if (window.jQuery) window.jQuery(document).on('draw.dt', function () { setTimeout(scan, 30); });
})();
