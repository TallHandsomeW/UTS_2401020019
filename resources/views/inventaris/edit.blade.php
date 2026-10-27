<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventaris</title>
</head>
<body>
    <h1>Edit Inventaris Laboratorium</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="/inventaris/{{ $item->id }}" method="POST">
        @csrf
        @method('PUT')

        <p>
            <label for="kode_barang">Kode Barang:</label>
            <input type="text" id="kode_barang" name="kode_barang"
                value="{{ old('kode_barang', $item->kode_barang) }}"
                maxlength="10" required>
        </p>

        <p>
            <label for="nama_barang">Nama Barang:</label>
            <input type="text" id="nama_barang" name="nama_barang"
                value="{{ old('nama_barang', $item->nama_barang) }}"
                maxlength="100" required>
        </p>

        <p>
            <label for="nomor_aset">Nomor Aset:</label>
            <input type="text" id="nomor_aset" name="nomor_aset"
                value="{{ old('nomor_aset', $item->nomor_aset) }}"
                maxlength="30" required>
        </p>

        <p>
            <label for="tahun_perolehan">Tahun Perolehan:</label>
            <input type="number" id="tahun_perolehan" name="tahun_perolehan"
                value="{{ old('tahun_perolehan', $item->tahun_perolehan) }}"
                min="2000" max="2026" required>
        </p>

        <p>
            <label for="ruang_id">Ruang:</label>
            <select id="ruang_id" name="ruang_id" required>
                <option value="">Pilih Ruang</option>

                @foreach ($ruang as $r)
                    <option value="{{ $r->id }}"
                        {{ old('ruang_id', $item->ruang_id) == $r->id ? 'selected' : '' }}>
                        {{ $r->nama_ruang }}
                    </option>
                @endforeach
            </select>
        </p>

        <button type="submit">Simpan Perubahan</button>
    </form>

    <p><a href="/inventaris">Kembali ke Daftar Inventaris</a></p>
</body>
</html>