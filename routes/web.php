<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*Fungsi sederhana: status kuota*/
if (!function_exists('statusKuota')) {
    function statusKuota(int $kuota): string
    {
        if ($kuota >= 75) {
            return 'Besar';
        }

        return 'Terbatas';
    }
}

/*Pembersihan input teks: trim dan strip_tags*/
function bersihkan(?string $nilai): string
{
    return trim(strip_tags((string) $nilai));
}

function bersihkanInputKegiatan(Request $request): void
{
    $request->merge([
        'kode_kegiatan' => bersihkan($request->input('kode_kegiatan')),
        'nama_kegiatan' => bersihkan($request->input('nama_kegiatan')),
        'email_pic' => bersihkan($request->input('email_pic')),
    ]);
}

/*Aturan validasi (dipakai Create dan Update)*/
function aturanKegiatan(): array
{
    return [
        'kode_kegiatan' => ['required', 'regex:/^[A-Za-z0-9]{10}$/'],
        'nama_kegiatan' => ['required', 'string', 'min:3', 'max:100'],
        'email_pic' => ['required', 'email', 'max:100'],
        'kuota' => ['required', 'integer', 'min:10', 'max:200'],
        'jenis_kegiatan_id' => ['required', 'integer'],
    ];
}

function pesanKegiatan(): array
{
    return [
        'kode_kegiatan.required' => 'Kode kegiatan wajib diisi.',
        'kode_kegiatan.regex' => 'Kode kegiatan harus tepat 10 karakter huruf atau angka.',
        'nama_kegiatan.required' => 'Nama kegiatan wajib diisi.',
        'nama_kegiatan.min' => 'Nama kegiatan minimal 3 karakter.',
        'nama_kegiatan.max' => 'Nama kegiatan maksimal 100 karakter.',
        'email_pic.required' => 'Email penanggung jawab wajib diisi.',
        'email_pic.email' => 'Format email tidak valid.',
        'email_pic.max' => 'Email maksimal 100 karakter.',
        'kuota.required' => 'Kuota wajib diisi.',
        'kuota.integer' => 'Kuota harus berupa bilangan bulat.',
        'kuota.min' => 'Kuota minimal 10.',
        'kuota.max' => 'Kuota maksimal 200.',
        'jenis_kegiatan_id.required' => 'Jenis kegiatan wajib dipilih.',
        'jenis_kegiatan_id.integer' => 'Jenis kegiatan tidak valid.',
    ];
}

/*Pemeriksaan jenis kegiatan lewat PDO*/
function jenisTersedia(\PDO $pdo, int $id): bool
{
    $cek = $pdo->prepare('SELECT COUNT(*) FROM jenis_kegiatan WHERE id = :id');
    $cek->execute(['id' => $id]);

    return (int) $cek->fetchColumn() > 0;
}

/*READ + PENCARIAN + FILTER*/
Route::get('/kegiatan', function (Request $request) {
    $kataKunci = bersihkan($request->query('q', ''));
    $jenisId = $request->query('jenis_kegiatan_id', '');

    try {
        $pdo = DB::connection()->getPdo();

        $sql = 'SELECT k.id, k.kode_kegiatan, k.nama_kegiatan, k.email_pic,
                       k.kuota, k.jenis_kegiatan_id, j.nama_jenis
                FROM kegiatan AS k
                JOIN jenis_kegiatan AS j
                  ON j.id = k.jenis_kegiatan_id
                WHERE 1 = 1';
        $parameter = [];

        if ($kataKunci !== '') {
            $sql .= ' AND (k.kode_kegiatan LIKE :kata_kunci_kode
                        OR k.nama_kegiatan LIKE :kata_kunci_nama
                        OR k.email_pic LIKE :kata_kunci_email)';
            // tanda % dan _ yang diketik pengguna dianggap huruf biasa
            $aman = addcslashes($kataKunci, '%_\\');
            $nilaiPencarian = '%' . $aman . '%';
            $parameter['kata_kunci_kode'] = $nilaiPencarian;
            $parameter['kata_kunci_nama'] = $nilaiPencarian;
            $parameter['kata_kunci_email'] = $nilaiPencarian;
        }

        if ($jenisId !== '') {
            $sql .= ' AND k.jenis_kegiatan_id = :jenis_kegiatan_id';
            $parameter['jenis_kegiatan_id'] = (int) $jenisId;
        }

        $sql .= ' ORDER BY k.kode_kegiatan';
        $ambilKegiatan = $pdo->prepare($sql);
        $ambilKegiatan->execute($parameter);
        $daftarKegiatan = $ambilKegiatan->fetchAll(\PDO::FETCH_ASSOC);

        $ambilJenis = $pdo->prepare(
            'SELECT id, nama_jenis
             FROM jenis_kegiatan
             ORDER BY nama_jenis'
        );
        $ambilJenis->execute();
        $daftarJenis = $ambilJenis->fetchAll(\PDO::FETCH_ASSOC);

        return view('kegiatan', compact(
            'daftarKegiatan',
            'daftarJenis',
            'kataKunci',
            'jenisId'
        ));
    } catch (\Throwable $error) {
        report($error);

        return response(
            'Data tidak dapat dibaca. Periksa koneksi dan query.',
            500
        );
    }
})->name('kegiatan.index');

