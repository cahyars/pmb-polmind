{{-- Tiga sheet docs/report pmb.xlsx dalam satu bidang (juga sumber gambar PNG). Variabel: $report dari MitraFormatReport --}}
@php
    $programs = $report['programs'];
    $totals = $report['totals'];
    $academicYear = $report['academicYear'] ? 'TAHUN AKADEMIK ' . $report['academicYear'] : null;
    $waveName = mb_strtoupper($report['wave']->name);
    $cell = fn ($value) => '<span class="' . ($value > 0 ? 'mx-b' : '') . '">' . $value . '</span>';
    // Format akuntansi Excel: 300,000 dan "-" untuk nol.
    $money = fn ($value) => $value > 0 ? number_format($value, 0, '.', ',') : '-';
    $barColors = ['#FF0000', '#F1650F', '#5B9BD5', '#FFFF00', '#92D050', '#7030A0', '#FF66CC', '#00FFFF'];

    // Sumbu Y grafik: langkah 1/2/5 x 10^n, puncak satu langkah di atas nilai tertinggi (seperti Excel).
    $chartMax = max($totals['programs'] ?: [0]);
    $rough = max($chartMax / 5, 1);
    $magnitude = 10 ** floor(log10($rough));
    $step = collect([1, 2, 5, 10])->map(fn ($factor) => $factor * $magnitude)->first(fn ($candidate) => $candidate >= $rough);
    $chartTop = (floor($chartMax / $step) + 1) * $step;
    // Grafik selebar tabel ringkasan di atasnya (NO + KETERANGAN + kolom prodi + JUMLAH).
    $chart = ['width' => 42 + 220 + 72 * count($programs) + 76, 'height' => 300, 'left' => 46, 'right' => 16, 'top' => 82, 'bottom' => 40];
    $plotWidth = $chart['width'] - $chart['left'] - $chart['right'];
    $plotHeight = $chart['height'] - $chart['top'] - $chart['bottom'];
    $slot = $plotWidth / max(count($programs), 1);
@endphp

