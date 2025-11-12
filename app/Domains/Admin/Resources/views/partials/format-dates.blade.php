<script>
(function(){
  function fmt(el){
    const iso = el.getAttribute('datetime');
    if(!iso) return;
    const d = new Date(iso);
    if (isNaN(d)) return;
    const lang = document.documentElement?.lang || 'en';
    const opts = { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' };
    el.textContent = new Intl.DateTimeFormat(lang, opts).format(d);
    el.dataset.fmt = '1';
  }
  function run(root){
    (root || document).querySelectorAll('.js-dt:not([data-fmt="1"])').forEach(fmt);
  }
  const mo = new MutationObserver(muts => {
    for (const m of muts){
      if (m.type === 'childList') {
        m.addedNodes.forEach(n => {
          if (n.nodeType === 1) {
            if (n.matches && n.matches('.js-dt')) fmt(n);
            run(n);
          }
        });
      }
    }
  });
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { run(); mo.observe(document.body, { childList:true, subtree:true }); });
  } else {
    run();
    mo.observe(document.body, { childList:true, subtree:true });
  }
  document.addEventListener('livewire:navigated', () => run(), { passive: true });
})();
</script>
