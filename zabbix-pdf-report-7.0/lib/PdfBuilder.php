<?php
// lib/PdfBuilder.php — Versión robusta con logos opcionales.

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/Settings.php';

class PdfBuilder
{
    /**
     * @param array $entries Arreglo de gráficos, cada uno con ['title' => '...', 'png' => '/ruta/absoluta/al/grafico.png']
     * @param string $outfile Ruta absoluta donde se guardará el PDF.
     * @param string $engine 'dompdf' o 'wkhtmltopdf'.
     * @param array $analysis Datos para la sección de análisis final
     *        (generada por generate.php): ['rows'=>[host,name,units,min,avg,max,last,dir], 'obs'=>[...], 'from','to','hosts','items'].
     * @throws RuntimeException Si ocurren errores críticos durante la generación.
     */
    public static function build(array $entries, string $outfile, $engine = 'dompdf', array $analysis = []): void
    {
        if (empty($entries)) {
            throw new RuntimeException('No hay graficos para generar el PDF.');
        }

        // 1. Prepara las imágenes de los gráficos
        $imgs = [];
        foreach ($entries as $i => $e) {
            $p = isset($e['png']) ? (string)$e['png'] : '';
            if ($p === '' || !is_file($p)) {
                // En lugar de fallar, podríamos registrar un aviso y continuar, pero por ahora mantenemos el error.
                throw new RuntimeException('PNG faltante para la entrada #'.$i);
            }
            $bin = @file_get_contents($p);
            if ($bin === false || strlen($bin) < 100) {
                throw new RuntimeException('PNG ilegible o vacio en #'.$i);
            }
            $imgs[] = [
                'title' => (string)($e['title'] ?? ''),
                'b64'   => 'data:image/png;base64,'.base64_encode($bin),
            ];
        }

        // ==================== INICIO DE LA CORRECCIÓN DE LOGOS ====================

        // 2. Logo personalizado (opcional) — รูปจากหน้าตั้งค่า (settings.php) มาก่อน,
        //    ถ้าไม่ได้ตั้งใช้ CUSTOM_LOGO_PATH จาก config.php
        $custom_logo_b64 = self::logoDataUri(
            Settings::logoPath() !== '' ? Settings::logoPath() : (defined('CUSTOM_LOGO_PATH') ? CUSTOM_LOGO_PATH : '')
        );

        // 3. Logo del pie de página — FOOTER_LOGO_PATH en config.php.
        //    '' o sin definir = logo de Zabbix por defecto; 'none' = ocultar; otra ruta = ese archivo.
        if (defined('FOOTER_LOGO_PATH') && FOOTER_LOGO_PATH === 'none') {
            $zabbix_logo_b64 = '';
        } elseif (defined('FOOTER_LOGO_PATH') && FOOTER_LOGO_PATH !== '') {
            $zabbix_logo_b64 = self::logoDataUri(FOOTER_LOGO_PATH);
        } else {
            $zabbix_logo_b64 = self::logoDataUri('assets/Zabbix_logo.png');
        }

        // ===================== FIN DE LA CORRECCIÓN DE LOGOS ======================

        // 4. Construye el HTML
        $html = self::buildHtml($imgs, $custom_logo_b64, $zabbix_logo_b64, $analysis);

        // 5. Genera el PDF con el motor seleccionado
        $eng = $engine ?: 'dompdf';
        if ($eng === 'wkhtmltopdf') {
            self::buildWithWkhtml($html, $outfile);
        } else {
            self::buildWithDompdf($html, $outfile);
        }

        if (!is_file($outfile) || filesize($outfile) < 1000) {
            throw new RuntimeException('PDF vacio o no generado.');
        }
    }

