<?php
$chartId = $chart_id ?? 'parking-chart';
$chartRows = $chart_rows ?? [];
$chartDatasets = $chart_datasets ?? [];
$chartCurrency = !empty($chart_currency);
$plotLeft = 58;
$plotRight = 735;
$plotTop = 22;
$plotBottom = 204;
$plotHeight = $plotBottom - $plotTop;
$plotWidth = $plotRight - $plotLeft;
$maxValue = 0.0;

foreach ($chartDatasets as $dataset) {
    foreach ($dataset['values'] as $value) {
        $maxValue = max($maxValue, (float) $value);
    }
}

$axisMax = $chartCurrency
    ? ($maxValue > 0 ? max(1000, ceil($maxValue * 1.2)) : 1000)
    : ($maxValue > 0 ? max(4, ceil($maxValue * 1.2)) : 4);
$tickStep = $axisMax / 4;
$axisFormatter = static function (float $value) use ($chartCurrency): string {
    if (!$chartCurrency) {
        return (string) (int) round($value);
    }
    if ($value >= 1000000) {
        return 'Rp ' . rtrim(rtrim(number_format($value / 1000000, 1, ',', '.'), '0'), ',') . ' jt';
    }
    if ($value >= 1000) {
        return 'Rp ' . rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',') . ' rb';
    }
    return 'Rp ' . number_format($value, 0, ',', '.');
};
?>
<section class="card chart-card">
    <div class="card-body p-4">
        <div class="section-heading">
            <div>
                <h2><?= e($chart_title ?? 'Aktivitas parkir') ?></h2>
                <p><?= e($chart_subtitle ?? 'Ringkasan aktivitas tujuh hari terakhir.') ?></p>
            </div>
            <span class="chart-period"><i class="bi bi-calendar3 me-1"></i>7 hari</span>
        </div>
        <div class="chart-scroll">
            <svg class="activity-chart" viewBox="0 0 760 250" role="img" aria-labelledby="<?= e($chartId) ?>-title <?= e($chartId) ?>-description">
                <title id="<?= e($chartId) ?>-title"><?= e($chart_title ?? 'Aktivitas parkir') ?></title>
                <desc id="<?= e($chartId) ?>-description"><?= e($chart_subtitle ?? 'Grafik tujuh hari terakhir') ?></desc>
                <?php for ($tick = 0; $tick <= 4; $tick++): $y = $plotBottom - ($tick / 4 * $plotHeight); $value = $tickStep * $tick; ?>
                    <line class="chart-gridline" x1="<?= $plotLeft ?>" y1="<?= round($y, 2) ?>" x2="<?= $plotRight ?>" y2="<?= round($y, 2) ?>"></line>
                    <text class="chart-axis-label" x="<?= $plotLeft - 12 ?>" y="<?= round($y + 3, 2) ?>" text-anchor="end"><?= e($axisFormatter($value)) ?></text>
                <?php endfor; ?>
                <?php foreach ($chartRows as $index => $row): $x = $plotLeft + ($chartRows ? $index * ($plotWidth / max(1, count($chartRows) - 1)) : 0); ?>
                    <text class="chart-x-label" x="<?= round($x, 2) ?>" y="232" text-anchor="middle"><?= e($row['label']) ?></text>
                <?php endforeach; ?>
                <?php foreach ($chartDatasets as $dataset): ?>
                    <?php
                    $points = [];
                    foreach ($dataset['values'] as $index => $value) {
                        $x = $plotLeft + ($index * ($plotWidth / max(1, count($dataset['values']) - 1)));
                        $y = $plotBottom - (min($axisMax, (float) $value) / $axisMax * $plotHeight);
                        $points[] = ['x' => round($x, 2), 'y' => round($y, 2)];
                    }
                    $path = '';
                    foreach ($points as $index => $point) {
                        $path .= ($index === 0 ? 'M ' : ' L ') . $point['x'] . ' ' . $point['y'];
                    }
                    ?>
                    <?php if ($path !== ''): ?>
                        <path class="chart-line" d="<?= e($path) ?>" stroke="<?= e($dataset['color']) ?>"></path>
                        <?php foreach ($points as $point): ?>
                            <circle class="chart-point" cx="<?= $point['x'] ?>" cy="<?= $point['y'] ?>" r="4" fill="<?= e($dataset['color']) ?>"></circle>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </svg>
        </div>
        <div class="chart-legend">
            <?php foreach ($chartDatasets as $dataset): ?>
                <span><i style="--legend-color: <?= e($dataset['color']) ?>"></i><?= e($dataset['label']) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>
