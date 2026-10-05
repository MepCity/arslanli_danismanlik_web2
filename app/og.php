<?php
declare(strict_types=1);

/**
 * Paylaşım kartları (Open Graph görseli): /og/{tür}/{ad}.png
 *   tür: sayfa (statik sayfalar, ad = adresin sadeleştirilmiş hali, ana sayfa = "ana-sayfa"), hizmet (ad = hizmet adresi),
 *        yazi (ad = yazı adresi), ilan (ad = ilan adresi; yalnızca açık ilan)
 * 1200×630 PNG, GD ile üretilir ve storage/og/ altında önbelleğe alınır. İçerik (başlık, açıklama, renk) değişince kart da yenilenir.
 *
 * Tasarım sitenin Evrak diliyle aynıdır: kâğıt zemin, arkada kanun metni, üstüne hafif eğik bir evrak sayfası; sayfada künye satırı
 * (daktilo), büyük başlık (Archivo, dar ve kalın), açıklama (Newsreader) ve hizmet dosyalarında dosyanın telli klasör rengi.
 *
 * @var array $m  index.php'deki eşleşme: [1] tür, [2] ad
 */

const OG_W = 1200;
const OG_H = 630;
const OG_VERSION = 1;

$type = $m[1];
$name = $m[2];

/** Türkçe büyük harf: i → İ, ı → I */
function og_upper(string $s): string
{
    return mb_strtoupper(strtr($s, ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
}

/** Telli dosya renkleri (assets/css/app.css: --f-*). */
function og_file_colors(): array
{
    return ['blue' => '8ea8d4', 'green' => '9fbb98', 'red' => 'dc7c6d', 'orange' => 'e39b62', 'lilac' => 'b2a5d0', 'yellow' => 'e8cd68',
        'grey' => 'b8b5ad', 'manila' => 'd9c69c', 'pink' => 'e2aab0'];
}

/**
 * Kartın içeriği: [künye, başlık, açıklama, kapak görseli dosyası|null, sekme rengi (hex)] ya da bulunamazsa null.
 * Metinler sayfanın kendi başlık ve açıklamasından gelir; panelden değişince kart da değişir.
 */
function og_spec(string $type, string $name): ?array
{
    $tab = 'd9c69c';
    if ($type === 'hizmet') {
        $s = services()[$name] ?? null;
        if (!$s) return null;
        return [folio_word() . ' ' . svc_no($s) . ' · ' . pg_name('hizmetler'), (string) $s['title'], (string) $s['short'], null, og_file_colors()[$s['color']] ?? $tab];
    }
    if ($type === 'yazi') {
        $p = posts()[$name] ?? null;
        if (!$p) return null;
        $img = null;
        $src = (string) ($p['image'] ?? '');
        if ($src !== '') {
            $file = str_contains($src, '/') ? ROOT . '/' . ltrim($src, '/') : ROOT . '/assets/img/blog/' . $src . '.webp';
            $img  = is_file($file) ? $file : null;
        }
        return [($p['category'] ?? pg_name('blog')) . ' · ' . tr_date((string) $p['date']), (string) $p['title'], (string) ($p['excerpt'] ?? ''), $img, $tab];
    }
    if ($type === 'ilan') {
        $x = ilan_public_find($name);
        if ($x === null || !ilan_active($x)) return null;
        return [pg_label('kariyer', 'KT-' . strtoupper(substr((string) $x['id'], 0, 5))), (string) $x['title'], implode(' · ', ilan_meta($x)), null, 'dc7c6d'];
    }
    if ($type === 'sayfa') {
        foreach (array_keys(site_static_routes()) as $path) {
            if (($path === '' ? 'ana-sayfa' : slugify((string) $path)) !== $name) continue;
            $r = seo_render_page((string) $path);
            if ($r === null) return null;
            $p = $r['page'];
            if ($path === '') {
                return [pg_label('home'), str_replace("\n", ' ', t('home.mercek.baslik')), t('home.mercek.giris'), null, $tab];
            }
            $key = array_search((string) $path, array_column(site_pages_all(), 'path', 'key'), true);
            return [$key !== false ? pg_label((string) $key) : (string) cfg('name'), (string) $p['title'], (string) $p['description'], null, $tab];
        }
    }
    return null;
}

/** Metni verilen genişliğe sığacak satırlara böler. */
function og_wrap(string $text, string $font, float $size, int $width): array
{
    $lines = [];
    $line  = '';
    foreach (preg_split('/\s+/u', trim($text)) as $word) {
        $try = $line === '' ? $word : $line . ' ' . $word;
        $box = imagettfbbox($size, 0, $font, $try);
        if ($line !== '' && ($box[2] - $box[0]) > $width) {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $try;
        }
    }
    if ($line !== '') $lines[] = $line;
    return $lines;
}

/** Satırları en fazla $max satıra sığdırır; taşarsa son satırı üç noktayla kısaltır. */
function og_clamp(array $lines, int $max, string $font, float $size, int $width): array
{
    if (count($lines) <= $max) return $lines;
    $lines = array_slice($lines, 0, $max);
    $last  = $lines[$max - 1];
    while (mb_strlen($last) > 1) {
        $box = imagettfbbox($size, 0, $font, $last . '…');
        if (($box[2] - $box[0]) <= $width) break;
        $last = rtrim(mb_substr($last, 0, mb_strrpos($last, ' ') ?: mb_strlen($last) - 1));
    }
    $lines[$max - 1] = $last . '…';
    return $lines;
}

function og_color($im, string $hex, int $alpha = 0): int
{
    $hex = ltrim($hex, '#');
    return imagecolorallocatealpha($im, hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)), $alpha);
}