    /** จัดรูปค่าตัวเลขตามหน่วยของ item (%, B, bps, s, unixtime, uptime)
     *  สำหรับตารางวิเคราะห์ท้ายรายงาน — ใช้เฉพาะอักขระที่ฟอนต์ Sarabun มี */
    public static function fmtValue($v, string $units = ''): string
    {
        if ($v === null || !is_numeric($v)) return '-';
        $v = (float)$v;
        $sign = ($v < 0) ? '-' : '';
        $x = abs($v);
        switch ($units) {
            case '%':
                return $sign . number_format($x, 1, '.', '') . '%';
            case 'B': case 'Bps':
                $u = ['B', 'KB', 'MB', 'GB', 'TB']; $i = 0;
                while ($x >= 1024 && $i < 4) { $x /= 1024; $i++; }
                return $sign . number_format($x, 1, '.', '') . ' ' . $u[$i] . ($units === 'Bps' ? '/s' : '');
            case 'bps':
                $u = ['bps', 'Kbps', 'Mbps', 'Gbps']; $i = 0;
                while ($x >= 1000 && $i < 3) { $x /= 1000; $i++; }
                return $sign . number_format($x, 1, '.', '') . ' ' . $u[$i];
            case 's':
                if ($x >= 86400) return $sign . number_format($x / 86400, 1, '.', '') . ' d';
                if ($x >= 3600)  return $sign . number_format($x / 3600, 1, '.', '') . ' h';
                if ($x >= 60)    return $sign . number_format($x / 60, 1, '.', '') . ' min';
                return $sign . number_format($x, 2, '.', '') . ' s';
            case 'unixtime':
                return date('Y-m-d H:i', (int)$v);
            case 'uptime':
                $d = floor($x / 86400); $h = floor(($x - $d * 86400) / 3600);
                return $sign . $d . 'd ' . $h . 'h';
            default:
                $dec = ($x >= 1000) ? 0 : 2;
                return $sign . number_format($x, $dec, '.', '') . ($units !== '' ? ' ' . $units : '');
        }
    }

    /** Devuelve un data URI base64 (PNG/JPEG según extensión), o '' si el archivo no existe. */
    private static function logoDataUri(string $relativePath): string
    {
        if ($relativePath === '') return '';
        $abs = __DIR__ . '/../' . $relativePath;
        if (!is_file($abs)) return '';
        $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
    }

