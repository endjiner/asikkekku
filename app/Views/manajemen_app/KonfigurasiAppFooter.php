<script type="text/javascript">
	$( function() {
		// $( ".table-flow tbody" ).sortable({ items: "> tr:not(:last)" });
		$( ".table-flow tbody" ).sortable();
	} );
    // Simpan setelan aplikasi (judul/deskripsi/tautan/logo) -> pop-up lalu reload.
    $("form.UserModify").submit(function(e) {
      e.preventDefault();
      var form = this;
      $.ajax({
        type: "POST",
        url: form.action,
        data: new FormData(form),
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(data) {
          show_toast('success', (data && data.msg) || 'Konfigurasi aplikasi tersimpan.');
          setTimeout(function () { location.reload(); }, 700);
        },
        error: function () { show_toast('error', 'Gagal menyimpan konfigurasi aplikasi. Coba lagi.'); }
      });
    });

    $("form.FlowModify").submit(function(e) {
      e.preventDefault();
      var form = $(this);
      form.find(':checkbox:not(:checked)').attr('value', '0').prop('checked', true);
      var action_url = form.attr('action');
      $.ajax({
          type: "POST",
          url: action_url,
          data: form.serialize(),
          dataType: 'json',
          success: function(data) {
            show_toast('success', 'Urutan alur tersimpan.');
            setTimeout(function () { location.reload(); }, 700);
					},
					error: function () {
						show_toast('error', 'Gagal menyimpan konfigurasi aplikasi. Coba lagi.');
          }
      });
    });

	BigLogoFile.onchange = evt => {
		const [file] = BigLogoFile.files
		if (file) {
			BigLogoFilePreview.src = URL.createObjectURL(file)
		}
	}
	SmallLogoFile.onchange = evt => {
		const [file] = SmallLogoFile.files
		if (file) {
			SmallLogoFilePreview.src = URL.createObjectURL(file)
		}
	}
	CoverLogoFile.onchange = evt => {
		const [file] = CoverLogoFile.files
		if (file) {
			CoverLogoFilePreview.src = URL.createObjectURL(file)
		}
	}
</script>