/** Kartı çizer ve PNG baytlarını döndürür. */
function og_draw(array $spec): string
{
    [$eyebrow, $title, $sub, $cover, $tabHex] = $spec;
    $F = ['display' => APP . '/fonts/og-display.ttf', 'label' => APP . '/fonts/og-label.ttf', 'serif' => APP . '/fonts/og-serif.ttf',
          'italic' => APP . '/fonts/og-serif-italic.ttf', 'type' => APP . '/fonts/og-type.ttf'];

    // Zemin: kâğıt; sağda silik kanun metni (ana sayfadaki mercek duvarının karşılığı)
    $im = imagecreatetruecolor(OG_W, OG_H);
    imagealphablending($im, true);
    imagefilledrectangle($im, 0, 0, OG_W, OG_H, og_color($im, 'eeeae1'));
    $law = (array) (site('hero_law')['blocks'] ?? []);
    $wall = trim(implode(' ', array_map(fn($b) => (string) $b['law'], $law)));
    if ($wall !== '') {
        $y = 70;
        imagettftext($im, 15, 0, 664, 52, og_color($im, '1a1a1d', 20), $F['label'], og_upper((string) ($law[0]['ref'] ?? '')));
        foreach (og_wrap($wall . ' ' . $wall, $F['serif'], 21, 500) as $l) {
            if ($y > OG_H + 20) break;
            imagettftext($im, 21, 0, 664, $y, og_color($im, '7a766d', 40), $F['serif'], $l);
            $y += 33;
        }
    }
    // Logo (koyu, kâğıt üstünde)
    $logo = @imagecreatefrompng(ROOT . '/assets/img/logo.png');
    if ($logo) {
        $lw = 214; $lh = (int) round(imagesy($logo) * $lw / imagesx($logo));
        imagealphablending($im, true);
        imagecopyresampled($im, $logo, 48, 34, 0, 0, $lw, $lh, imagesx($logo), imagesy($logo));
    }

    // Evrak sayfası: ayrı bir tuvalde çizilir, gölgelenir ve hafif eğik yerleştirilir
    $SW = 1030; $SH = 470;
    $sheet = imagecreatetruecolor($SW, $SH);
    imagealphablending($sheet, true);
    imagefilledrectangle($sheet, 0, 0, $SW, $SH, og_color($sheet, 'f9f7f2'));
    $pad = 48;
    $textW = $SW - 2 * $pad;
    if ($cover) {   // yazıların kapağı: sayfanın sağ yanında
        $src = @imagecreatefromstring((string) file_get_contents($cover));
        if ($src) {
            $cw = 340;
            $sw = imagesx($src); $sh = imagesy($src);
            $scale = max($cw / $sw, $SH / $sh);
            $dw = (int) ceil($sw * $scale); $dh = (int) ceil($sh * $scale);
            imagecopyresampled($sheet, $src, $SW - $cw - (int) (($dw - $cw) / 2), -(int) (($dh - $SH) / 2), 0, 0, $dw, $dh, $sw, $sh);
            imagefilledrectangle($sheet, 0, 0, $SW - $cw - 1, $SH, og_color($sheet, 'f9f7f2'));
            $textW = $SW - $cw - 2 * $pad + 10;
        }
    }
    // Telli dosya sekmesi: sayfanın sol kenarında renkli şerit (hizmet dosyalarında dosyanın rengi)
    imagefilledrectangle($sheet, 0, 0, 14, $SH, og_color($sheet, $tabHex));
    imagefilledrectangle($sheet, 14, 0, 15, $SH, og_color($sheet, '1a1a1d', 100));

    // Künye (daktilo): "Evrak 03.1 · Hizmetler"
    imagettftext($sheet, 20, 0, $pad + 6, 62, og_color($sheet, '7a766d'), $F['italic'], $eyebrow);
    // Başlık: üç satıra sığacak en büyük boy
    $up = og_upper($title);
    $size = 88;
    $need = $sub !== '' ? 2 * 38 + 14 : 0;   // açıklamaya en az iki satır yer kalsın
    do {
        $lines = og_wrap($up, $F['display'], $size, $textW);
        $fits  = count($lines) <= 3 && 78 + (int) round($size * 1.18) * count($lines) + $need <= SH_LIMIT;
        $size -= 4;
    } while (!$fits && $size > 48);
    $size += 4;
    $lines = og_clamp($lines, 3, $F['display'], $size, $textW);
    $lh = (int) round($size * 1.18);
    $y = 78 + $lh;
    foreach ($lines as $l) {
        imagettftext($sheet, $size, 0, $pad + 4, $y, og_color($sheet, '1a1a1d'), $F['display'], $l);
        $y += $lh;
    }
    // Açıklama
    $y -= (int) round($lh * 0.3);
    $room = (int) floor((396 - $y) / 38) + 1;
    if ($sub !== '' && $room > 0) {
        $subLines = og_clamp(og_wrap($sub, $F['serif'], 25, $textW), min(3, $room), $F['serif'], 25, $textW);
        $y += 6;
        foreach ($subLines as $l) {
            imagettftext($sheet, 25, 0, $pad + 6, $y, og_color($sheet, '47464a'), $F['serif'], $l);
            $y += 38;
        }
    }
    // Alt çizgi ve alan adı
    imagefilledrectangle($sheet, $pad + 6, $SH - 60, $SW - $pad - ($cover ? 350 : 0), $SH - 59, og_color($sheet, '1a1a1d', 96));
    $host = (string) parse_url((string) cfg('url'), PHP_URL_HOST);
    imagettftext($sheet, 19, 0, $pad + 6, $SH - 26, og_color($sheet, '1a1a1d'), $F['label'], $host);
    $box = imagettfbbox(18, 0, $F['type'], (string) cfg('phone'));
    imagettftext($sheet, 18, 0, $pad + 6 + (int) (imagettfbbox(19, 0, $F['label'], $host)[2]) + 28, $SH - 26, og_color($sheet, '7a766d'), $F['type'], (string) cfg('phone'));

    // Gölge + eğim
    $pw = $SW + 80; $ph = $SH + 80;
    $layer = imagecreatetruecolor($pw, $ph);
    imagesavealpha($layer, true);
    imagealphablending($layer, false);
    imagefill($layer, 0, 0, imagecolorallocatealpha($layer, 0, 0, 0, 127));
    $shadow = imagecreatetruecolor($pw, $ph);
    imagefill($shadow, 0, 0, imagecolorallocate($shadow, 255, 255, 255));
    imagefilledrectangle($shadow, 44, 58, 44 + $SW, 58 + $SH, imagecolorallocate($shadow, 150, 146, 138));
    for ($i = 0; $i < 6; $i++) imagefilter($shadow, IMG_FILTER_GAUSSIAN_BLUR);
    imagealphablending($layer, false);
    for ($yy = 0; $yy < $ph; $yy += 1) {
        for ($xx = 0; $xx < $pw; $xx += 1) {
            $v = imagecolorat($shadow, $xx, $yy) & 0xFF;
            $a = (int) round(127 - (255 - $v) / 255 * 127 * 0.9);
            if ($a < 127) imagesetpixel($layer, $xx, $yy, imagecolorallocatealpha($layer, 70, 60, 40, min(127, $a)));
        }
    }
    imagealphablending($layer, true);
    imagecopy($layer, $sheet, 40, 40, 0, 0, $SW, $SH);
    $rot = imagerotate($layer, 1.4, imagecolorallocatealpha($layer, 0, 0, 0, 127));
    imagealphablending($im, true);
    imagecopy($im, $rot, (int) (62 - (imagesx($rot) - $pw) / 2) - 40, (int) (122 - (imagesy($rot) - $ph) / 2) - 40, 0, 0, imagesx($rot), imagesy($rot));

    ob_start();
    imagepng($im, null, 6);
    return (string) ob_get_clean();
}
const SH_LIMIT = 410;

/* ---------- İstek ---------- */

$spec = function_exists('imagettftext') ? og_spec($type, $name) : null;
if ($spec === null) {
    // Bilinmeyen kart ya da GD yok: genel paylaşım görseline yönlendir
    redirect(asset('img/og.jpg'), 302);
}

$dir = ROOT . '/storage/og';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$hash = substr(md5(OG_VERSION . json_encode($spec) . cfg('url') . cfg('phone') . ($spec[3] ? filemtime($spec[3]) : '') . json_encode(site('hero_law'))), 0, 12);
$file = $dir . '/' . $type . '-' . $name . '-' . $hash . '.png';
if (!is_file($file)) {
    foreach (glob($dir . '/' . $type . '-' . $name . '-*.png') ?: [] as $old) @unlink($old);
    $png = og_draw($spec);
    @file_put_contents($file, $png, LOCK_EX);
} else {
    $png = (string) file_get_contents($file);
}

$etag = '"' . $hash . '"';
header('Content-Type: image/png');
header('Cache-Control: public, max-age=604800');
header('ETag: ' . $etag);
if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) {
    http_response_code(304);
    exit;
}
header('Content-Length: ' . strlen($png));
echo $png;
