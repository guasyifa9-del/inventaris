<?php
 goto OQiTk; lKTXO: $barang_query = "\xa\40\x20\x20\x20\123\x45\x4c\105\103\x54\x20\142\56\x2a\40\x46\x52\x4f\115\x20\142\141\x72\x61\x6e\x67\40\x62\12\40\x20\40\x20\x57\x48\x45\x52\105\x20\x62\56\163\164\x61\x74\x75\163\x20\x3d\x20\47\141\x63\164\151\x76\145\47\40\101\116\x44\x20\142\56\152\165\155\x6c\x61\150\137\164\x65\162\163\145\x64\151\x61\x20\76\40\60\xa\x20\40\40\x20\117\x52\104\105\122\40\x42\131\x20\142\56\x6b\141\x74\x65\x67\157\162\151\x5f\x69\144\x2c\40\x62\56\156\x61\155\x61\x5f\x62\141\x72\x61\156\147\12"; goto hHfd3; n1rNn: echo isset($_POST["\152\x61\x6d\137\x73\145\154\x65\x73\x61\x69\x5f\160\151\156\x6a\x61\155"]) ? htmlspecialchars($_POST["\152\141\x6d\137\163\x65\x6c\145\163\x61\x69\x5f\x70\151\156\152\141\155"]) : "\x31\67\72\60\60"; goto sEnEm; gGTsx: ?>
