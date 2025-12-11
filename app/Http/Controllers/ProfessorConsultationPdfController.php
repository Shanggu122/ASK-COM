<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ProfessorConsultationPdfController extends Controller
{
    public function download(Request $request)
    {
        $professor = Auth::guard("professor")->user();
        // Expect the frontend to send an array of logs; if not present, try to build from session or abort.
        $logs = $request->input("logs", []);
        if (!is_array($logs) || empty($logs)) {
            return response()->json(["error" => "No consultation logs supplied"], 422);
        }
        $logs = array_map(function ($log) {
            if (!is_array($log)) {
                $log = (array) $log;
            }
            $log["remarks"] = isset($log["remarks"]) ? trim((string) $log["remarks"]) : "";
            return $log;
        }, $logs);
        // Sort by date then student (assuming date is a printable string)
        usort($logs, function ($a, $b) {
            $da = strtotime($a["date"] ?? "") ?: 0;
            $db = strtotime($b["date"] ?? "") ?: 0;
            if ($da === $db) {
                return strcmp($a["student"] ?? "", $b["student"] ?? "");
            }
            return $da <=> $db;
        });

        $deptMap = [
            1 => "IT&IS",
            2 => "COMSCI",
        ];
        $departmentLabel = isset($professor->Dept_ID)
            ? $deptMap[$professor->Dept_ID] ?? "N/A"
            : "N/A";

        // Optional header meta for the printable form
        $semester = (string) $request->input("semester", "");
        $syStart = (string) $request->input("sy_start", "");
        $syEnd = (string) $request->input("sy_end", "");
        $term = (string) $request->input("term", ""); // expected values: prelim|midterm|finals

        $data = [
            "professor" => $professor,
            "department" => $departmentLabel,
            "logs" => $logs,
            "total" => count($logs),
            "generated" => now()->format("M d, Y h:i A"),
            "semester" => $semester,
            "syStart" => $syStart,
            "syEnd" => $syEnd,
            "term" => strtolower($term),
        ];

        // Render HTML first so we can debug/normalize before DomPDF sees it
        $html = view("pdf.professor-consultations", $data)->render();

        // Save a copy for troubleshooting
        try {
            \Illuminate\Support\Facades\Storage::disk("local")->put("last_prof_pdf.html", $html);
        } catch (\Throwable $e) {
            // ignore storage write errors
        }

        // Temporary hardening: if PNGs are present and GD is NOT available, strip them to avoid DomPDF PNG/GD crash
        if (!extension_loaded("gd") && stripos($html, ".png") !== false) {
            try {
                \Illuminate\Support\Facades\Log::warning(
                    "PDF HTML contains PNG images; stripping to avoid GD crash",
                );
                // Use DOM to remove any <img> whose src contains .png
                $dom = new \DOMDocument("1.0", "UTF-8");
                // Suppress warnings due to HTML5 tags and load without extra html/body wrappers
                @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                $imgs = $dom->getElementsByTagName("img");
                // Because NodeList is live, collect to array first
                $toRemove = [];
                foreach ($imgs as $img) {
                    if ($img instanceof \DOMElement) {
                        $src = $img->getAttribute("src");
                        if (stripos($src, ".png") !== false) {
                            $toRemove[] = $img;
                        }
                    }
                }
                foreach ($toRemove as $n) {
                    if ($n->parentNode) {
                        $n->parentNode->removeChild($n);
                    }
                }
                // Sanitize inline style url(...png...) occurrences
                $xpath = new \DOMXPath($dom);
                foreach ($xpath->query("//*[@style]") as $el) {
                    if ($el instanceof \DOMElement) {
                        $style = $el->getAttribute("style");
                        $clean = preg_replace("/url\([^)]*\.png[^)]*\)/i", "none", $style);
                        if ($clean !== null) {
                            $el->setAttribute("style", $clean);
                        }
                    }
                }
                $html = $dom->saveHTML();
            } catch (\Throwable $e) {
                // If DOM operations fail, fall back to a simple CSS url() neutralizer
                $tmp = preg_replace("/url\([^)]*\.png[^)]*\)/i", "none", $html);
                if ($tmp !== null) {
                    $html = $tmp;
                }
            }
        }

        $pdf = Pdf::loadHTML($html)->setPaper("A4", "portrait");
        $filename =
            "consultation_logs_" .
            ($professor->Prof_ID ?? "professor") .
            "_" .
            now()->format("Ymd_His") .
            ".pdf";
        return $pdf->download($filename);
    }
}
