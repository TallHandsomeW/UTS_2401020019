<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

function statusUsiaAset($tahun)
{
    if ($tahun >= 2023) {
        return 'Baru';
    }

    return 'Lama';
}

Route::get('/', function () {
    return redirect('/inventaris');
});

Route::get('/inventaris', function () {
    $data = [];
    $ruang = [];
    $gagal = null;

    try {
        $pdo = DB::connection()->getPdo();

        $cari = trim(strip_tags(request('cari', '')));
        $ruangId = request('ruang_id', '');

        $stmt = $pdo->prepare(
            "SELECT inventaris.*, ruang.nama_ruang
             FROM inventaris
             INNER JOIN ruang ON inventaris.ruang_id = ruang.id
             WHERE (
                 inventaris.kode_barang LIKE :kode
                 OR inventaris.nama_barang LIKE :nama
                 OR inventaris.nomor_aset LIKE :aset
             )
             AND (:ruang_id = '' OR inventaris.ruang_id = :ruang_id_filter)
             ORDER BY inventaris.id ASC"
        );

        $stmt->execute([
            'kode' => '%' . $cari . '%',
            'nama' => '%' . $cari . '%',
            'aset' => '%' . $cari . '%',
            'ruang_id' => $ruangId,
            'ruang_id_filter' => $ruangId,
        ]);

        $data = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $stmtRuang = $pdo->prepare(
            "SELECT id, nama_ruang FROM ruang ORDER BY nama_ruang ASC"
        );

        $stmtRuang->execute();
        $ruang = $stmtRuang->fetchAll(\PDO::FETCH_OBJ);

    } catch (\Throwable $e) {
        report($e);
        $gagal = 'Terjadi kesalahan pada server. Silakan coba lagi.';
    }

    return view('inventaris.index', [
        'data' => $data,
        'ruang' => $ruang,
        'gagal' => $gagal,
    ]);
});


Route::post('/inventaris', function () {
    $input = [
        'kode_barang' => trim(strip_tags(request('kode_barang', ''))),
        'nama_barang' => trim(strip_tags(request('nama_barang', ''))),
        'nomor_aset' => trim(strip_tags(request('nomor_aset', ''))),
        'tahun_perolehan' => trim(request('tahun_perolehan', '')),
        'ruang_id' => trim(request('ruang_id', '')),
    ];

    $errors = [];

    if ($input['kode_barang'] === '') {
        $errors['kode_barang'] = 'Kode barang wajib diisi.';
    } elseif (strlen($input['kode_barang']) !== 10) {
        $errors['kode_barang'] = 'Kode barang harus tepat 10 karakter.';
    }

    if ($input['nama_barang'] === '') {
        $errors['nama_barang'] = 'Nama barang wajib diisi.';
    } elseif (strlen($input['nama_barang']) > 100) {
        $errors['nama_barang'] = 'Nama barang maksimal 100 karakter.';
    }

    if ($input['nomor_aset'] === '') {
        $errors['nomor_aset'] = 'Nomor aset wajib diisi.';
    } elseif (strlen($input['nomor_aset']) > 30) {
        $errors['nomor_aset'] = 'Nomor aset maksimal 30 karakter.';
    }

    if (
        $input['tahun_perolehan'] === ''
        || !ctype_digit($input['tahun_perolehan'])
        || (int) $input['tahun_perolehan'] < 2000
        || (int) $input['tahun_perolehan'] > 2026
    ) {
        $errors['tahun_perolehan'] =
            'Tahun perolehan harus berupa angka dari 2000 sampai 2026.';
    }

    if (
        $input['ruang_id'] === ''
        || !ctype_digit($input['ruang_id'])
        || (int) $input['ruang_id'] < 1
    ) {
        $errors['ruang_id'] = 'Ruang wajib dipilih.';
    }

    if (!empty($errors)) {
        return redirect('/inventaris')
            ->withErrors($errors)
            ->withInput();
    }

    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare(
            'SELECT id FROM inventaris
             WHERE kode_barang = :kode'
        );
        $stmt->execute(['kode' => $input['kode_barang']]);

        if ($stmt->fetch()) {
            $errors['kode_barang'] = 'Kode barang sudah digunakan.';
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM inventaris
             WHERE nomor_aset = :aset'
        );
        $stmt->execute(['aset' => $input['nomor_aset']]);

        if ($stmt->fetch()) {
            $errors['nomor_aset'] = 'Nomor aset sudah digunakan.';
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM ruang WHERE id = :id'
        );
        $stmt->execute(['id' => (int) $input['ruang_id']]);

        if (!$stmt->fetch()) {
            $errors['ruang_id'] = 'Ruang yang dipilih tidak tersedia.';
        }

        if (!empty($errors)) {
            return redirect('/inventaris')
                ->withErrors($errors)
                ->withInput();
        }

        $stmt = $pdo->prepare(
            'INSERT INTO inventaris
                (kode_barang, nama_barang, nomor_aset,
                 tahun_perolehan, ruang_id, created_at, updated_at)
             VALUES
                (:kode, :nama, :aset, :tahun, :ruang, NOW(), NOW())'
        );

        $stmt->execute([
            'kode' => $input['kode_barang'],
            'nama' => $input['nama_barang'],
            'aset' => $input['nomor_aset'],
            'tahun' => (int) $input['tahun_perolehan'],
            'ruang' => (int) $input['ruang_id'],
        ]);

        return redirect('/inventaris')
            ->with('sukses', 'Data inventaris berhasil ditambahkan.');

    } catch (\Throwable $e) {
        report($e);

        return redirect('/inventaris')
            ->withErrors([
                'server' => 'Data gagal disimpan. Silakan periksa data dan coba lagi.'
            ])
            ->withInput();
    }
});


