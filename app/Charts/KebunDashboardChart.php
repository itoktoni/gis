<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;

class KebunDashboardChart
{
    /**
     * Line chart satu dataset (mis. tonase kirim per hari/minggu).
     */
    public function tonase(array $labels, array $tons, string $title = 'Tonase Kirim', ?string $fontColor = null): LarapexChart
    {
        $chart = (new LarapexChart)->lineChart()
            ->setTitle($title)
            ->setSubtitle('Ton TBS — periode filter')
            ->addData(array_values($tons), 'Tonase (ton)')
            ->setXAxis(array_values($labels))
            ->setColors(['#3755c3'])
            ->setGrid()
            ->setMarkers(['#3755c3'], 4, 6);
        if ($fontColor !== null) {
            $chart->setFontColor($fontColor);
        }

        return $chart;
    }

    /**
     * Line chart Yield per Ha (kg/ha) — produksi kirim ÷ luas kebun.
     */
    public function yieldPerHa(array $labels, array $values, string $subtitle = ''): LarapexChart
    {
        return (new LarapexChart)->lineChart()
            ->setTitle('Yield per Ha')
            ->setSubtitle($subtitle ?: 'Kg/Ha — produksi kirim ÷ luas kebun')
            ->addData(array_values($values), 'Yield (kg/ha)')
            ->setXAxis(array_values($labels))
            ->setColors(['#16a34a'])
            ->setGrid()
            ->setMarkers(['#16a34a'], 4, 6);
    }
}