"type="date"name="tanggal_selesai_pinjam"min="<?php  goto Zc2bU; tPy10: echo date("\x59\x2d\155\55\x64"); goto B_sR2; bnEHn: foreach ($kategori_list as $cat) { ?>
<option value="<?php  echo $cat["\x6b\141\164\145\147\x6f\x72\x69\137\x69\144"]; ?>
"><?php  echo htmlspecialchars($cat["\156\x61\x6d\x61\x5f\x6b\x61\x74\145\x67\157\162\x69"]); ?>
</option><?php  } goto EJ33J; qT92f: $user = getCurrentUser(); goto LTHVu; AXBKu: echo isset($_POST["\x74\141\156\147\x67\141\154\137\163\x65\x6c\145\163\x61\151\x5f\160\151\156\x6a\x61\x6d"]) ? htmlspecialchars($_POST["\164\x61\156\x67\x67\141\x6c\x5f\x73\145\154\x65\163\x61\x69\137\x70\x69\156\x6a\x61\x6d"]) : ''; goto gGTsx; T_aok: while ($row = mysqli_fetch_assoc($kategori_result)) { $kategori_list[] = $row; } goto lKTXO; vw4zG: echo isset($_POST["\x74\141\x6e\x67\x67\x61\154\x5f\155\x75\x6c\141\151\x5f\x70\x69\x6e\152\141\155"]) ? htmlspecialchars($_POST["\x74\141\x6e\x67\x67\141\154\137\x6d\165\x6c\141\151\x5f\x70\151\x6e\152\x61\x6d"]) : ''; goto AIbd0; RpuOr: $success = ''; goto N0pjH; Nf0NF: ?>
</textarea></div></div></div><div class="mb-4 card"><div class="bg-primary card-header text-white"><h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Periode Peminjaman</h5></div><div class="card-body"><div class="mb-3 row"><div class="col-md-6"><label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label> <input class="form-control"value="<?php  goto vw4zG; sEnEm: ?>
"type="time"name="jam_selesai_pinjam"></div></div></div></div><div class="mb-4 card"><div class="bg-primary card-header text-white"><h5 class="mb-0"><i class="fas fa-box"></i> Barang/Ruang Yang Diminjam <span class="text-danger">*</span></h5></div><div class="card-body"><div class="row"><div class="col-md-6"><label class="form-label">Filter Kategori</label> <select class="form-select"id="kategori-filter"><option value="">-- Semua Kategori --</option><?php  goto bnEHn; HaNEu: require_once __DIR__ . "\x2f\56\56\x2f\56\56\57\143\x6f\156\146\151\147\57\x64\141\x74\141\142\141\x73\x65\56\160\150\x70"; goto Gkuja; OQiTk: session_start(); goto HaNEu; hHfd3: $barang_result = mysqli_query($connection, $barang_query); goto ghXC4; abJ2n: $kategori_result = mysqli_query($connection, $kategori_query); goto lUVBG; Gkuja: require_once __DIR__ . "\x2f\56\56\57\x2e\x2e\57\x6c\151\x62\57\146\165\x6e\x63\164\x69\157\156\x73\x2e\160\x68\160"; goto jqMk3; KqAvE: ?>
"required></div><div class="col-md-6"><label class="form-label">Jam Selesai</label> <input class="form-control"value="<?php  goto n1rNn; LTHVu: if ($user["\152\x65\156\151\163\137\x70\145\x6e\147\x67\x75\156\x61"] !== "\x6b\145\160\141\x6c\x61\x5f\144\145\x70\x61\162\164\x65\x6d\145\156" && $user["\162\x6f\x6c\x65"] !== "\x61\144\155\151\156") { setFlashMessage("\x65\162\162\x6f\x72", "\x41\156\144\x61\40\164\x69\x64\x61\153\x20\155\x65\155\151\154\151\x6b\x69\40\x61\153\x73\145\163\x20\x75\156\164\165\x6b\40\x6d\x65\x6d\x62\165\141\164\x20\163\165\162\141\164\40\160\145\x6d\151\x6e\x6a\141\155\141\156"); header("\114\157\x63\141\164\x69\157\156\x3a\x20\x69\156\144\x65\170\56\x70\150\x70"); die; } goto h1ANk; R7Yu6: echo htmlspecialchars($user["\x64\x65\160\141\x72\x74\145\x6d\145\156"] ?? "\x4e\x2f\101"); goto eIeoE; EJ33J: ?>
</select></div></div><div class="row mt-3"><div class="col-md-6"><label class="form-label mb-2">Daftar Barang Tersedia:</label><div class="item-list"id="barang-list"><?php  goto I6pvh; I6pvh: foreach ($barang_list as $barang) { ?>
<div class="item-card"data-barang-id="<?php  echo $barang["\142\141\162\141\x6e\147\x5f\151\144"]; ?>
"data-kategori="<?php  echo $barang["\153\141\x74\x65\147\x6f\162\151\x5f\x69\144"]; ?>
"><div class="d-flex justify-content-between align-items-start"><div class="flex-grow-1"><strong><?php  echo htmlspecialchars($barang["\x6e\x61\x6d\141\x5f\x62\x61\x72\141\x6e\147"]); ?>
</strong><br><small class="text-muted">Kode:<?php  echo htmlspecialchars($barang["\x6b\157\144\145\137\142\x61\x72\x61\156\x67"]); ?>
</small><br><small class="text-muted">Tersedia: <span class="text-success"><?php  echo $barang["\152\165\155\x6c\141\150\137\x74\145\x72\163\145\144\151\141"]; ?>
unit</span> </small><?php  if ($barang["\x69\x73\x5f\x62\x65\162\x62\141\171\141\x72"]) { ?>
<br><small class="text-danger">💰<?php  echo formatRupiah($barang["\150\141\x72\147\x61\137\163\145\x77\x61\x5f\160\145\162\137\x68\x61\162\151"]); ?>
/hari </small><?php  } ?>
</div><input class="barang-checkbox"value="<?php  echo $barang["\x62\x61\162\x61\x6e\147\x5f\151\144"]; ?>
"type="checkbox"onclick="toggleItemCard(this)"></div></div><?php  } goto U4vyu; fR7w3: ?>
</textarea> <small class="text-muted">Jelaskan tujuan/keperluan peminjaman barang/ruang</small></div><div class="mb-3"><label class="form-label">Keterangan Tambahan</label> <textarea class="form-control"name="keterangan_peminjaman"placeholder="Informasi tambahan jika diperlukan"rows="2">
<?php  goto u9J1B; B_sR2: ?>
"required></div><div class="col-md-6"><label class="form-label">Jam Mulai</label> <input class="form-control"value="<?php  goto zJkDd; qw9FJ: ?>
"type="time"name="jam_mulai_pinjam"></div></div><div class="mb-3 row"><div class="col-md-6"><label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label> <input class="form-control"value="<?php  goto AXBKu; ghXC4: $barang_list = array(); goto tFx_P; u9J1B: echo isset($_POST["\153\x65\164\x65\162\141\x6e\147\x61\156\x5f\x70\145\155\x69\x6e\152\x61\x6d\141\156"]) ? htmlspecialchars($_POST["\153\145\164\145\162\141\x6e\x67\141\x6e\137\x70\x65\155\151\156\x6a\141\x6d\141\156"]) : ''; goto Nf0NF; Pokrv: ?>
<form enctype="multipart/form-data"id="form-surat"method="POST"><div class="mb-4 card"><div class="bg-primary card-header text-white"><h5 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Dasar</h5></div><div class="card-body"><div class="mb-3 row"><div class="col-md-6"><label class="form-label">Kepala Departemen <span class="text-danger">*</span></label> <input class="form-control"value="<?php  goto wLekG; Ntf1W: ?>
<main class="main-content"><div class="mb-4 d-flex align-items-center justify-content-between"><div><h2 class="mb-1"><i class="fas fa-plus-circle"></i> Buat Surat Peminjaman Baru</h2><nav aria-label="breadcrumb"><ol class="mb-0 breadcrumb"><li class="breadcrumb-item"><a href="../../index.php">Home</a></li><li class="breadcrumb-item"><a href="index.php">Surat Peminjaman</a></li><li class="breadcrumb-item active">Buat Baru</li></ol></nav></div></div><?php  goto lrcw_; lUVBG: $kategori_list = array(); goto T_aok; VlUDr: if ($_SERVER["\x52\105\121\x55\105\x53\124\137\115\105\124\x48\117\x44"] === "\x50\117\123\x54") { $tanggal_mulai = $_POST["\x74\141\x6e\x67\147\141\x6c\137\x6d\x75\x6c\141\x69\137\160\x69\156\x6a\x61\155"] ?? ''; $tanggal_selesai = $_POST["\x74\x61\156\147\147\141\x6c\x5f\163\x65\x6c\145\163\141\151\137\x70\151\x6e\x6a\x61\155"] ?? ''; $jam_mulai = $_POST["\152\141\155\137\x6d\x75\x6c\141\x69\x5f\160\x69\156\x6a\x61\x6d"] ?? "\60\x38\x3a\60\x30"; $jam_selesai = $_POST["\152\141\155\137\163\x65\154\x65\x73\x61\151\x5f\x70\151\x6e\152\141\x6d"] ?? "\x31\x37\x3a\60\x30"; $keperluan = $_POST["\153\x65\x70\145\x72\154\165\141\x6e"] ?? ''; $keterangan = $_POST["\x6b\145\x74\145\162\141\156\147\x61\156\x5f\160\145\155\x69\x6e\x6a\x61\x6d\x61\156"] ?? ''; $action = $_POST["\x61\x63\x74\x69\x6f\x6e"] ?? "\x73\x61\166\x65\137\x64\162\141\146\x74"; if (empty($tanggal_mulai) || empty($tanggal_selesai)) { $error = "\x54\141\156\x67\x67\141\154\40\x6d\165\154\141\151\40\x64\141\156\x20\x74\141\x6e\x67\147\x61\154\40\163\x65\x6c\x65\163\141\151\40\150\x61\x72\x75\163\40\x64\151\x69\163\151"; } elseif (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) { $error = "\x54\x61\156\x67\x67\x61\x6c\x20\x6d\x75\154\141\x69\x20\x68\x61\162\x75\163\x20\x73\x65\142\x65\154\165\x6d\40\164\x61\156\x67\x67\141\x6c\x20\163\x65\154\x65\163\141\151"; } elseif (empty($keperluan)) { $error = "\113\x65\x70\x65\162\154\x75\x61\156\x20\160\145\x6d\x69\156\152\141\x6d\x61\x6e\x20\150\141\162\165\163\40\144\151\151\163\151"; } elseif (!isset($_POST["\142\x61\162\141\156\147\137\x69\144"]) || empty($_POST["\x62\141\162\141\x6e\147\x5f\151\x64"])) { $error = "\x4d\x69\156\151\x6d\141\154\x20\150\x61\162\x75\x73\40\x61\144\x61\x20\61\40\142\141\162\141\156\x67\40\171\x61\156\x67\40\144\x69\x70\151\154\151\x68"; } else { $data = array("\153\x65\160\x61\x6c\141\x5f\x64\x65\x70\141\x72\x74\145\155\x65\156\x5f\x69\x64" => $user["\x75\x73\x65\x72\137\151\x64"], "\144\x65\x70\x61\162\x74\x65\155\145\156" => $user["\x64\x65\x70\x61\x72\x74\x65\155\145\156"], "\164\141\156\147\147\x61\154\x5f\x73\165\162\x61\x74" => date("\x59\x2d\155\55\144"), "\x74\141\156\147\x67\x61\x6c\137\x6d\x75\x6c\x61\x69\137\160\151\156\152\x61\x6d" => $tanggal_mulai, "\164\141\156\147\147\x61\154\137\x73\145\154\145\x73\141\151\137\x70\x69\156\152\141\x6d" => $tanggal_selesai, "\152\141\x6d\137\155\x75\154\141\x69\x5f\x70\151\156\152\141\155" => $jam_mulai, "\x6a\x61\x6d\x5f\x73\x65\154\x65\163\141\x69\137\160\151\x6e\152\141\x6d" => $jam_selesai, "\x6b\x65\x70\x65\162\154\165\141\x6e" => $keperluan, "\x6b\145\x74\x65\x72\x61\x6e\x67\x61\156\137\160\145\155\x69\x6e\x6a\x61\155\141\x6e" => $keterangan, "\x69\164\145\x6d\163" => array()); $barang_ids = $_POST["\142\141\162\141\156\x67\137\151\144"]; $jumlah_array = $_POST["\x6a\x75\x6d\154\141\150"] ?? array(); foreach ($barang_ids as $idx => $barang_id) { $barang_id = (int) $barang_id; $jumlah = (int) ($jumlah_array[$idx] ?? 1); if ($jumlah > 0) { $data["\x69\164\145\155\x73"][] = array("\142\141\x72\x61\x6e\x67\137\151\x64" => $barang_id, "\152\x75\x6d\x6c\141\x68" => $jumlah); } } if (empty($data["\151\164\145\x6d\163"])) { $error = "\120\x69\x6c\x69\150\40\x6d\x69\x6e\x69\155\141\154\40\x31\40\142\x61\162\141\156\x67\40\144\145\x6e\x67\141\156\x20\152\165\x6d\154\x61\150\x20\76\40\x30"; } else { $result = createSuratPeminjaman($data); if ($result["\163\165\x63\x63\145\163\x73"]) { if ($action === "\x73\x65\x6e\144") { $send_result = sendSuratPeminjaman($result["\x73\165\162\x61\164\x5f\151\144"]); if ($send_result["\163\x75\x63\143\145\x73\163"]) { setFlashMessage("\x73\x75\x63\143\145\163\163", "\x53\165\x72\x61\x74\40\x62\145\x72\150\x61\163\151\154\x20\x64\x69\x62\x75\x61\x74\x20\x64\141\156\x20\144\x69\x6b\x69\162\x69\x6d\40\x6b\145\x20\x4b\145\x70\141\x6c\x61\40\111\x6e\x76\x65\156\x74\141\162\x69\x73"); header("\x4c\x6f\x63\x61\x74\x69\x6f\156\x3a\x20\144\145\x74\x61\151\x6c\56\160\x68\160\77\x69\144\75" . $result["\x73\x75\x72\141\164\x5f\x69\144"]); die; } else { $error = $send_result["\155\x65\x73\163\x61\147\145"]; } } else { setFlashMessage("\x73\165\x63\x63\x65\x73\x73", "\123\x75\x72\141\164\x20\x62\145\x72\150\x61\x73\x69\x6c\40\x64\x69\163\x69\x6d\160\141\156\x20\163\145\142\141\147\141\x69\x20\153\157\x6e\x73\145\x70\56\x20\101\156\144\x61\x20\142\151\x73\x61\x20\155\x65\156\147\145\x64\x69\164\156\x79\141\x20\x61\x74\x61\x75\x20\x6d\145\156\x67\x69\162\151\x6d\x6e\171\x61\x20\156\x61\x6e\164\x69\56"); header("\x4c\x6f\x63\141\164\151\x6f\x6e\72\40\x64\145\164\x61\x69\154\56\x70\x68\x70\77\151\144\75" . $result["\x73\x75\162\141\x74\x5f\151\144"]); die; } } else { $error = $result["\155\145\x73\163\x61\147\145"]; } } } } goto OPZp6; wLekG: echo htmlspecialchars($user["\156\x61\x6d\x61\x5f\x6c\145\x6e\x67\153\141\160"]); goto X_Ocn; zJkDd: echo isset($_POST["\x6a\x61\155\x5f\x6d\165\x6c\141\151\137\x70\x69\156\x6a\x61\x6d"]) ? htmlspecialchars($_POST["\152\x61\155\137\155\x75\154\141\151\137\160\x69\x6e\x6a\141\x6d"]) : "\60\x38\72\x30\60"; goto qw9FJ; s1Evv: include __DIR__ . "\x2f\x2e\x2e\57\x2e\56\57\166\x69\145\x77\163\57\144\145\146\x61\165\154\164\57\x73\151\144\145\x62\141\x72\x5f\x61\144\155\x69\156\x2e\160\150\160"; goto Ntf1W; AIbd0: ?>
"type="date"name="tanggal_mulai_pinjam"min="<?php  goto tPy10; eIeoE: ?>
"disabled></div></div><div class="mb-3"><label class="form-label">Keperluan Peminjaman <span class="text-danger">*</span></label> <textarea class="form-control"name="keperluan"placeholder="Contoh: Rapat tahunan perusahaan, Acara training karyawan, dll"rows="3"required>
<?php  goto PSS1Y; X_Ocn: ?>
"disabled> <small class="text-muted">Surat dikirim atas nama Anda sebagai Kepala Departemen</small></div><div class="col-md-6"><label class="form-label">Departemen <span class="text-danger">*</span></label> <input class="form-control"value="<?php  goto R7Yu6; OPZp6: ?>
<!doctypehtml><html lang="id"><head><meta charset="UTF-8"><meta content="width=device-width,initial-scale=1"name="viewport"><title>Buat Surat Peminjaman - Inventaris Kantor</title><link href="../../assets/css/style.css"rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"rel="stylesheet"><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f0f2f5}.main-content{margin-left:260px;padding:30px;min-height:100vh}.item-card{border:1px solid #dee2e6;border-radius:8px;padding:12px;margin-bottom:10px;transition:all .3s ease}.item-card:hover{background-color:#f8f9fa;border-color:#0d6efd}.item-card.selected{background-color:#e7f1ff;border-color:#0d6efd;border-width:2px}.item-list{max-height:400px;overflow-y:auto}.selected-items-list{background-color:#f8f9fa;border-radius:8px;padding:12px;min-height:50px}.selected-item-badge{display:inline-block;background-color:#e7f1ff;border:1px solid #0d6efd;border-radius:20px;padding:8px 12px;margin:4px;font-size:13px}.selected-item-badge .btn-remove{margin-left:8px;cursor:pointer;color:#dc3545}.selected-item-badge .btn-remove:hover{font-weight:700}</style></head><body><div class="container-fluid"><div class="row"><?php  goto s1Evv; r90hC: echo json_encode($barang_list); goto JIqso; lrcw_: if ($error) { ?>
<div class="alert alert-danger alert-dismissible fade show"role="alert"><i class="fas fa-exclamation-circle"></i><?php  echo htmlspecialchars($error); ?>
<button class="btn-close"type="button"data-bs-dismiss="alert"></button></div><?php  } goto Pokrv; U4vyu: ?>
</div></div><div class="col-md-6"><label class="form-label mb-2">Barang Dipilih:</label><div class="selected-items-list"id="selected-items"><span class="text-muted">Belum ada barang dipilih</span></div><div id="hidden-inputs"></div><div class="mt-3"><label class="form-label">Total Perkiraan Biaya:</label><div class="mb-0 alert alert-info"><h5 class="mb-0"><span id="total-biaya">Rp 0</span></h5></div></div></div></div></div></div><div class="mb-4 card"><div class="bg-primary card-header text-white"><h5 class="mb-0"><i class="fas fa-file-upload"></i> Dokumen Pendukung</h5></div><div class="card-body"><div class="mb-3"><label class="form-label">Upload Surat Peminjaman (PDF/DOC/JPG)</label> <input class="form-control"type="file"name="file_surat"accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"> <small class="text-muted">Ukuran maksimal 5MB. Format: PDF, Word, atau Image</small></div></div></div><div class="mb-4 d-flex gap-2"><button class="btn btn-secondary"type="submit"name="action"value="save_draft"><i class="fas fa-save"></i> Simpan Sebagai Konsep</button> <button class="btn btn-success"type="submit"name="action"value="send"><i class="fas fa-paper-plane"></i> Kirim ke Kepala Inventaris</button> <a href="index.php"class="btn btn-outline-secondary"><i class="fas fa-times"></i> Batal</a></div></form></main></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script>let selectedItems = {};
        let barangData =<?php  goto r90hC; N0pjH: $kategori_query = "\123\105\x4c\105\103\124\40\x2a\40\x46\122\117\115\x20\153\141\x74\145\x67\157\162\151\x20\117\122\104\x45\122\x20\102\131\x20\156\x61\x6d\x61\x5f\153\x61\164\145\x67\x6f\x72\151"; goto abJ2n; jqMk3: require_once __DIR__ . "\57\56\x2e\57\x2e\56\x2f\x6c\x69\x62\x2f\x61\x75\164\150\56\160\150\x70"; goto yggP3; PSS1Y: echo isset($_POST["\153\145\x70\x65\x72\154\x75\x61\156"]) ? htmlspecialchars($_POST["\x6b\x65\x70\145\x72\154\165\141\x6e"]) : ''; goto fR7w3; yggP3: requireAdmin(); goto qT92f; tFx_P: while ($row = mysqli_fetch_assoc($barang_result)) { $barang_list[] = $row; } goto VlUDr; h1ANk: $error = ''; goto RpuOr; Zc2bU: echo date("\x59\x2d\x6d\x2d\144"); goto KqAvE; JIqso: ?>
