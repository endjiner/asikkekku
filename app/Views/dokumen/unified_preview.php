<?php
/**
 * Unified In-Page Document Preview & Reader
 * Menampilkan seluruh dokumen pencairan (in-app generated + uploaded files)
 * dalam 1 layar berkelanjutan dengan TOC Navigator, Scrollspy, Reorder Mode, & Quick Actions.
 *
 * Variabel yang diterima:
 *   $KegiatanID : int
 *   $kegiatan   : array
 *   $items      : array of document items (in-app & upload)
 *   $total      : int
 *   $userPos    : string
 */
$base = base_url();
$AppConfig = isset($AppConfig) ? $AppConfig : [];
$canEdit = in_array($userPos, ['PJ-Kegiatan', 'SuperAdmin', 'PPK-Staff', 'SPM', 'PPSPM', 'Verifikator'], true);
?>

<div class="unidoc-wrapper" id="unidoc-app">
  <!-- Top Control Bar -->
  <div class="unidoc-topbar">
    <div class="unidoc-topbar-left">
      <div class="unidoc-title-badge">
        <span class="unidoc-badge-dot"></span>
        <strong>Bundle Dokumen Pencairan</strong>
        <span class="badge badge-light ml-2" id="unidoc-count-badge"><?= (int)$total ?> Dokumen</span>
      </div>
    </div>
    <div class="unidoc-topbar-actions">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-toggle-reorder" title="Atur urutan dokumen">
        <i class="fas fa-sort-amount-down-alt mr-1"></i> <span id="lbl-reorder">Atur Urutan</span>
      </button>
      <a href="<?= $base ?>dokumen/paketCetak/<?= $KegiatanID ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak semua dokumen sekaligus">
        <i class="fas fa-print mr-1"></i> Cetak Seluruh Paket
      </a>
      <a href="<?= $base ?>dokumen/paketUnduh/<?= $KegiatanID ?>" target="_blank" class="btn btn-sm btn-primary" title="Unduh semua dokumen sebagai 1 PDF">
        <i class="fas fa-file-pdf mr-1"></i> Unduh Paket PDF
      </a>
    </div>
  </div>

  <div class="unidoc-layout">
    <!-- Left Sticky Sidebar: In-Page Navigation / Reorder Panel -->
    <aside class="unidoc-sidebar" id="unidoc-sidebar">
      <div class="unidoc-sidebar-header">
        <div class="input-group input-group-sm mb-2">
          <div class="input-group-prepend">
            <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
          </div>
          <input type="text" id="unidoc-search" class="form-control border-left-0" placeholder="Cari nama dokumen...">
        </div>
      </div>

      <!-- Reorder Mode Notice (hidden by default) -->
      <div id="reorder-banner" class="alert alert-info py-2 px-3 text-xs mb-2 d-none">
        <i class="fas fa-info-circle mr-1"></i> Klik tanda panah untuk menaikkan/menurunkan posisi dokumen.
      </div>

      <!-- TOC List -->
      <nav class="unidoc-nav" id="unidoc-nav-list">
        <?php if (empty($items)): ?>
          <div class="p-3 text-muted text-sm text-center">Belum ada dokumen yang tersedia.</div>
        <?php else: ?>
          <?php foreach ($items as $idx => $item): ?>
            <div class="unidoc-nav-item <?= $idx === 0 ? 'active' : '' ?>" data-key="<?= htmlspecialchars($item['key'], ENT_QUOTES) ?>" data-target="#doc-sec-<?= $idx ?>">
              <div class="unidoc-nav-left">
                <span class="unidoc-nav-num"><?= $idx + 1 ?></span>
                <div class="unidoc-nav-info">
                  <div class="unidoc-nav-title"><?= htmlspecialchars($item['short_title']) ?></div>
                  <?php if (!empty($item['sub_title'])): ?>
                    <div class="unidoc-nav-sub"><?= htmlspecialchars($item['sub_title']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Reorder Buttons -->
              <div class="unidoc-reorder-btns d-none">
                <button type="button" class="btn-reorder btn-move-up" title="Geser ke atas" <?= $idx === 0 ? 'disabled' : '' ?>>
                  <i class="fas fa-chevron-up"></i>
                </button>
                <button type="button" class="btn-reorder btn-move-down" title="Geser ke bawah" <?= $idx === count($items) - 1 ? 'disabled' : '' ?>>
                  <i class="fas fa-chevron-down"></i>
                </button>
              </div>

              <!-- Type Badge -->
              <div class="unidoc-nav-badge">
                <?php if ($item['type'] === 'inapp'): ?>
                  <span class="badge badge-soft-primary" title="Dokumen Sistem In-App">Otomatis</span>
                <?php else: ?>
                  <span class="badge badge-soft-success" title="File PDF Diunggah">Upload</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </nav>

      <?php if (!empty($items)): ?>
        <div class="unidoc-sidebar-footer" id="reorder-save-footer" class="d-none">
          <button type="button" class="btn btn-sm btn-success btn-block" id="btn-save-order">
            <i class="fas fa-check mr-1"></i> Simpan Urutan Dokumen
          </button>
        </div>
      <?php endif; ?>
    </aside>

    <!-- Main Content: Continuous Document Stream -->
    <main class="unidoc-main" id="unidoc-stream">
      <?php if (empty($items)): ?>
        <div class="unidoc-empty-state">
          <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
          <h5>Belum ada dokumen yang dihasilkan</h5>
          <p class="text-muted text-sm">Pastikan data pengajuan & pelaksana telah terisi dengan benar.</p>
        </div>
      <?php else: ?>
        <?php foreach ($items as $idx => $item): ?>
          <section class="unidoc-section" id="doc-sec-<?= $idx ?>" data-key="<?= htmlspecialchars($item['key'], ENT_QUOTES) ?>">
            <!-- Section Header -->
            <div class="unidoc-section-header">
              <div class="d-flex align-items-center gap-2">
                <span class="unidoc-sec-number"><?= $idx + 1 ?></span>
                <div>
                  <h5 class="unidoc-sec-title mb-0"><?= htmlspecialchars($item['nama']) ?></h5>
                  <div class="text-xs text-muted">
                    <?= $item['type'] === 'inapp' ? 'Dokumen Sistem Dihasilkan Otomatis' : 'Berkas Eksternal / PDF Terunggah' ?>
                  </div>
                </div>
              </div>
              <div class="unidoc-sec-actions">
                <?php if ($item['type'] === 'inapp'): ?>
                  <a href="<?= $item['url_cetak'] ?>" target="_blank" class="btn btn-xs btn-outline-secondary">
                    <i class="fas fa-print mr-1"></i> Cetak
                  </a>
                  <a href="<?= $item['url_unduh'] ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                    <i class="fas fa-download mr-1"></i> PDF
                  </a>
                <?php else: ?>
                  <a href="<?= $item['file_url'] ?>" target="_blank" class="btn btn-xs btn-outline-primary">
                    <i class="fas fa-external-link-alt mr-1"></i> Buka File
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Section Body / Paper Render -->
            <div class="unidoc-paper-wrapper">
              <?php if ($item['type'] === 'inapp'): ?>
                <div class="unidoc-paper a4">
                  <?php
                    // Render sub-template in-app
                    echo view('dokumen/_render', [
                      'kode'          => $item['kode'],
                      'tpl'           => $item['tpl'],
                      'd'             => $item['d'],
                      'kegiatan'      => $kegiatan,
                      'ttd'           => $item['ttd'],
                      'tampilkan_ttd' => true,
                      'AppConfig'     => $AppConfig,
                    ]);
                  ?>
                </div>
              <?php else: ?>
                <!-- Uploaded PDF viewer -->
                <div class="unidoc-pdf-card">
                  <div class="unidoc-pdf-meta mb-2">
                    <i class="fas fa-file-pdf text-danger fa-lg mr-2"></i>
                    <span class="font-weight-bold"><?= htmlspecialchars($item['sub_title']) ?></span>
                    <a href="<?= $item['file_url'] ?>" target="_blank" class="btn btn-xs btn-light ml-auto">
                      <i class="fas fa-external-link-alt mr-1"></i> Buka di Tab Baru
                    </a>
                  </div>
                  <div class="unidoc-pdf-embed-wrapper">
                    <iframe src="<?= $item['file_url'] ?>#toolbar=0&navpanes=0" class="unidoc-pdf-iframe" loading="lazy"></iframe>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </section>
        <?php endforeach; ?>
      <?php endif; ?>
    </main>
  </div>
</div>

<style>
/* ================= UNIFIED DOCUMENT VIEWER STYLES ================= */
.unidoc-wrapper {
  background: #f4f6f9;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  height: 82vh;
  min-height: 550px;
}

/* Control Topbar */
.unidoc-topbar {
  background: #ffffff;
  padding: 10px 18px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  z-index: 10;
}
.unidoc-title-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.95rem;
  color: #1e293b;
}
.unidoc-badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  margin-right: 8px;
}
.unidoc-topbar-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Layout */
.unidoc-layout {
  display: flex;
  flex: 1;
  overflow: hidden;
  position: relative;
}

