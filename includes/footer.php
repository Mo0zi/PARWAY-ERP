    </main> <!-- End page-container -->
  </div> <!-- End main-content -->
</div> <!-- End app-wrapper -->

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  $(document).ready(function() {
    $('#sidebarToggle').on('click', function() {
      $('.app-sidebar').toggleClass('d-block');
    });
  });

  // Reusable Single-Tab WhatsApp Opener & Text Copier
  let waTabRef = null;

  function openWhatsAppInvoice(waUrl) {
    if (waTabRef && !waTabRef.closed) {
      waTabRef.location.href = waUrl;
      waTabRef.focus();
    } else {
      waTabRef = window.open(waUrl, 'parway_whatsapp_tab');
      if (waTabRef) {
        waTabRef.focus();
      }
    }
    return false;
  }

  function copyWhatsAppText(btn) {
    const rawText = $(btn).attr('data-watext') || '';
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(rawText).then(function() {
        showToast('Invoice text copied! You can now paste (Ctrl+V) in WhatsApp.');
      });
    } else {
      const tempInput = document.createElement('textarea');
      tempInput.value = rawText;
      document.body.appendChild(tempInput);
      tempInput.select();
      document.execCommand('copy');
      document.body.removeChild(tempInput);
      showToast('Invoice text copied! You can now paste (Ctrl+V) in WhatsApp.');
    }
  }

  function showToast(message) {
    let $toast = $('#globalToast');
    if ($toast.length === 0) {
      $('body').append(`
        <div id="globalToast" class="toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3 shadow-lg" role="alert" style="z-index:9999;">
          <div class="d-flex">
            <div class="toast-body fw-bold">
              <i class="fa-solid fa-circle-check me-2"></i><span id="globalToastMsg"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
          </div>
        </div>
      `);
      $toast = $('#globalToast');
    }
    $('#globalToastMsg').text(message);
    const toast = new bootstrap.Toast($toast[0], { delay: 3500 });
    toast.show();
  }
</script>

</body>
</html>