<style>
    .mx-report { width: max-content; padding: 28px; background: #fff; color: #000; font-family: Arial, 'Liberation Sans', Helvetica, sans-serif; font-size: 13px; }
    .mx-report table { border-collapse: collapse; }
    .mx-report th, .mx-report td { padding: 0 6px; text-align: center; vertical-align: middle; white-space: nowrap; }
    .mx-title { text-align: center; font-weight: 700; font-size: 16px; line-height: 1.6; margin-bottom: 16px; }
    .mx-sheet thead th { background: #5C9BD5; color: #fff; font-weight: 700; border: 3px double #1F2937; }
    .mx-sheet thead th.mx-online { background: #00B050; }
    .mx-sheet thead th.mx-wrap { white-space: normal; line-height: 1.3; }
    .mx-sheet tbody td { height: 23px; border: 1px dotted #6B7280; }
    .mx-sheet tbody td.mx-date { border-right: 1px solid #000; }
    .mx-sheet tbody td.mx-money { text-align: right; }
    .mx-sheet tfoot td { height: 24px; font-weight: 700; }
    .mx-b { font-weight: 700; }
    .mx-yellow { background: #FFFF00; }
    .mx-sum td { border-top: 1px solid #000; border-left: 1px dotted #6B7280; border-right: 1px dotted #6B7280; }
    .mx-grand td { border: 1px solid #000; }
    .mx-total td { border: 2px solid #000; border-left-style: double; border-right-style: double; }
    .mx-summary td { border: 1px solid #000; height: 22px; padding: 0 8px; }
    .mx-summary td:first-child { text-align: left; }
    .mx-chart { border: 1px solid #D9D9D9; border-radius: 2px; }
</style>

<div id="mitra-report" class="mx-report">
    <div style="display: flex; gap: 40px; align-items: flex-start;">

        {{-- ===== Sheet 1: AKUMULASI REGISTRASI PMB ===== --}}
        <section>
            <div class="mx-title">
                REKAP AKUMULASI PEMBAYARAN REGISTRASI PMB<br>
                @if($academicYear){{ $academicYear }}<br>@endif
                {{ $waveName }}
            </div>

            <table class="mx-sheet">
                <colgroup>
                    <col style="width: 42px"><col style="width: 190px">
                    <col style="width: 82px"><col style="width: 82px"><col style="width: 82px"><col style="width: 82px">
                    <col style="width: 104px"><col style="width: 112px"><col style="width: 108px">
                </colgroup>
                <thead>
                    <tr style="height: 66px">
                        <th rowspan="2">NO</th>
                        <th rowspan="2">TANGGAL</th>
                        <th colspan="2" class="mx-wrap">PENDAFTARAN MITRA INDUSTRI / AFILIASI</th>
                        <th colspan="2" class="mx-wrap">PENDAFTARAN UMUM</th>
                        <th rowspan="2" class="mx-wrap">TOTAL PENDAFTAR</th>
                        <th rowspan="2" class="mx-wrap">TOTAL UANG<br>(Rp)</th>
                        <th rowspan="2">KETERANGAN</th>
                    </tr>
                    <tr style="height: 28px">
                        <th>OFFLINE</th><th class="mx-online">ONLINE</th>
                        <th>OFFLINE</th><th class="mx-online">ONLINE</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['no'] }}</td>
                            <td class="mx-date">{{ $row['date']->translatedFormat('d F Y') }}</td>
                            <td>{!! $cell($row['partner_offline']) !!}</td>
                            <td>{!! $cell($row['partner_online']) !!}</td>
                            <td>{!! $cell($row['general_offline']) !!}</td>
                            <td>{!! $cell($row['general_online']) !!}</td>
                            <td>{!! $cell($row['total']) !!}</td>
                            <td class="mx-money">{{ $money($row['money']) }}</td>
                            <td></td>
                        </tr>
                    @empty
                        <tr><td colspan="9">Gelombang belum dimulai pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="mx-sum">
                        <td colspan="2">Jumlah</td>
                        <td>{{ $totals['partner_offline'] }}</td>
                        <td>{{ $totals['partner_online'] }}</td>
                        <td>{{ $totals['general_offline'] }}</td>
                        <td>{{ $totals['general_online'] }}</td>
                        <td rowspan="2" style="border: 1px solid #000; border-top-width: 1px">{{ $totals['total'] }}</td>
                        <td rowspan="2" style="border: 1px solid #000">{{ $money($totals['money']) }}</td>
                        <td rowspan="2" style="border: 1px solid #000"></td>
                    </tr>
                    <tr class="mx-grand">
                        <td colspan="2" class="mx-yellow">GRAND TOTAL PENDAFTAR</td>
                        <td colspan="4" class="mx-yellow">{{ $totals['total'] }}</td>
                    </tr>
                </tfoot>
            </table>

            {{-- ===== Sheet 3: GRAFIK PENGISIAN FORMULIR ===== --}}
            <div style="margin-top: 40px; display: flex; flex-direction: column; align-items: center">
                <div class="mx-title" style="font-size: 17px">
                    DATA PENGISIAN FORMULIR PMB<br>
                    @if($academicYear){{ $academicYear }}<br>@endif
                    {{ $waveName }}
                </div>

                <table class="mx-sheet">
                    <colgroup>
                        <col style="width: 42px"><col style="width: 220px">
                        @foreach($programs as $code)<col style="width: 72px">@endforeach
                        <col style="width: 76px">
                    </colgroup>
                    <thead>
                        <tr style="height: 22px">
                            <th rowspan="2">NO</th>
                            <th rowspan="2">KETERANGAN</th>
                            <th colspan="{{ count($programs) }}">PENGISIAN FORMULIR</th>
                            <th rowspan="2">JUMLAH</th>
                        </tr>
                        <tr style="height: 22px">
                            @foreach($programs as $code)<th>{{ $code }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="height: 58px">
                            <td>1</td>
                            <td style="white-space: normal; line-height: 1.3">TOTAL YANG SUDAH MENGISI FORMULIR HINGGA HARI INI</td>
                            @foreach($programs as $id => $code)
                                <td style="font-weight: 700; font-style: italic">{{ $totals['programs'][$id] }}</td>
                            @endforeach
                            <td style="font-weight: 700; font-style: italic; font-size: 21px">{{ array_sum($totals['programs']) }}</td>
                        </tr>
                    </tbody>
                </table>

                <svg class="mx-chart" style="margin-top: 14px; display: block" width="{{ $chart['width'] }}" height="{{ $chart['height'] }}"
                     viewBox="0 0 {{ $chart['width'] }} {{ $chart['height'] }}" role="img"
                     aria-label="Grafik pengisian formulir per prodi per {{ $report['date']->translatedFormat('j F Y') }}">
                    <rect width="100%" height="100%" fill="#fff" />
                    <text x="{{ $chart['width'] / 2 }}" y="32" text-anchor="middle" font-family="Calibri, Carlito, Arial, sans-serif" font-size="19" fill="#000">GRAFIK PENGISIAN FORMULIR PMB</text>
                    <text x="{{ $chart['width'] / 2 }}" y="56" text-anchor="middle" font-family="Calibri, Carlito, Arial, sans-serif" font-size="19" fill="#000">PERTANGGAL {{ mb_strtoupper($report['date']->translatedFormat('j F Y')) }}</text>

                    @for($value = 0; $value <= $chartTop; $value += $step)
                        @php $y = $chart['top'] + $plotHeight - ($value / $chartTop) * $plotHeight; @endphp
                        <line x1="{{ $chart['left'] }}" x2="{{ $chart['width'] - $chart['right'] }}" y1="{{ $y }}" y2="{{ $y }}" stroke="#D9D9D9" stroke-width="1" />
                        <text x="{{ $chart['left'] - 8 }}" y="{{ $y + 4 }}" text-anchor="end" font-family="Arial, sans-serif" font-size="11" fill="#595959">{{ $value }}</text>
                    @endfor

                    @foreach(array_values($programs) as $index => $code)
                        @php
                            $value = $totals['programs'][array_keys($programs)[$index]];
                            $barHeight = ($value / $chartTop) * $plotHeight;
                            $barWidth = $slot * 0.42;
                            $x = $chart['left'] + $slot * $index + ($slot - $barWidth) / 2;
                            $y = $chart['top'] + $plotHeight - $barHeight;
                        @endphp
                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $barHeight }}" fill="{{ $barColors[$index % count($barColors)] }}" />
                        <text x="{{ $x + $barWidth / 2 }}" y="{{ $y - 7 }}" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" font-weight="700" fill="#000">{{ $value }}</text>
                        <text x="{{ $x + $barWidth / 2 }}" y="{{ $chart['height'] - $chart['bottom'] + 18 }}" text-anchor="middle" font-family="Arial, sans-serif" font-size="13" font-weight="700" fill="#000">{{ $code }}</text>
                    @endforeach
                </svg>
            </div>
        </section>

        {{-- ===== Sheet 2: PENGISIAN FORMULIR ===== --}}
        <section>
            <div class="mx-title">
                DATA PENGISIAN FORMULIR PMB<br>
                @if($academicYear){{ $academicYear }}<br>@endif
                {{ $waveName }}
            </div>

            <table class="mx-sheet">
                <colgroup>
                    <col style="width: 42px"><col style="width: 160px">
                    @foreach($programs as $code)<col style="width: 68px">@endforeach
                    <col style="width: 72px">
                </colgroup>
                <thead>
                    <tr style="height: 52px">
                        <th rowspan="2">NO</th>
                        <th rowspan="2">TANGGAL</th>
                        <th colspan="{{ count($programs) }}">PENGISIAN FORMULIR</th>
                        <th rowspan="2">JUMLAH</th>
                    </tr>
                    <tr style="height: 42px">
                        @foreach($programs as $code)<th>{{ $code }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                        <tr>
                            <td>{{ $row['no'] }}</td>
                            <td class="mx-date">{{ $row['date']->translatedFormat('d F Y') }}</td>
                            @foreach($programs as $id => $code)
                                <td>{!! $cell($row['programs'][$id]) !!}</td>
                            @endforeach
                            <td>{!! $cell(array_sum($row['programs'])) !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($programs) + 3 }}">Gelombang belum dimulai pada tanggal ini.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="mx-total mx-yellow">
                        <td colspan="2">TOTAL</td>
                        @foreach($programs as $id => $code)<td>{{ $totals['programs'][$id] }}</td>@endforeach
                        <td>{{ array_sum($totals['programs']) }}</td>
                    </tr>
                </tfoot>
            </table>

            <p class="mx-b" style="margin: 22px 0 2px 42px">TOTAL KESELURUHAN :</p>
            <table class="mx-summary" style="margin-left: 42px">
                <tr><td style="width: 300px">Pendaftar yang sudah mengisi formulir</td><td style="width: 72px">{{ $totals['total'] }}</td></tr>
                <tr><td>Jumlah Pendaftar Terverifikasi</td><td>{{ $report['verified'] }}</td></tr>
                <tr><td>Jumlah Pendaftar yang belum Terverifikasi</td><td>{{ $report['unverified'] }}</td></tr>
            </table>

        </section>
    </div>
</div>
