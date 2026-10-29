# API PMB untuk SIAKAD

SIAKAD menarik data mahasiswa baru dari PMB Polmind, lalu mengirim konfirmasi untuk setiap mahasiswa.

Data yang tersedia hanya milik camaba yang sudah memenuhi semua syarat berikut:

1. Dinyatakan lolos seleksi.
2. Daftar ulangnya sudah valid.
3. Sudah diberi NIM oleh Admin PMB.

NIM dibuat di PMB, bukan di SIAKAD.

- **Base URL:** `https://pmb.polmind.ac.id/api/siakad`
- **Autentikasi:** setiap request wajib membawa header `Authorization: Bearer <token>`. Token dibuat Admin PMB di menu **Integrasi SIAKAD > Token API** lalu diberikan ke tim SIAKAD. Token baru langsung membatalkan token lama.
- **Format:** semua respons berupa JSON, termasuk respons error.
- **Batas request:** 120 request per menit.

## 1. Daftar mahasiswa siap ditarik

```
GET /mahasiswa
```

| Parameter | Keterangan |
|---|---|
| `status` | Opsional. Bila tidak diisi, yang dikembalikan hanya `siap_sinkron` dan `proses_sinkron`, yaitu yang perlu ditarik. Nilai lain: `sudah_sinkron`, `gagal_sinkron`. |
| `tahun` | Opsional. Tahun masuk, contoh `2027`. |
| `prodi` | Opsional. Kode prodi PMB, contoh `TRPL`. |
| `per_page` | Opsional. Jumlah data per halaman, 1–200 (default 50). Halaman berikutnya diambil dengan `page=2`, `page=3`, dan seterusnya. |

Contoh respons:

```json
{
  "data": [
    {
      "id_pendaftar": "A120270001",
      "nim": "A20270001",
      "status_sinkron": "siap_sinkron",
      "tahun_masuk": 2027,
      "program_studi": { "kode": "TRPL", "nama": "Teknologi Rekayasa Perangkat Lunak", "jenjang": "D4", "kode_pddikti": "58302", "id_siakad": "uuid-prodi" },
      "kelas": { "kode": "REG_A", "nama": "Reguler A" },
      "jalur": { "kode": "umum", "nama": "Jalur Umum" },
      "gelombang": "Gelombang 1",
      "tanggal_daftar_ulang": "2027-04-05T10:00:00+07:00",
      "tanggal_nim": "2027-04-06T09:00:00+07:00",
      "tanggal_sinkron": null,
      "data_lengkap": true,
      "data_kurang": [],
      "record": { "nama_mahasiswa": "AMMAR MUSYAFFA FIRDAUS", "jenis_kelamin": "L", "...": "..." }
    }
  ],
  "meta": { "page": 1, "per_page": 50, "total": 1, "last_page": 1 }
}
```

Isi `record` sama persis dengan format `body_request_mahasiswa.json` dari tim SIAKAD, jadi bisa langsung dipakai sebagai body request biodata mahasiswa.

Bila `data_lengkap` bernilai `false`, biodata camaba belum lengkap. Daftar data yang belum diisi ada di `data_kurang`. Mahasiswa seperti ini sebaiknya dilewati atau dilaporkan `gagal`.

## 2. Detail satu mahasiswa

```
GET /mahasiswa/{id_pendaftar}
```

Respons berisi `{ "data": { ... } }`, dengan isi yang sama seperti satu item pada daftar di atas.

Bila mahasiswa tidak ditemukan atau belum ber-NIM, respons berkode `404`.

## 3. Konfirmasi hasil

```
POST /mahasiswa/{id_pendaftar}/konfirmasi
```

Bila data berhasil disimpan di SIAKAD:

```json
{ "status": "berhasil", "nim": "A20270001", "id_mahasiswa": "uuid-mahasiswa-di-siakad" }
```

- `nim` wajib diisi dan harus sama dengan NIM yang ditarik.
- `id_mahasiswa` opsional dan disimpan di log PMB.
- Setelah konfirmasi berhasil, status di PMB menjadi `sudah_sinkron` dan NIM tidak bisa diubah lagi oleh PMB.
- Bila konfirmasi yang sama dikirim ulang, respons tetap `200`.

Bila data gagal disimpan di SIAKAD:

```json
{ "status": "gagal", "pesan": "id_wilayah tidak dikenal" }
```

- Status di PMB menjadi `gagal_sinkron` dan isi `pesan` tampil di halaman Integrasi SIAKAD.
- Admin PMB akan memperbaiki datanya lalu menyiapkannya lagi (tombol **Proses**). Setelah itu, mahasiswa ini muncul kembali di daftar.

## Kode respons

| Kode | Arti |
|---|---|
| `200` | Berhasil. |
| `401` | Token tidak ada atau salah. |
| `403` | IP server SIAKAD tidak termasuk daftar IP yang diizinkan (bila PMB mengaktifkan daftar IP). |
| `404` | Mahasiswa tidak ditemukan atau belum siap ditarik. |
| `409` | Konflik. Contoh: NIM yang dikonfirmasi berbeda dengan NIM di PMB (tarik ulang datanya), atau mahasiswa sudah berstatus berhasil lalu dilaporkan gagal. |
| `422` | Body request tidak valid. Detail ada di field `errors`. |
| `503` | API belum diaktifkan di server PMB. |

## Alur yang disarankan

1. Panggil `GET /mahasiswa` secara berkala, misalnya setiap jam, dan ambil semua halaman.
2. Simpan setiap `record` ke SIAKAD dengan memakai `nim` dan `program_studi` dari data tersebut.
3. Kirim `konfirmasi` per mahasiswa:
   - status `berhasil` bila data tersimpan;
   - status `gagal` dengan `pesan` berisi alasannya bila data tidak tersimpan.
