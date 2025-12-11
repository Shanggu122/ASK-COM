<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consultation Monitoring Form</title>
    <style>
        /* Page and base typography */
    @page { margin: 20px 35px 30px 35px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }

        /* Header layout */
    .header-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
    .header-table td { vertical-align: top; border: 0 !important; padding: 0; }
        .header-left { /* reserved for potential spacing next to logo */ }
        .header-right-td { text-align: right; }
    .logo { height: 190px; width: auto; object-fit: contain; display: block; }
        .univ-name { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
    .office-title { text-align: right; font-size: 16px; line-height: 1.25; font-weight: 700; margin-top: 48px; }

    .divider { border-top: 1px solid #000; margin: -26px 0 6px; }

    .form-title { text-align: center; font-size: 18px; font-weight: 700; margin: 0 0 12px; text-transform: uppercase; }

    .meta-line { text-align: center; font-size: 12px; margin-bottom: 8px; line-height: 1.4; }
    .meta-line .sy, .meta-line .term { display: block; margin: 0 auto 2px; }
    .meta-line .sy { white-space: nowrap; }
    .checkbox { display:inline-block; border:1px solid #000; width:11px; height:11px; vertical-align: middle; margin: 0 4px 0 8px; position: relative; top: 2px; }

        .disclaimer {
            font-size: 12px;
            text-align: justify;
            margin: 12px 0 22px; /* bumped bottom space a bit more */
        }

    /* Faculty/Department row: use table for DomPDF compatibility */
    .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .info-table td { border: 0; padding: 0; vertical-align: bottom; width: 50%; }
        .label { font-weight: 700; }
    .line { display:inline-block; border-bottom: 1px solid #000; min-width: 200px; padding: 0 4px; }

    /* Table (unchanged structure) */
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    thead { display: table-header-group; }
    tbody { display: table-row-group; }
    th, td { border: 1px solid #000; padding: 3px 6px; line-height: 1.15; }
    th { background: #f3f3f3; font-weight: 700; }
    /* Prevent date wrapping */
    .col-date { white-space: nowrap; }
    .col-remarks { width: 140px; white-space: pre-wrap; word-break: break-word; }
    .col-student { width: 90px; }

    /* Footer signatures */
    .signature-row { display:flex; justify-content: space-between; align-items: flex-start; gap: 32px; margin-top: 24px; }
    .sig, .date-block { flex: 1; }
    .sig-table { width: 100%; border-collapse: collapse; }
    .sig-table td { border: 0; padding: 0; vertical-align: bottom; }
    .sig-title { width: 1%; font-size: 12px; font-weight: 700; text-align: left; white-space: nowrap; padding-right: 2px; }
    .sig-line { border-bottom: 1px solid #000; height: 16px; }
    .sig-line-left { width: 260px; }
    .sig-line-right { width: 280px; }
    .sig-line-cell { width: auto; padding-left: 0; }
    .sig-caption { font-size: 12px; text-align: center; padding-top: 2px; }
    .sig-master { width: 100%; border-collapse: collapse; margin-top: 24px; }
    .sig-master td { border: 0; padding: 0; vertical-align: bottom; }
    .page-break { page-break-after: always; }
    </style>
    @php
        // Header meta
        $semesterRaw = (string) ($semester ?? '');
        $semester = '';
        if (trim($semesterRaw) !== '') {
            $normalized = strtolower($semesterRaw);
            if (str_contains($normalized, 'first')) {
                $semester = '1st';
            } elseif (str_contains($normalized, 'second')) {
                $semester = '2nd';
            } elseif (str_contains($normalized, 'mid')) {
                $semester = 'Midyear';
            } else {
                $cleaned = trim($semesterRaw);
                $normalizedTrim = rtrim($normalized);
                if (substr($normalizedTrim, -8) === 'semester') {
                    $cleaned = trim(substr($cleaned, 0, -strlen('semester')));
                } elseif (substr($normalizedTrim, -4) === 'term') {
                    $cleaned = trim(substr($cleaned, 0, -strlen('term')));
                }
                $semester = $cleaned !== '' ? $cleaned : trim($semesterRaw);
            }
        }
        $syStart = $syStart ?? '';
        $syEnd = $syEnd ?? '';
        $term = strtolower($term ?? '');
        $profName = $professor->Name ?? ($professor->name ?? '');
        $dept = $department ?? '';

        // Logo selection: prefer specific LOGOMOd.jpg; fallback to PNG (if GD) then AduLogo.jpg/.jpeg
        $logoDataUri = null;
        $gdLoaded = extension_loaded('gd');
        // 1) Preferred: LOGOMOd.jpg
        $preferredLogo = public_path('images/LOGOMOd.jpg');
        if (is_file($preferredLogo)) {
            $logoDataUri = 'data:image/jpeg;base64,' . base64_encode(@file_get_contents($preferredLogo));
        }
        // 2) Fallbacks only if preferred not found
        if (!$logoDataUri) {
            if ($gdLoaded) {
                $pngPath = public_path('images/AduLogo.png');
                if (is_file($pngPath)) {
                    $logoDataUri = 'data:image/png;base64,' . base64_encode(@file_get_contents($pngPath));
                }
            }
            if (!$logoDataUri) {
                foreach (['jpg','jpeg'] as $ext) {
                    $p = public_path("images/AduLogo.$ext");
                    if (is_file($p)) {
                        $logoDataUri = 'data:image/jpeg;base64,' . base64_encode(@file_get_contents($p));
                        break;
                    }
                }
            }
        }
    @endphp
    @php
    $perPage = 20;
        // Normalize to array and filter only Completed statuses
        $allLogs = is_array($logs) ? $logs : (array)$logs;
        $filteredLogs = array_values(array_filter($allLogs, function ($log) {
            $status = '';
            if (is_array($log)) {
                $status = $log['status'] ?? '';
            } elseif (is_object($log)) {
                $status = $log->status ?? '';
            }
            return strtolower(trim((string)$status)) === 'completed';
        }));
        // Chunk only the Completed logs; ensure at least one page renders
        $chunks = count($filteredLogs) ? array_chunk($filteredLogs, $perPage) : [ [] ];
        $totalPages = count($chunks);
    @endphp

    @foreach($chunks as $pageIndex => $chunk)
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if(!empty($logoDataUri))
                        <img class="logo" src="{{ $logoDataUri }}" alt="Adamson University Logo">
                    @endif
                </td>
                <td class="header-right-td">
                    <div class="office-title">OFFICE OF THE VICE PRESIDENT<br>FOR ACADEMIC AFFAIRS</div>
                </td>
            </tr>
        </table>
        <div class="divider"></div>

        <div class="form-title">CONSULTATION MONITORING FORM</div>
        <div class="meta-line">
            <div class="sy">{{ $semester ? $semester.' Semester,' : '________ Semester,' }} SY {{ $syStart ?: '____' }} - {{ $syEnd ?: '____' }}</div>
            <div class="term">
                Term:
                <span class="checkbox">@if($term==='prelim')✓@endif</span> Prelim
                <span class="checkbox">@if($term==='midterm')✓@endif</span> Midterm
                <span class="checkbox">@if($term==='finals' || $term==='final')✓@endif</span> Finals
            </div>
        </div>

        <div class="disclaimer">
            "In compliance with the requirements of the Data Privacy Act, we would like to secure your consent on the general use and sharing of information to process your personal data for the purpose of documentation and accreditation evidence. The personal data collected herein will only be used for the purpose stated above and will be stored digitally and eventually be disposed securely after five (5) years in accordance with AdU’s retention and disposal policies."
        </div>

        <table class="info-table">
            <tr>
                <td><span class="label">Name of the Faculty:</span> <span class="line">{{ $profName ?: 'N/A' }}</span></td>
                <td><span class="label">College / Department:</span> <span class="line">{{ $dept ?: 'N/A' }}</span></td>
            </tr>
        </table>

        <table>
            <thead>
                <tr>
                    <th style="width:30px">No.</th>
                    <th class="col-student">Student</th>
                    <th>Subject</th>
                    <th style="width:120px">Date</th>
                    <th>Type</th>
                    <th style="width:55px">Mode</th>
                    <th style="width:70px">Status</th>
                    <th class="col-remarks">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($chunk as $i => $log)
                    <tr>
                        <td>{{ ($pageIndex * $perPage) + $i + 1 }}</td>
                        <td class="col-student">{!! nl2br(e($log['student'] ?? '')) !!}</td>
                        <td>{{ $log['subject'] ?? '' }}</td>
                        <td class="col-date">{{ $log['date'] ?? '' }}</td>
                        <td>{{ $log['type'] ?? '' }}</td>
                        <td>{{ $log['mode'] ?? '' }}</td>
                        <td>{{ $log['status'] ?? '' }}</td>
                        <td class="col-remarks">{!! nl2br(e($log['remarks'] ?? '')) !!}</td>
                    </tr>
                @endforeach
                @php
                    $pad = max(0, $perPage - (is_countable($chunk) ? count($chunk) : 0));
                @endphp
                @for ($r = 0; $r < $pad; $r++)
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="col-date">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="col-remarks">&nbsp;</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="sig-master">
            <tr>
                <td class="sig-title">Signature:</td>
                <td class="sig-line-cell"><div class="sig-line sig-line-left"></div></td>
                <td class="sig-title">Date Submitted:</td>
                <td class="sig-line-cell"><div class="sig-line sig-line-right"></div></td>
            </tr>
            <tr>
                <td></td>
                <td class="sig-caption">Chairperson</td>
                <td></td>
                <td></td>
            </tr>
        </table>

        @if($pageIndex < $totalPages - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