;

        function toggleItemCard(checkbox) {
            const barangId = parseInt(checkbox.value);
            const card = checkbox.closest('.item-card');
            
            if (checkbox.checked) {
                selectedItems[barangId] = {
                    barang_id: barangId,
                    jumlah: 1,
                    nama: card.querySelector('strong').textContent,
                    harga: barangData.find(b => b.barang_id == barangId).harga_sewa_per_hari
                };
                card.classList.add('selected');
            } else {
                delete selectedItems[barangId];
                card.classList.remove('selected');
            }
            
            updateSelectedItems();
        }

        function updateSelectedItems() {
            const container = document.getElementById('selected-items');
            const hiddenInputs = document.getElementById('hidden-inputs');
            
            if (Object.keys(selectedItems).length === 0) {
                container.innerHTML = '<span class="text-muted">Belum ada barang dipilih</span>';
                hiddenInputs.innerHTML = '';
                document.getElementById('total-biaya').textContent = 'Rp 0';
                return;
            }
            
            let html = '';
            let totalBiaya = 0;
            let hiddenHtml = '';
            let index = 0;
            
            for (const barangId in selectedItems) {
                const item = selectedItems[barangId];
                const barang = barangData.find(b => b.barang_id == barangId);
                
                html += `
                    <span class="selected-item-badge">
                        ${item.nama} x 
                        <input type="number" min="1" max="${barang.jumlah_tersedia}" value="${item.jumlah}" 
                            style="width: 40px;" onchange="updateItemQuantity(${barangId}, this.value)">
                        <span class="btn-remove" onclick="removeItem(${barangId})">✕</span>
                    </span>
                `;
                
                totalBiaya += (item.harga * item.jumlah);
                
                hiddenHtml += `<input type="hidden" name="barang_id[]" value="${barangId}">`;
                hiddenHtml += `<input type="hidden" name="jumlah[]" value="${item.jumlah}">`;
            }
            
            container.innerHTML = html;
            hiddenInputs.innerHTML = hiddenHtml;
            
            // Format currency
            let tanggalMulai = document.querySelector('input[name="tanggal_mulai_pinjam"]').value;
            let tanggalSelesai = document.querySelector('input[name="tanggal_selesai_pinjam"]').value;
            
            if (tanggalMulai && tanggalSelesai) {
                const start = new Date(tanggalMulai);
                const end = new Date(tanggalSelesai);
                const durasi = Math.max(1, Math.ceil((end - start) / (1000 * 60 * 60 * 24)));
                totalBiaya *= durasi;
            }
            
            document.getElementById('total-biaya').textContent = formatRupiah(totalBiaya);
        }

        function updateItemQuantity(barangId, value) {
            const jumlah = parseInt(value) || 1;
            if (selectedItems[barangId]) {
                selectedItems[barangId].jumlah = jumlah;
                updateSelectedItems();
            }
        }

        function removeItem(barangId) {
            delete selectedItems[barangId];
            document.querySelector(`.barang-checkbox[value="${barangId}"]`).checked = false;
            document.querySelector(`.barang-checkbox[value="${barangId}"]`).closest('.item-card').classList.remove('selected');
            updateSelectedItems();
        }

        // Filter by kategori
        document.getElementById('kategori-filter').addEventListener('change', function() {
            const kategoriId = this.value;
            const items = document.querySelectorAll('.item-card');
            
            items.forEach(item => {
                if (!kategoriId || item.dataset.kategori == kategoriId) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }

        // Form validation
        document.getElementById('form-surat').addEventListener('submit', function(e) {
            if (Object.keys(selectedItems).length === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 barang untuk dipinjam!');
                return false;
            }
            
            const tanggalMulai = document.querySelector('input[name="tanggal_mulai_pinjam"]').value;
            const tanggalSelesai = document.querySelector('input[name="tanggal_selesai_pinjam"]').value;
            
            if (!tanggalMulai || !tanggalSelesai) {
                e.preventDefault();
                alert('Tanggal mulai dan tanggal selesai harus diisi!');
                return false;
            }
            
            if (new Date(tanggalMulai) > new Date(tanggalSelesai)) {
                e.preventDefault();
                alert('Tanggal mulai harus sebelum tanggal selesai!');
                return false;
            }
        });</script></body></html>