<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaris Laboratorium</title>
</head>
<body>
    <h1>Sistem Inventaris Laboratorium</h1>

    @if ($gagal)
        <p>{{ $gagal }}</p>
    @endif


    <h2>Daftar Inventaris</h2>

    <form action="/inventaris" method="GET">
        <label for="cari">Pencarian:</label>
        <input
            type="text"
            id="cari"
            name="cari"
            value="{{ request('cari') }}"
            placeholder="Kode, nama, atau nomor aset"
        >

        <label for="ruang_id">Filter Ruang:</label>
        <select id="ruang_id" name="ruang_id">
            <option value="">Semua Ruang</option>

            @foreach ($ruang as $r)
                <option
                    value="{{ $r->id }}"
                    {{ request('ruang_id') == $r->id ? 'selected' : '' }}
                >
                    {{ $r->nama_ruang }}
                </option>
            @endforeach
        </select>

        <button type="submit">Cari</button>
        <a href="/inventaris">Reset</a>
    </form>

    <br>

    
        <button type="submit">Cari</button>
        <a href="/inventaris">Reset</a>
    </form>

    <br>

    <!-- FORM TAMBAH INVENTARIS, MULAI -->
    <hr>

    <h2>Tambah Inventaris</h2>

    @if (session('sukses'))
        <p>{{ session('sukses') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="/inventaris" method="POST">
        @csrf

        <p>
            <label for="kode_barang">Kode Barang:</label>
            <input type="text" id="kode_barang" name="kode_barang"
                value="{{ old('kode_barang') }}" maxlength="10" required>
        </p>

        <p>
            <label for="nama_barang">Nama Barang:</label>
            <input type="text" id="nama_barang" name="nama_barang"
                value="{{ old('nama_barang') }}" maxlength="100" required>
        </p>

        <p>
            <label for="nomor_aset">Nomor Aset:</label>
            <input type="text" id="nomor_aset" name="nomor_aset"
                value="{{ old('nomor_aset') }}" maxlength="30" required>
        </p>

        <p>
            <label for="tahun_perolehan">Tahun Perolehan:</label>
            <input type="number" id="tahun_perolehan" name="tahun_perolehan"
                value="{{ old('tahun_perolehan') }}" min="2000" max="2026" required>
        </p>

        <p>
            <label for="ruang_id">Ruang:</label>
            <select id="ruang_id" name="ruang_id" required>
                <option value="">Pilih Ruang</option>
                @foreach ($ruang as $r)
                    <option value="{{ $r->id }}"
                        {{ old('ruang_id') == $r->id ? 'selected' : '' }}>
                        {{ $r->nama_ruang }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Simpan Data</button>
    </form>

    <hr>
    <!-- FORM TAMBAH INVENTARIS, SELESAI -->

    <table border="1" cellpadding="5">

    
    <table border="1" cellpadding="5">

        <thead>
            <tr>
                <th>No.</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Nomor Aset</th>
                <th>Tahun Perolehan</th>
                <th>Ruang</th>
                <th>Status Usia Aset</th>
                <th>Aksi</th>
                <th>Status Usia Aset</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->kode_barang }}</td>
                    <td>{{ $item->nama_barang }}</td>
                    <td>{{ $item->nomor_aset }}</td>
                    <td>{{ $item->tahun_perolehan }}</td>
                    <td>{{ $item->nama_ruang }}</td>
                    <td>{{ statusUsiaAset($item->tahun_perolehan) }}</td>
                    <td>
                        <a href="/inventaris/{{ $item->id }}/edit">Edit</a>

                        <form action="/inventaris/{{ $item->id }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada data inventaris.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>