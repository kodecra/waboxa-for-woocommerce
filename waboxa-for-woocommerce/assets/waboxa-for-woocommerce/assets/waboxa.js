document.addEventListener('DOMContentLoaded', function () {
  const buttons = document.querySelectorAll('.waboxa-btn');
  buttons.forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault();
      e.stopPropagation();

      const phone = btn.dataset.phone;
      let msg = btn.dataset.msg || '';

      msg = msg.replace(/\\\\/g, '\\');
      msg = msg.replace(/\\n/g, '\n');
      msg = msg.replace(/\\/g, '');

      const encoded = encodeURIComponent(msg);
      const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
      const base = isMobile
        ? 'https://api.whatsapp.com/send'
        : 'https://web.whatsapp.com/send';

      const finalUrl = `${base}?phone=${phone}&text=${encoded}`;
      const win = window.open();
      setTimeout(() => {
        win.location = finalUrl;
      }, 100);
    });
  });
});