    private static function buildHtml(array $imgs, string $customLogoB64, string $zabbixLogoB64, array $analysis = []): string
    {

        // Sarabun (soporta tailandés) se registra programáticamente en
        // buildWithDompdf() vía FontMetrics::registerFont — dompdf 1.x no
        // procesa @font-face con data URI. Esta ranura queda para el CSS.
        $sarabun = '';

        $blocks = [];
        $toc = [];
        $n = 1;
        
        foreach ($imgs as $img) {
            $id = 'g'.$n++;
            $t = htmlspecialchars($img['title'], ENT_QUOTES, 'UTF-8');
            $toc[] = ['id' => $id, 'title' => $t];
            $blocks[] = [
                'id' => $id,
                'title' => $t,
                'content' => '<div class="chart-block">'.
                           '<h2 id="'.$id.'" class="chart-title">'.$t.'</h2>'.
                           '<div class="chart-container">'.
                           '<img src="'.$img['b64'].'" class="chart-image" />'.
                           '</div></div>'
            ];
        }

        // --- สรุปและวิเคราะห์ข้อมูลท้ายรายงาน (เริ่มหน้าใหม่) ---
        // คำนวณก่อนสารบัญ เพื่อให้แถว "สรุปและวิเคราะห์ข้อมูล" เข้า TOC ได้
        $analysisHtml = '';
        if (!empty($analysis) && !empty($analysis['rows'])) {
            $tbl = '';
            foreach ((array)$analysis['rows'] as $r) {
                $dirKey = ($r['dir'] ?? '') === 'up' ? 'pdf_trend_up'
                        : (($r['dir'] ?? '') === 'down' ? 'pdf_trend_down' : 'pdf_trend_stable');
                $tbl .= '<tr>'
                    .'<td class="a-item">'.htmlspecialchars((string)$r['name'], ENT_QUOTES, 'UTF-8').'</td>'
                    .'<td class="a-host">'.htmlspecialchars((string)$r['host'], ENT_QUOTES, 'UTF-8').'</td>'
                    .'<td class="a-num">'.self::fmtValue($r['min'], (string)($r['units'] ?? '')).'</td>'
                    .'<td class="a-num">'.self::fmtValue($r['avg'], (string)($r['units'] ?? '')).'</td>'
                    .'<td class="a-num">'.self::fmtValue($r['max'], (string)($r['units'] ?? '')).'</td>'
                    .'<td class="a-num">'.(isset($r['last']) && $r['last'] !== null ? self::fmtValue($r['last'], (string)($r['units'] ?? '')) : '-').'</td>'
                    .'<td class="a-trend">'.t($dirKey).'</td>'
                    .'</tr>';
            }
            $obsHtml = '';
            foreach ((array)($analysis['obs'] ?? []) as $o) {
                $obsHtml .= '<li>'.htmlspecialchars((string)$o, ENT_QUOTES, 'UTF-8').'</li>';
            }
            $overview = strtr(t('pdf_analysis_overview'), [
                '{HOSTS}' => (string)($analysis['hosts'] ?? 0),
                '{ITEMS}' => (string)($analysis['items'] ?? 0),
                '{FROM}'  => (string)($analysis['from'] ?? ''),
                '{TO}'    => (string)($analysis['to'] ?? ''),
            ]);
            $analysisHtml = '<div class="analysis-block">'
                .'<h2 id="analysis" class="analysis-title">'.htmlspecialchars(t('pdf_analysis_title'), ENT_QUOTES, 'UTF-8').'</h2>'
                .'<p class="analysis-overview">'.htmlspecialchars($overview, ENT_QUOTES, 'UTF-8').'</p>'
                .'<table class="analysis-table"><thead><tr>'
                .'<th class="a-item">'.t('pdf_col_item').'</th>'
                .'<th class="a-host">'.t('pdf_col_host').'</th>'
                .'<th>'.t('pdf_col_min').'</th>'
                .'<th>'.t('pdf_col_avg').'</th>'
                .'<th>'.t('pdf_col_max').'</th>'
                .'<th>'.t('pdf_col_last').'</th>'
                .'<th>'.t('pdf_col_trend').'</th>'
                .'</tr></thead><tbody>'.$tbl.'</tbody></table>'
                .(!empty($obsHtml)
                    ? '<h3 class="analysis-sub">'.t('pdf_analysis_obs_title').'</h3><ul class="analysis-obs">'.$obsHtml.'</ul>'
                    : '')
                .'</div>';
        }

        $tocHtml = '<div class="toc-container">'.
                  '<h2 class="toc-title">' . t('pdf_toc_title') . '</h2>'.
                  '<table class="toc-table"><tbody>';

        foreach ($toc as $entry) {
            $target = '#'.$entry['id'];
            $tocHtml .= '<tr class="toc-row">'
                       .'<td class="toc-title-cell"><a href="'.$target.'" class="toc-link">'.$entry['title'].'</a></td>'
                       .'<td class="toc-dots-cell"><span class="dots"></span></td>'
                       .'<td class="toc-page-cell"><span class="toc-page" data-target="'.$target.'"></span></td>'
                       .'</tr>';
        }
        // หน้าสรุป/วิเคราะห์ท้ายรายงานก็ขึ้นในสารบัญ (กรณีมีข้อมูลสถิติ)
        if ($analysisHtml !== '') {
            $tocHtml .= '<tr class="toc-row">'
                       .'<td class="toc-title-cell"><a href="#analysis" class="toc-link">'.htmlspecialchars(t('pdf_analysis_title'), ENT_QUOTES, 'UTF-8').'</a></td>'
                       .'<td class="toc-dots-cell"><span class="dots"></span></td>'
                       .'<td class="toc-page-cell"><span class="toc-page" data-target="#analysis"></span></td>'
                       .'</tr>';
        }
        $tocHtml .= '</tbody></table></div>';

        $content = '';
        foreach ($blocks as $block) {
            $content .= $block['content'];
        }

        // --- CÓDIGO HTML MODIFICADO PARA LOGOS OPCIONALES ---
        $customLogoHtml = $customLogoB64 ? '<img src="'.$customLogoB64.'" alt="Logo">' : '';
        $zabbixLogoHtml = $zabbixLogoB64 ? '<img src="'.$zabbixLogoB64.'" alt="Zabbix Logo">' : '';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>' . t('pdf_main_title') . '</title>
            <style>
                ' . $sarabun . '
                @page { margin: 80px 50px 60px 50px; }
                body { font-family: "Sarabun", Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.5; margin: 0; padding: 0; }
                .header { position: fixed; top: -60px; left: 0; right: 0; height: 60px; padding: 10px 50px; display: flex; align-items: center; border-bottom: 1px solid #ddd; background: white; }
                .header img { height: 40px; }
                .footer { position: fixed; bottom: -40px; left: 0; right: 0; height: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; display: flex; justify-content: center; align-items: center; gap: 10px; background: white; padding: 5px 0; }
                .footer img { height: 20px; }
                .footer .separator { color: #ccc; }
                .chart-block { margin: 0 0 20px 0; page-break-inside: avoid; padding: 10px 0 20px 0; border-bottom: 1px solid #f0f0f0; }
                .toc-container { margin-bottom: 30px; }
                .toc-title { color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 5px; margin-bottom: 15px; }
                .toc-table { width: 100%; border-collapse: collapse; table-layout: auto; }
                .toc-table td { padding: 4px 0; }
                .toc-row { height: auto; line-height: 1; vertical-align: middle; }
                .toc-title-cell { width: auto; padding: 2px 6px 2px 0; white-space: nowrap; word-break: keep-all; hyphens: none; overflow: hidden; text-overflow: ellipsis; }
                .toc-dots-cell { width: 100%; overflow: hidden; }
                .toc-dots-cell .dots { display: block; height: 0; margin: 0 4px; border-bottom: 1px dotted #9ab0be; }
                .toc-page-cell { width: 40px; text-align: right; padding-left: 6px; white-space: nowrap; }
                .toc-link { color: #2c3e50; text-decoration: none; display: inline-block; max-width: 100%; white-space: nowrap; word-break: keep-all; hyphens: none; overflow: hidden; text-overflow: ellipsis; }
                .toc-link:hover { color: #1a5276; text-decoration: underline; }
                .toc-page { color: #2c3e50; font-size: 0.9em; text-align: right; }
                .toc-page:after { content: target-counter(attr(data-target), page); }
                .chart-title { color: #1a5276; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-bottom: 15px; line-height: 1.7; }
                /* Espacio extra arriba: los signos vocálicos y tonos del tailandés
                   sobresalen por encima del line box y quedarían tapados por la cabecera. */
                .content { padding-top: 12px; }
                h1 { margin: 10px 0 15px; line-height: 1.7; }
                .chart-container { width: 100%; text-align: center; }
                .chart-image { max-width: 100%; height: auto; margin: 0 auto; display: block; }
                /* สรุปและวิเคราะห์ท้ายรายงาน */
                .analysis-block { page-break-before: always; margin-top: 10px; }
                .analysis-title { color: #1a5276; border-bottom: 2px solid #1a5276; padding-bottom: 5px; margin-bottom: 15px; line-height: 1.7; }
                .analysis-overview { font-size: 11px; color: #555; margin: 0 0 10px; }
                .analysis-table { width: 100%; border-collapse: collapse; font-size: 9px; }
                .analysis-table th, .analysis-table td { border: 1px solid #ccc; padding: 3px 5px; text-align: right; }
                .analysis-table th { background: #eef3f7; color: #1a5276; }
                .analysis-table td.a-item, .analysis-table th.a-item { text-align: left; word-break: break-word; }
                .analysis-table td.a-host, .analysis-table th.a-host { text-align: left; }
                .analysis-sub { color: #1a5276; margin: 15px 0 5px; line-height: 1.7; }
                .analysis-obs { font-size: 11px; margin: 0; padding-left: 18px; }
            </style>
        </head>
        <body>
            <div class="header">' . $customLogoHtml . '</div>
            <div class="content">
                <h1>' . t('pdf_main_title') . '</h1>
                '.$tocHtml.'
                '.$content.'
                '.$analysisHtml.'
            </div>
            <div class="footer">
                <span>' . t('pdf_generated_on') . ' '.date('d/m/Y H:i:s').'</span>
                <span class="separator">|</span>
                <span>' . t('pdf_author_credit') . '</span>
                ' . $zabbixLogoHtml . '
            </div>
            <script type="text/php">
                if (isset($pdf)) {
                    $text = "' . t('pdf_page_x_of_y') . '";
                    $font = $fontMetrics->get_font("Sarabun, Arial, sans-serif", "normal");
                    $size = 8;
                    $y = $pdf->get_height() - 20;
                    $x = $pdf->get_width() - 50 - $fontMetrics->get_text_width($text, $font, $size);
                    $pdf->page_text($x, $y, $text, $font, $size);
                }
            </script>
        </body>
        </html>';

        return $html;
    }

    private static function buildWithDompdf(string $html, string $outfile): void
    {
        $autoload = __DIR__ . '/../vendor/autoload.php';
        if (!is_file($autoload)) {
            throw new RuntimeException('Dompdf no esta disponible. Instala con: composer require dompdf/dompdf');
        }
        require_once $autoload;

        $options = new Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isPhpEnabled', true);
        // dompdf solo deja cargar archivos locales dentro de su chroot
        // (por defecto, su propia carpeta). Se amplía al raíz de la app
        // para poder registrar las fuentes de assets/fonts.
        $options->set('chroot', array_values(array_filter([
            realpath(__DIR__ . '/..'),
            realpath(__DIR__ . '/../vendor/dompdf/dompdf'),
        ])));

        $dompdf = new Dompdf\Dompdf($options);

        // Registra Sarabun (normal/bold) para que el HTML pueda usar
        // font-family: Sarabun y renderizar texto en tailandés.
        $fontMetrics = $dompdf->getFontMetrics();
        foreach (['Regular' => 'normal', 'Bold' => 'bold'] as $variant => $weight) {
            $fontFile = realpath(__DIR__ . '/../assets/fonts/Sarabun-' . $variant . '.ttf');
            if ($fontFile !== false && is_file($fontFile)) {
                $fontMetrics->registerFont(
                    ['family' => 'Sarabun', 'weight' => $weight, 'style' => 'normal'],
                    $fontFile
                );
            }
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($outfile, $dompdf->output());
    }

    private static function buildWithWkhtml(string $html, string $outfile): void
    {
        $tmp = (defined('APP_TMP') ? APP_TMP : sys_get_temp_dir()).DIRECTORY_SEPARATOR.'html_'.uniqid().'.html';
        file_put_contents($tmp, $html);
        $cmd = 'wkhtmltopdf --enable-local-file-access --quiet --margin-top 70 --margin-bottom 40 --header-html "about:blank" --footer-html "about:blank" '.escapeshellarg($tmp).' '.escapeshellarg($outfile).' 2>&1';
        exec($cmd, $out, $rc);
        @unlink($tmp);
        if ($rc !== 0) {
            throw new RuntimeException('wkhtmltopdf fallo rc='.$rc);
        }
    }
}