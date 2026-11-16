# Cabang membership mengikuti paket

Cabang asal identitas (`members.branch_store_id`) berbeda dari cabang paket
membership (`member_packages.branch_store_id`). Tidak ada pemindahan identitas
atau penulisan ulang histori untuk memperbaiki kasus renewal lintas cabang.

- Paket One Club: daftar aktif, unpaid, pending, expired, leading history, dan
  approval mengikuti cabang paket. Label cabang pada daftar membership/approval
  juga memakai cabang paket.
- Paket All Club tetap terlihat lintas cabang.
- Approval, termasuk kiriman langsung ke endpoint update, memeriksa cabang paket.
  Paket yang sudah soft-delete tetap mempertahankan cabang untuk histori/approval.
- Renewal All Club boleh di cabang lain. Renewal One Club hanya di cabang paket
  sumber, baik membuka form maupun mengirim POST. Untuk mengambil membership
  cabang lain, gunakan Create Membership pada identitas member yang sama.
- Paket tujuan renewal/Create Membership harus berasal dari cabang login.
  Memalsukan ID paket dari cabang lain ditolak, termasuk paket All Club tujuan.
  Kebijakan paket gratis untuk CS tetap berlaku.
- Pembayaran awal renewal/Create Membership menyimpan snapshot cabang login.
  Pembayaran yang sudah ada dan cabang asal member tidak dimodifikasi.
- Check-in berdasarkan kartu mempertimbangkan membership aktif yang dapat
  diakses dari cabang login; membership One Club lain tidak menutupi membership
  yang berlaku di cabang tersebut. Aturan One Club berdasarkan cabang paket,
  pembayaran, tenggat, freeze, dan cicilan tetap dipertahankan.

Kasus verifikasi baca database lokal: member WSP1648, registrasi 5478, paket
One Club Candisari (315), cabang asal member Pamularsih (2), pembayaran awal
Rp900.000 di Candisari (3). Setelah perbaikan query aktif/paginasi menemukan
registrasi itu di Candisari dan tidak di Pamularsih. Tidak ada data kasus asli
yang diedit untuk pengujian.

Deploy hanya new-level-fit-master, lalu `php artisan optimize:clear`.
Tidak ada migration/SQL, tidak perlu memindahkan data member atau pembayaran.
