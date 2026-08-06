{{-- Shared behaviour for the written guides: theme toggle, copy buttons,
     scroll progress bar, and active-section highlighting in the TOC. --}}
<script>
  // theme toggle (root gets data-theme; overrides the media query both ways)
  (function () {
    var tt = document.getElementById('tt');
    tt.addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme');
      if (!cur) { cur = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
      document.documentElement.setAttribute('data-theme', cur === 'dark' ? 'light' : 'dark');
    });
  })();

  // copy buttons
  document.querySelectorAll('.console').forEach(function (box) {
    var btn = box.querySelector('.copybtn'); if (!btn) return;
    var code = box.querySelector('code');
    btn.addEventListener('click', function () {
      var text = code.innerText.replace(/^\$ /gm, '');
      navigator.clipboard.writeText(text).then(function () {
        btn.textContent = 'Copied ✓'; btn.classList.add('done');
        setTimeout(function () { btn.textContent = 'Copy'; btn.classList.remove('done'); }, 1600);
      });
    });
  });

  // scroll progress + active TOC
  var prog = document.getElementById('progress');
  var links = Array.from(document.querySelectorAll('nav.toc a'));
  var map = {}; links.forEach(function (a) { map[a.getAttribute('href').slice(1)] = a; });
  function onScroll() {
    var h = document.documentElement;
    var pct = (h.scrollTop) / (h.scrollHeight - h.clientHeight) * 100;
    prog.style.width = Math.min(100, Math.max(0, pct)) + '%';
  }
  document.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting && map[e.target.id]) {
          links.forEach(function (l) { l.classList.remove('active'); });
          map[e.target.id].classList.add('active');
        }
      });
    }, { rootMargin: '-20% 0px -70% 0px' });
    document.querySelectorAll('section[id]').forEach(function (s) { io.observe(s); });
  }
</script>
