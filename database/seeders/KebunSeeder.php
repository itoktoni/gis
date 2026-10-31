<?php

namespace Database\Seeders;

use App\Models\Kebun;
use Illuminate\Database\Seeder;

class KebunSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'kebun_nama' => 'Blok A Sungai Siak',
                'kebun_luas_ha' => 20.5,
                'kebun_jumlah_pohon' => 2870,
                'kebun_status' => 'aktif',
                'kebun_lat' => 0.55,
                'kebun_lng' => 101.45,
                'kebun_polygon' => '{"type":"Polygon","coordinates":[[[101.4477,0.5482],[101.4510,0.5475],[101.4535,0.5490],[101.4540,0.5505],[101.4525,0.5522],[101.4498,0.5525],[101.4475,0.5510],[101.4470,0.5495],[101.4477,0.5482]]]}',
            ],
            [
                'kebun_nama' => 'Blok B Sei Mangkei',
                'kebun_luas_ha' => 35.2,
                'kebun_jumlah_pohon' => 4840,
                'kebun_status' => 'aktif',
                'kebun_lat' => 3.12,
                'kebun_lng' => 99.35,
                'kebun_polygon' => '{"type":"Polygon","coordinates":[[[99.3468,3.1175],[99.3505,3.1168],[99.3532,3.1185],[99.3538,3.1205],[99.3520,3.1228],[99.3490,3.1225],[99.3470,3.1208],[99.3465,3.1190],[99.3468,3.1175]]]}',
            ],
            [
                'kebun_nama' => 'Blok C Kotawaringin',
                'kebun_luas_ha' => 52.8,
                'kebun_jumlah_pohon' => 7120,
                'kebun_status' => 'aktif',
                'kebun_lat' => -2.35,
                'kebun_lng' => 111.65,
                'kebun_polygon' => '{"type":"Polygon","coordinates":[[[111.6458,-2.3532],[111.6495,-2.3540],[111.6530,-2.3525],[111.6545,-2.3500],[111.6528,-2.3475],[111.6498,-2.3465],[111.6468,-2.3480],[111.6455,-2.3505],[111.6458,-2.3532]]]}',
            ],
            [
                'kebun_nama' => 'Blok D Musi Banyuasin',
                'kebun_luas_ha' => 18.4,
                'kebun_jumlah_pohon' => 2410,
                'kebun_status' => 'replanting',
                'kebun_lat' => -2.95,
                'kebun_lng' => 103.8,
                'kebun_polygon' => '{"type":"Polygon","coordinates":[[[103.7979,-2.9517],[103.8005,-2.9522],[103.8023,-2.9508],[103.8020,-2.9492],[103.8002,-2.9480],[103.7985,-2.9485],[103.7976,-2.9500],[103.7979,-2.9517]]]}',
            ],
            [
                'kebun_nama' => 'Blok E Sanggau',
                'kebun_luas_ha' => 27.6,
                'kebun_jumlah_pohon' => 3680,
                'kebun_status' => 'nonaktif',
                'kebun_lat' => 0.15,
                'kebun_lng' => 110.6,
                'kebun_polygon' => '{"type":"Polygon","coordinates":[[[110.5973,0.1479],[110.6000,0.1472],[110.6025,0.1485],[110.6030,0.1505],[110.6012,0.1523],[110.5988,0.1520],[110.5970,0.1502],[110.5973,0.1479]]]}',
            ],
        ];

        foreach ($rows as $row) {
            Kebun::updateOrCreate(['kebun_nama' => $row['kebun_nama']], $row);
        }
    }
}