/* Sidebar TOC Navigation */
.unidoc-sidebar {
  width: 320px;
  background: #ffffff;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  overflow: hidden;
}
.unidoc-sidebar-header {
  padding: 12px 14px 4px 14px;
}
.unidoc-nav {
  flex: 1;
  overflow-y: auto;
  padding: 6px 10px 14px 10px;
}
.unidoc-nav-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 9px 12px;
  margin-bottom: 5px;
  border-radius: 8px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  cursor: pointer;
  transition: all 0.18s ease;
  user-select: none;
}
.unidoc-nav-item:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}
.unidoc-nav-item.active {
  background: #eff6ff;
  border-color: #93c5fd;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.08);
}
.unidoc-nav-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  flex: 1;
}
.unidoc-nav-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 6px;
  background: #e2e8f0;
  color: #475569;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}
.unidoc-nav-item.active .unidoc-nav-num {
  background: #2563eb;
  color: #ffffff;
}
.unidoc-nav-info {
  min-width: 0;
  flex: 1;
}
.unidoc-nav-title {
  font-size: 0.83rem;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.unidoc-nav-sub {
  font-size: 0.72rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.unidoc-nav-badge .badge {
  font-size: 0.68rem;
  padding: 3px 6px;
  font-weight: 500;
}
.badge-soft-primary {
  background-color: #dbeafe;
  color: #1d4ed8;
}
.badge-soft-success {
  background-color: #d1fae5;
  color: #065f46;
}

/* Reorder Controls */
.unidoc-reorder-btns {
  display: inline-flex;
  gap: 3px;
  margin-right: 6px;
}
.btn-reorder {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 0.7rem;
  color: #475569;
  cursor: pointer;
}
.btn-reorder:hover:not(:disabled) {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}
.btn-reorder:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.unidoc-sidebar-footer {
  padding: 10px 14px;
  border-top: 1px solid #e2e8f0;
  background: #ffffff;
}

/* Main Stream */
.unidoc-main {
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px 60px 24px;
  scroll-behavior: smooth;
}
.unidoc-section {
  margin-bottom: 30px;
}
.unidoc-section-header {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-bottom: none;
  border-top-left-radius: 10px;
  border-top-right-left-radius: 10px;
  padding: 12px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.unidoc-sec-number {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.85rem;
  font-weight: 700;
}
.unidoc-sec-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}
.unidoc-sec-actions {
  display: flex;
  gap: 6px;
}

/* Paper Container */
.unidoc-paper-wrapper {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-bottom-left-radius: 10px;
  border-bottom-right-radius: 10px;
  padding: 24px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  overflow-x: auto;
}
.unidoc-paper {
  max-width: 820px;
  margin: 0 auto;
  font-family: "DejaVu Serif", serif, "Times New Roman", Times;
  color: #000000;
}

/* PDF Embed in Stream */
.unidoc-pdf-card {
  padding: 6px;
}
.unidoc-pdf-meta {
  display: flex;
  align-items: center;
}
.unidoc-pdf-embed-wrapper {
  width: 100%;
  height: 600px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  overflow: hidden;
  background: #475569;
}
.unidoc-pdf-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

/* Empty State */
.unidoc-empty-state {
  text-align: center;
  padding: 80px 20px;
  background: #ffffff;
  border-radius: 12px;
  border: 1px dashed #cbd5e1;
}

@media (max-width: 991.98px) {
  .unidoc-layout {
    flex-direction: column;
  }
  .unidoc-sidebar {
    width: 100%;
    height: 220px;
    border-right: none;
    border-bottom: 1px solid #e2e8f0;
  }
  .unidoc-main {
    padding: 14px;
  }
}
</style>

<script>
(function() {
  var base = '<?= $base ?>';
  var KID  = <?= (int)$KegiatanID ?>;
  var isReorderMode = false;

  // Search in TOC list
  $('#unidoc-search').on('input', function() {
    var val = ($(this).val() || '').toLowerCase().trim();
    $('#unidoc-nav-list .unidoc-nav-item').each(function() {
      var t = $(this).find('.unidoc-nav-title').text().toLowerCase();
      var s = $(this).find('.unidoc-nav-sub').text().toLowerCase();
      if (t.indexOf(val) > -1 || s.indexOf(val) > -1) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });

  // Click on TOC nav item -> smooth scroll into view
  $('body').on('click', '.unidoc-nav-item', function(e) {
    if ($(e.target).closest('.btn-reorder').length) return; // ignore click on up/down buttons
    var target = $(this).attr('data-target');
    var $sec   = $(target);
    if ($sec.length) {
      $('.unidoc-nav-item').removeClass('active');
      $(this).addClass('active');
      var container = $('#unidoc-stream');
      container.animate({
        scrollTop: container.scrollTop() + $sec.position().top - 15
      }, 250);
    }
  });

  // Toggle Reorder Mode
  $('#btn-toggle-reorder').on('click', function() {
    isReorderMode = !isReorderMode;
    if (isReorderMode) {
      $(this).addClass('btn-warning').removeClass('btn-outline-secondary');
      $('#lbl-reorder').text('Selesai Mengatur');
      $('.unidoc-reorder-btns').removeClass('d-none');
      $('.unidoc-nav-badge').addClass('d-none');
      $('#reorder-banner').removeClass('d-none');
      $('#reorder-save-footer').removeClass('d-none');
    } else {
      $(this).removeClass('btn-warning').addClass('btn-outline-secondary');
      $('#lbl-reorder').text('Atur Urutan');
      $('.unidoc-reorder-btns').addClass('d-none');
      $('.unidoc-nav-badge').removeClass('d-none');
      $('#reorder-banner').addClass('d-none');
      $('#reorder-save-footer').addClass('d-none');
    }
  });

  // Up/Down button handlers
  function refreshNavNumbers() {
    $('#unidoc-nav-list .unidoc-nav-item').each(function(idx) {
      $(this).find('.unidoc-nav-num').text(idx + 1);
      $(this).find('.btn-move-up').prop('disabled', idx === 0);
      $(this).find('.btn-move-down').prop('disabled', idx === $('#unidoc-nav-list .unidoc-nav-item').length - 1);
    });
  }

  $('body').on('click', '.btn-move-up', function(e) {
    e.stopPropagation();
    var $item = $(this).closest('.unidoc-nav-item');
    var $prev = $item.prev('.unidoc-nav-item');
    if ($prev.length) {
      $item.insertBefore($prev);
      refreshNavNumbers();
      reorderDomSections();
    }
  });

  $('body').on('click', '.btn-move-down', function(e) {
    e.stopPropagation();
    var $item = $(this).closest('.unidoc-nav-item');
    var $next = $item.next('.unidoc-nav-item');
    if ($next.length) {
      $item.insertAfter($next);
      refreshNavNumbers();
      reorderDomSections();
    }
  });

  function reorderDomSections() {
    var $stream = $('#unidoc-stream');
    $('#unidoc-nav-list .unidoc-nav-item').each(function(idx) {
      var key = $(this).attr('data-key');
      var $sec = $('.unidoc-section[data-key="' + key + '"]');
      if ($sec.length) {
        $sec.find('.unidoc-sec-number').text(idx + 1);
        $stream.append($sec);
      }
    });
  }

  // Save Order via AJAX
  $('#btn-save-order').on('click', function() {
    var keys = [];
    $('#unidoc-nav-list .unidoc-nav-item').each(function() {
      keys.push($(this).attr('data-key'));
    });

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

    $.ajax({
      type: 'POST',
      url: base + 'dokumen/simpanUrutan',
      data: { KegiatanID: KID, order: keys },
      dataType: 'json',
      success: function(res) {
        $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Simpan Urutan Dokumen');
        if (res && res.ok) {
          show_toast('success', 'Urutan dokumen berhasil disimpan!');
          $('#btn-toggle-reorder').trigger('click');
        } else {
          show_toast('error', res.msg || 'Gagal menyimpan urutan dokumen.');
        }
      },
      error: function() {
        $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Simpan Urutan Dokumen');
        show_toast('error', 'Gagal menghubungi server.');
      }
    });
  });

  // Scrollspy: update active nav item as user scrolls through document stream
  var scrollTimer = null;
  $('#unidoc-stream').on('scroll', function() {
    if (scrollTimer) clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function() {
      var containerTop = $('#unidoc-stream').offset().top;
      var currentKey = null;

      $('.unidoc-section').each(function() {
        var secTop = $(this).offset().top - containerTop;
        if (secTop <= 80) {
          currentKey = $(this).attr('data-key');
        }
      });

      if (currentKey) {
        var $activeItem = $('.unidoc-nav-item[data-key="' + currentKey + '"]');
        if ($activeItem.length && !$activeItem.hasClass('active')) {
          $('.unidoc-nav-item').removeClass('active');
          $activeItem.addClass('active');
        }
      }
    }, 50);
  });
})();
</script>