Route::get('/inventaris/{id}/edit', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare(
            'SELECT * FROM inventaris WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        $item = $stmt->fetch(\PDO::FETCH_OBJ);

        if (!$item) {
            abort(404);
        }

        $stmtRuang = $pdo->prepare(
            'SELECT id, nama_ruang FROM ruang ORDER BY nama_ruang ASC'
        );
        $stmtRuang->execute();

        $ruang = $stmtRuang->fetchAll(\PDO::FETCH_OBJ);

        return view('inventaris.edit', [
            'item' => $item,
            'ruang' => $ruang,
        ]);
    } catch (\Throwable $e) {
        report($e);
        abort(500);
    }
});


Route::put('/inventaris/{id}', function ($id) {
    $input = [
        'kode_barang' => trim(strip_tags(request('kode_barang', ''))),
        'nama_barang' => trim(strip_tags(request('nama_barang', ''))),
        'nomor_aset' => trim(strip_tags(request('nomor_aset', ''))),
        'tahun_perolehan' => trim(request('tahun_perolehan', '')),
        'ruang_id' => trim(request('ruang_id', '')),
    ];

    $errors = [];

    if ($input['kode_barang'] === '') {
        $errors['kode_barang'] = 'Kode barang wajib diisi.';
    } elseif (strlen($input['kode_barang']) !== 10) {
        $errors['kode_barang'] = 'Kode barang harus tepat 10 karakter.';
    }

    if ($input['nama_barang'] === '') {
        $errors['nama_barang'] = 'Nama barang wajib diisi.';
    } elseif (strlen($input['nama_barang']) > 100) {
        $errors['nama_barang'] = 'Nama barang maksimal 100 karakter.';
    }

    if ($input['nomor_aset'] === '') {
        $errors['nomor_aset'] = 'Nomor aset wajib diisi.';
    } elseif (strlen($input['nomor_aset']) > 30) {
        $errors['nomor_aset'] = 'Nomor aset maksimal 30 karakter.';
    }

    if (
        $input['tahun_perolehan'] === ''
        || !ctype_digit($input['tahun_perolehan'])
        || (int) $input['tahun_perolehan'] < 2000
        || (int) $input['tahun_perolehan'] > 2026
    ) {
        $errors['tahun_perolehan'] =
            'Tahun perolehan harus berupa angka dari 2000 sampai 2026.';
    }

    if (
        $input['ruang_id'] === ''
        || !ctype_digit($input['ruang_id'])
        || (int) $input['ruang_id'] < 1
    ) {
        $errors['ruang_id'] = 'Ruang wajib dipilih.';
    }

    if (!empty($errors)) {
        return redirect("/inventaris/{$id}/edit")
            ->withErrors($errors)
            ->withInput();
    }

    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare(
            'SELECT id FROM inventaris WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        if (!$stmt->fetch()) {
            abort(404);
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM inventaris
             WHERE kode_barang = :kode AND id <> :id'
        );
        $stmt->execute([
            'kode' => $input['kode_barang'],
            'id' => $id,
        ]);

        if ($stmt->fetch()) {
            $errors['kode_barang'] = 'Kode barang sudah digunakan.';
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM inventaris
             WHERE nomor_aset = :aset AND id <> :id'
        );
        $stmt->execute([
            'aset' => $input['nomor_aset'],
            'id' => $id,
        ]);

        if ($stmt->fetch()) {
            $errors['nomor_aset'] = 'Nomor aset sudah digunakan.';
        }

        $stmt = $pdo->prepare(
            'SELECT id FROM ruang WHERE id = :ruang'
        );
        $stmt->execute(['ruang' => (int) $input['ruang_id']]);

        if (!$stmt->fetch()) {
            $errors['ruang_id'] = 'Ruang yang dipilih tidak tersedia.';
        }

        if (!empty($errors)) {
            return redirect("/inventaris/{$id}/edit")
                ->withErrors($errors)
                ->withInput();
        }

        $stmt = $pdo->prepare(
            'UPDATE inventaris SET
                kode_barang = :kode,
                nama_barang = :nama,
                nomor_aset = :aset,
                tahun_perolehan = :tahun,
                ruang_id = :ruang,
                updated_at = NOW()
             WHERE id = :id'
        );

        $stmt->execute([
            'kode' => $input['kode_barang'],
            'nama' => $input['nama_barang'],
            'aset' => $input['nomor_aset'],
            'tahun' => (int) $input['tahun_perolehan'],
            'ruang' => (int) $input['ruang_id'],
            'id' => $id,
        ]);

        return redirect('/inventaris')
            ->with('sukses', 'Data inventaris berhasil diperbarui.');

    } catch (\Throwable $e) {
        report($e);

        return redirect("/inventaris/{$id}/edit")
            ->withErrors([
                'server' => 'Data gagal diperbarui. Silakan coba lagi.'
            ])
            ->withInput();
    }
});


Route::delete('/inventaris/{id}', function ($id) {
    try {
        $pdo = DB::connection()->getPdo();

        $stmt = $pdo->prepare(
            'SELECT id FROM inventaris WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        if (!$stmt->fetch()) {
            return redirect('/inventaris')
                ->withErrors([
                    'server' => 'Data inventaris tidak ditemukan.'
                ]);
        }

        $stmt = $pdo->prepare(
            'DELETE FROM inventaris WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return redirect('/inventaris')
            ->with('sukses', 'Data inventaris berhasil dihapus.');

    } catch (\Throwable $e) {
        report($e);

        return redirect('/inventaris')
            ->withErrors([
                'server' => 'Data gagal dihapus. Silakan coba lagi.'
            ]);
    }
});