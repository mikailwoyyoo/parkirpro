<?php
if (!defined('PARKING_REPORT_EXPORT') || !isset($exportRows, $summary, $start, $end, $type, $status)) {
    http_response_code(404);
    exit;
}

$xml = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
$cell = static function ($value, string $type = 'String', string $style = 'Text') use ($xml): string {
    return '<Cell ss:StyleID="' . $style . '"><Data ss:Type="' . $type . '">' . $xml($value) . '</Data></Cell>';
};
$row = static fn(array $cells): string => '<Row>' . implode('', $cells) . '</Row>';
$statusLabel = $status !== '' ? ($status === 'masuk' ? 'Sedang parkir' : 'Selesai') : 'Semua status';
$typeLabel = $type !== '' ? ucfirst($type) : 'Semua jenis';
$excelRows = [
    '<Row ss:Height="30"><Cell ss:StyleID="Title" ss:MergeAcross="10"><Data ss:Type="String">' . $xml('PARKIRPRO · LAPORAN TRANSAKSI') . '</Data></Cell></Row>',
    '<Row ss:Height="22"><Cell ss:StyleID="Subtitle" ss:MergeAcross="10"><Data ss:Type="String">' . $xml('Ringkasan operasional parkir') . '</Data></Cell></Row>',
    $row([$cell('Periode', 'String', 'Label'), $cell($start . ' s.d. ' . $end, 'String', 'Text'), $cell('Jenis kendaraan', 'String', 'Label'), $cell($typeLabel, 'String', 'Text')]),
    $row([$cell('Status', 'String', 'Label'), $cell($statusLabel, 'String', 'Text'), $cell('Dibuat pada', 'String', 'Label'), $cell(date('d-m-Y H:i'), 'String', 'Text')]),
    '<Row ss:Height="8"/>',
    $row([$cell('TOTAL TRANSAKSI', 'String', 'MetricLabel'), $cell('TRANSAKSI SELESAI', 'String', 'MetricLabel'), $cell('TOTAL PENDAPATAN', 'String', 'MetricLabel')]),
    $row([$cell((int) $summary['jumlah'], 'Number', 'MetricValue'), $cell((int) $summary['selesai'], 'Number', 'MetricValue'), $cell((float) $summary['pendapatan'], 'Number', 'MetricCurrency')]),
    '<Row ss:Height="10"/>',
    $row(array_map(static fn(string $heading): string => $cell($heading, 'String', 'Header'), ['ID', 'Plat nomor', 'Jenis', 'Pemilik', 'Area', 'Waktu masuk', 'Waktu keluar', 'Durasi (jam)', 'Status', 'Pendapatan', 'Petugas'])),
];

foreach ($exportRows as $record) {
    $excelRows[] = $row([
        $cell((int) $record['id_parkir'], 'Number', 'Number'),
        $cell($record['plat_nomor']),
        $cell(ucfirst($record['jenis_kendaraan'])),
        $cell($record['pemilik']),
        $cell($record['nama_area']),
        $cell($record['waktu_masuk']),
        $cell($record['waktu_keluar'] ?? '-'),
        $record['durasi_jam'] !== null ? $cell((int) $record['durasi_jam'], 'Number', 'Number') : $cell('-'),
        $cell($record['status'] === 'masuk' ? 'Sedang parkir' : 'Selesai'),
        $record['biaya_total'] !== null ? $cell((float) $record['biaya_total'], 'Number', 'Currency') : $cell('-', 'String', 'Text'),
        $cell($record['nama_petugas'] ?? 'Pengguna dihapus'),
    ]);
}
if (!$exportRows) {
    $excelRows[] = $row([$cell('Tidak ada transaksi pada periode dan filter terpilih.', 'String', 'Text')]);
}

$workbook = '<?xml version="1.0" encoding="UTF-8"?>'
    . '<?mso-application progid="Excel.Sheet"?>'
    . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
    . '<Styles>'
    . '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Aptos" ss:Size="10" ss:Color="#26334D"/></Style>'
    . '<Style ss:ID="Title"><Font ss:FontName="Aptos Display" ss:Size="16" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#172B55" ss:Pattern="Solid"/><Alignment ss:Vertical="Center" ss:Horizontal="Left"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#4169ED"/></Borders></Style>'
    . '<Style ss:ID="Subtitle"><Font ss:Size="10" ss:Color="#64748B" ss:Italic="1"/></Style>'
    . '<Style ss:ID="Label"><Font ss:Bold="1" ss:Color="#53627A"/><Interior ss:Color="#F1F5FB" ss:Pattern="Solid"/></Style>'
    . '<Style ss:ID="Text"><Alignment ss:Vertical="Center"/></Style>'
    . '<Style ss:ID="MetricLabel"><Font ss:Size="9" ss:Bold="1" ss:Color="#50617F"/><Interior ss:Color="#EAF0FF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
    . '<Style ss:ID="MetricValue"><Font ss:Size="15" ss:Bold="1" ss:Color="#3158DC"/><Interior ss:Color="#F7F9FF" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>'
    . '<Style ss:ID="MetricCurrency"><Font ss:Size="15" ss:Bold="1" ss:Color="#13865F"/><Interior ss:Color="#F0FAF5" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><NumberFormat ss:Format="&quot;Rp&quot; #,##0"/></Style>'
    . '<Style ss:ID="Header"><Font ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#3158A8" ss:Pattern="Solid"/><Alignment ss:Vertical="Center" ss:Horizontal="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#203F84"/></Borders></Style>'
    . '<Style ss:ID="Number"><NumberFormat ss:Format="0"/><Alignment ss:Horizontal="Right"/></Style>'
    . '<Style ss:ID="Currency"><NumberFormat ss:Format="&quot;Rp&quot; #,##0"/></Style>'
    . '</Styles><Worksheet ss:Name="Laporan"><Table>'
    . '<Column ss:Width="58"/><Column ss:Width="100"/><Column ss:Width="90"/><Column ss:Width="140"/><Column ss:Width="120"/><Column ss:Width="130"/><Column ss:Width="130"/><Column ss:Width="78"/><Column ss:Width="112"/><Column ss:Width="110"/><Column ss:Width="140"/>'
    . implode('', $excelRows)
    . '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>9</SplitHorizontal><TopRowBottomPane>9</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet></Workbook>';

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan-parkir-' . $start . '-sd-' . $end . '.xls"');
header('X-Content-Type-Options: nosniff');
echo $workbook;
exit;
