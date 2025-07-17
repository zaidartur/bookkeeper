<?php

namespace App\Imports;

use App\Models\BukuTamu;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Ramsey\Uuid\Uuid;

class ImportTamu implements ToCollection, WithHeadingRow, WithCalculatedFormulas, WithStartRow
{
    /**
    * @param Collection $collection
    */

    public $success = 0;
    public $total = 0;
    public $incomplete = 0;
    public $duplicate = 0;
    public $object = [];

    public function collection(Collection $rows)
    {
        $this->total = count($rows);
        foreach ($rows as $key => $row) {
            if(!empty($row['nama_lengkap']) && !empty($row['instansi']) && !empty($row['tanggal']) && !empty($row['jam_masuk']) && !empty($row['jam_keluar']) && !empty($row['keperluan'])) {
                $uuid = Uuid::uuid4()->toString();
                $data = [
                    'uuid'      => $uuid,
                    'nama'      => $row['nama_lengkap'],
                    'instansi'  => $row['instansi'],
                    'tanggal'   => $row['tanggal'],
                    'jam_masuk' => $row['jam_masuk'],
                    'jam_keluar'=> $row['jam_keluar'],
                    'keperluan' => $row['keperluan'],
                    'created_at'=> date_format(date_create($row['tanggal'] .' '. $row['jam_keluar']), 'Y-m-d H:i:s'),
                ];

                // $save = BukuTamu::insert($data);
                $save = true;
                if ($save) {
                    $this->success++;
                } else {
                    $this->incomplete++;
                }
            } else {
                $this->incomplete++;
                $this->object[] = $row;
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }
}
