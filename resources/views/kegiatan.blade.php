<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kegiatan Mahasiswa</title>
</head>
<body>
    <h1>Data Kegiatan Mahasiswa</h1>

    @if (session('berhasil'))
        <p>{{ session('berhasil') }}</p>
    @endif
    @if (session('gagal'))
        <p>{{ session('gagal') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <h2>Tambah Kegiatan</h2>
    <form action="{{ route('kegiatan.store') }}" method="POST">
        @csrf
        <p>Kode Kegiatan<br>
            <input type="text" name="kode_kegiatan"
                   value="{{ old('kode_kegiatan') }}">
        </p>
        <p>Nama Kegiatan<br>
            <input type="text" name="nama_kegiatan"
                   value="{{ old('nama_kegiatan') }}">
        </p>
        <p>Email PIC<br>
            <input type="email" name="email_pic"
                   value="{{ old('email_pic') }}">
        </p>
        <p>Kuota<br>
            <input type="number" name="kuota" value="{{ old('kuota') }}">
        </p>
        <p>Jenis Kegiatan<br>
            <select name="jenis_kegiatan_id">
                <option value="">-- Pilih Jenis Kegiatan --</option>
                @foreach ($daftarJenis as $jenis)
                    <option value="{{ $jenis['id'] }}"
                        @selected(old('jenis_kegiatan_id') == $jenis['id'])>
                        {{ $jenis['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Tambah Data</button>
    </form>

    <h2>Pencarian dan Filter</h2>
    <form action="{{ route('kegiatan.index') }}" method="GET">
        <p>Kata Kunci<br>
            <input type="text" name="q" value="{{ $kataKunci }}">
        </p>
        <p>Jenis Kegiatan<br>
            <select name="jenis_kegiatan_id">
                <option value="">-- Semua Jenis --</option>
                @foreach ($daftarJenis as $jenis)
                    <option value="{{ $jenis['id'] }}"
                        @selected((string) $jenisId === (string) $jenis['id'])>
                        {{ $jenis['nama_jenis'] }}
                    </option>
                @endforeach
            </select>
        </p>
        <button type="submit">Cari</button>
        <a href="{{ route('kegiatan.index') }}">Reset</a>
    </form>

    <h2>Daftar Kegiatan</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr>
            <th>Kode</th><th>Nama Kegiatan</th><th>Email PIC</th>
            <th>Kuota</th><th>Status Kuota</th><th>Jenis</th><th>Aksi</th>
        </tr>
        @forelse ($daftarKegiatan as $kegiatan)
            <tr>
                <td>{{ $kegiatan['kode_kegiatan'] }}</td>
                <td>{{ $kegiatan['nama_kegiatan'] }}</td>
                <td>{{ $kegiatan['email_pic'] }}</td>
                <td>{{ $kegiatan['kuota'] }}</td>
                <td>
                    @if (statusKuota((int) $kegiatan['kuota']) === 'Besar')
                        Besar
                    @else
                        Terbatas
                    @endif
                </td>
                <td>{{ $kegiatan['nama_jenis'] }}</td>
                <td>
                    <a href="{{ route('kegiatan.edit', $kegiatan['id']) }}">
                        Ubah
                    </a>
                    <form action="{{ route('kegiatan.destroy', $kegiatan['id']) }}"
                          method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Data tidak ditemukan.</td></tr>
        @endforelse
    </table>
</body>
</html>