/*CREATE*/
Route::post('/kegiatan', function (Request $request) {
    bersihkanInputKegiatan($request);
    $data = $request->validate(aturanKegiatan(), pesanKegiatan());

    try {
        $pdo = DB::connection()->getPdo();

        if (!jenisTersedia($pdo, (int) $data['jenis_kegiatan_id'])) {
            return back()->withInput()->with(
                'gagal',
                'Jenis kegiatan tidak tersedia.'
            );
        }

        $cek = $pdo->prepare(
            'SELECT kode_kegiatan, email_pic FROM kegiatan
             WHERE kode_kegiatan = :kode OR email_pic = :email'
        );
        $cek->execute([
            'kode' => $data['kode_kegiatan'],
            'email' => $data['email_pic'],
        ]);
        $ditemukan = $cek->fetch(\PDO::FETCH_ASSOC);

        if ($ditemukan) {
            $pesan = strcasecmp($ditemukan['kode_kegiatan'], $data['kode_kegiatan']) === 0
                ? 'Kode kegiatan sudah digunakan.'
                : 'Email penanggung jawab sudah digunakan.';

            return back()->withInput()->with('gagal', $pesan);
        }

        $simpan = $pdo->prepare(
            'INSERT INTO kegiatan
             (kode_kegiatan, nama_kegiatan, email_pic, kuota, jenis_kegiatan_id)
             VALUES (:kode, :nama, :email, :kuota, :jenis)'
        );
        $simpan->execute([
            'kode' => $data['kode_kegiatan'],
            'nama' => $data['nama_kegiatan'],
            'email' => $data['email_pic'],
            'kuota' => $data['kuota'],
            'jenis' => $data['jenis_kegiatan_id'],
        ]);

        return redirect()->route('kegiatan.index')->with(
            'berhasil',
            'Data kegiatan berhasil ditambahkan.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal disimpan. Periksa koneksi dan data.'
        );
    }
})->name('kegiatan.store');

/*FORM EDIT*/
Route::get('/kegiatan/{id}/edit', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();
        $ambilKegiatan = $pdo->prepare(
            'SELECT id, kode_kegiatan, nama_kegiatan, email_pic,
                    kuota, jenis_kegiatan_id
             FROM kegiatan WHERE id = :id'
        );
        $ambilKegiatan->execute(['id' => $id]);
        $kegiatan = $ambilKegiatan->fetch(\PDO::FETCH_ASSOC);

        if (!$kegiatan) {
            return redirect()->route('kegiatan.index')->with(
                'gagal',
                'Data kegiatan tidak ditemukan.'
            );
        }

        $ambilJenis = $pdo->prepare(
            'SELECT id, nama_jenis FROM jenis_kegiatan ORDER BY nama_jenis'
        );
        $ambilJenis->execute();
        $daftarJenis = $ambilJenis->fetchAll(\PDO::FETCH_ASSOC);

        return view('kegiatan-edit', compact('kegiatan', 'daftarJenis'));
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('kegiatan.index')->with(
            'gagal',
            'Data gagal dibuka untuk diubah.'
        );
    }
})->name('kegiatan.edit');

/*UPDATE*/
Route::put('/kegiatan/{id}', function (Request $request, int $id) {
    bersihkanInputKegiatan($request);
    $data = $request->validate(aturanKegiatan(), pesanKegiatan());

    try {
        $pdo = DB::connection()->getPdo();

        if (!jenisTersedia($pdo, (int) $data['jenis_kegiatan_id'])) {
            return back()->withInput()->with(
                'gagal',
                'Jenis kegiatan tidak tersedia.'
            );
        }

        $cek = $pdo->prepare(
            'SELECT kode_kegiatan, email_pic FROM kegiatan
             WHERE (kode_kegiatan = :kode OR email_pic = :email)
               AND id <> :id'
        );
        $cek->execute([
            'kode' => $data['kode_kegiatan'],
            'email' => $data['email_pic'],
            'id' => $id,
        ]);
        $ditemukan = $cek->fetch(\PDO::FETCH_ASSOC);

        if ($ditemukan) {
            $pesan = strcasecmp($ditemukan['kode_kegiatan'], $data['kode_kegiatan']) === 0
                ? 'Kode kegiatan sudah digunakan kegiatan lain.'
                : 'Email penanggung jawab sudah digunakan kegiatan lain.';

            return back()->withInput()->with('gagal', $pesan);
        }

        $ubah = $pdo->prepare(
            'UPDATE kegiatan
             SET kode_kegiatan = :kode, nama_kegiatan = :nama,
                 email_pic = :email, kuota = :kuota,
                 jenis_kegiatan_id = :jenis
             WHERE id = :id'
        );
        $ubah->execute([
            'kode' => $data['kode_kegiatan'],
            'nama' => $data['nama_kegiatan'],
            'email' => $data['email_pic'],
            'kuota' => $data['kuota'],
            'jenis' => $data['jenis_kegiatan_id'],
            'id' => $id,
        ]);

        return redirect()->route('kegiatan.index')->with(
            'berhasil',
            'Data kegiatan berhasil diperbarui.'
        );
    } catch (\Throwable $error) {
        report($error);

        return back()->withInput()->with(
            'gagal',
            'Data gagal diperbarui. Periksa koneksi dan data.'
        );
    }
})->name('kegiatan.update');

/*DELETE*/
Route::delete('/kegiatan/{id}', function (int $id) {
    try {
        $pdo = DB::connection()->getPdo();
        $hapus = $pdo->prepare('DELETE FROM kegiatan WHERE id = :id');
        $hapus->execute(['id' => $id]);

        if ($hapus->rowCount() === 0) {
            return redirect()->route('kegiatan.index')->with(
                'gagal',
                'Data kegiatan tidak ditemukan.'
            );
        }

        return redirect()->route('kegiatan.index')->with(
            'berhasil',
            'Data kegiatan berhasil dihapus.'
        );
    } catch (\Throwable $error) {
        report($error);

        return redirect()->route('kegiatan.index')->with(
            'gagal',
            'Data gagal dihapus. Periksa relasi atau koneksi.'
        );
    }
})->name('kegiatan.destroy');