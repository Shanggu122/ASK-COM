<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProfanityDetector
{
    /**
     * @var string[]
     */
    private array $keywordList = [
        // Tagalog profanity / insults
        "amputa",
        "animal ka",
        "tangina",
        "tang ina",
        "putangina",
        "putang ina",
        "putang",
        "puking ina",
        "puking ina mo",
        "ina mo",
        "inamo",
        "inamoko",
        "binibrocha",
        "bogo",
        "boto",
        "brocha",
        "king ina",
        "puta",
        "ulol",
        "ulul",
        "gago",
        "gagi",
        "gaga",
        "bobo",
        "bwisit",
        "bwishet",
        "bwesit",
        "pakshet",
        "pakshit",
        "pakingshet",
        "leche",
        "leching",
        "lechugas",
        "lintik",
        "kantot",
        "kantotan",
        "kantutan",
        "kantut",
        "iyot",
        "iyutin",
        "iniyot",
        "hindot",
        "hindutan",
        "habal",
        "hayop ka",
        "hayup",
        "hinampak",
        "hinayupak",
        "hudas",
        "inutel",
        "inutil",
        "kagaguhan",
        "kagang",
        "puke",
        "puking",
        "pukinangina",
        "pekpek",
        "tite",
        "titi",
        "tete",
        "teti",
        "burat",
        "bayag",
        "kupal",
        "kiki",
        "kikinginamo",
        "libog",
        "kalibugan",
        "tigang",
        "pakantot",
        "bilat",
        "kayat",
        "kaululan",
        "nakakaburat",
        "nimal",
        "ogag",
        "olok",
        "pakyu",
        "pesteng yawa",
        "poke",
        "poki",
        "pokpok",
        "poyet",
        "pu'keng",
        "pucha",
        "puchanggala",
        "puchangina",
        "puki",
        "punyeta",
        "putanginamo",
        "putaragis",
        "putragis",
        "puyet",
        "ratbu",
        "shunga",
        "sira ulo",
        "siraulo",
        "suso",
        "susu",
        "tae",
        "taena",
        "tamod",
        "tanga",
        "taragis",
        "tarantado",
        "timang",
        "tinil",
        "tungaw",
        "ungas",
        "demonyo ka",
        "engot",
        // English profanity / insults and explicit words
        "arsehole",
        "asshat",
        "asshole",
        "bastard",
        "big black cock",
        "bitch",
        "bloody",
        "blowjob",
        "boobs",
        "breasts",
        "nipple",
        "cum",
        "orgasm",
        "bollocks",
        "bugger",
        "bullshit",
        "damn",
        "chicken shit",
        "ching chong",
        "clusterfuck",
        "cock",
        "cock sucker",
        "cocksucker",
        "coonass",
        "coon ass",
        "cornhole",
        "cox-zucker machine",
        "cox zucker machine",
        "cracker",
        "crap",
        "cunt",
        "darn",
        "dick",
        "douche",
        "dumbass",
        "enshittification",
        "explicit",
        "faggot",
        "fag",
        "feck",
        "fuck",
        "fuck, marry, kill",
        "fuck her right in the pussy",
        "fuck joe biden",
        "fuck joe",
        "fuck yourself",
        "fuckery",
        "fucking",
        "fuckk",
        "fuuck",
        "fuuckk",
        "grab 'em by the pussy",
        "grab em by the pussy",
        "grabbing them by the pussy",
        "healslut",
        "horny",
        "if you see kay",
        "jerk",
        "jesus fucking christ",
        "kike",
        "list of films that most frequently use the word fuck",
        "motherfucker",
        "nigga",
        "nigger",
        "nude",
        "nudes",
        "nsfw",
        "pajeet",
        "paki",
        "penis",
        "piss",
        "poof",
        "poofter",
        "porn",
        "porno",
        "pornhub",
        "pornography",
        "prick",
        "pussy",
        "ratfucking",
        "retard",
        "russian warship, go fuck yourself",
        "serving cunt",
        "sex",
        "sexual",
        "sexy",
        "shit",
        "shit happens",
        "shithouse",
        "shitposting",
        "shitter",
        "shut the fuck up",
        "shut the hell up",
        "slut",
        "son of a bitch",
        "spic",
        "take the piss",
        "taking the piss",
        "twat",
        "unclefucker",
        "use of nigger in proper names",
        "vagina",
        "wanker",
        "whore",
        "xxx",
    ];

    /**
     * @var string[]
     */
    private array $collapsedKeywords = [
        "tangina",
        "putangina",
        "putanginamo",
        "pukinginamo",
        "pukingina",
        "pukinangina",
        "kingina",
        "inamoko",
        "inamomo",
        "animalk",
        "hayopka",
        "demonyoka",
        "pestengyawa",
        "puchanggala",
        "puchangina",
        "pukeng",
        "motherfucker",
        "sonofabitch",
        "fuckk",
        "fuuck",
        "fuuckk",
        "shittt",
        "shiit",
        "shiitt",
        "shett",
        "shettt",
        "cocksucker",
        "dumbass",
        "healslut",
        "unclefucker",
        "jesusfuckingchrist",
        "shutthefuckup",
        "shutthehellup",
        "fuckherrightinthepussy",
        "grabembythepussy",
        "grabbingthembythepussy",
        "coxzuckermachine",
        "russianwarshipgofuckyourself",
        "fuckyourself",
        "fuckjoe",
        "fuckjoebiden",
        "listoffilmsthatmostfrequentlyusethewordfuck",
    ];

    private bool $aiEnabled;
    private ?string $apiKey;
    private string $baseUri;
    private float $timeout;
    private string $model;
    private float $flagThreshold;

    public function __construct()
    {
        $config = config("services.openai_moderation", []);
        $this->apiKey = $config["key"] ?? env("OPENAI_API_KEY");
        $this->aiEnabled = (bool) ($config["enabled"] ?? false);
        $this->baseUri = rtrim($config["base_uri"] ?? "https://api.openai.com", "/");
        $this->timeout = (float) ($config["timeout"] ?? 5.0);
        $this->model = $config["model"] ?? "omni-moderation-latest";
        $this->flagThreshold = (float) ($config["flag_threshold"] ?? 0.5);
    }

    public function detectsProfanity(string $message): bool
    {
        $normalized = mb_strtolower(trim($message));
        if ($normalized === "") {
            return false;
        }

        $aiDecision = $this->analyzeWithOpenAi($normalized);
        if ($aiDecision !== null) {
            return $aiDecision;
        }

        return $this->matchKeywords($normalized);
    }

    private function analyzeWithOpenAi(string $normalized): ?bool
    {
        if (!$this->shouldUseAi()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $this->apiKey,
            ])
                ->timeout($this->timeout)
                ->post($this->baseUri . "/v1/moderations", [
                    "model" => $this->model,
                    "input" => $normalized,
                ]);

            if (!$response->successful()) {
                Log::warning("ProfanityDetector: moderation request failed", [
                    "status" => $response->status(),
                    "body" => $response->body(),
                ]);
                return null;
            }

            $payload = $response->json();
            if (!is_array($payload) || empty($payload["results"][0])) {
                return null;
            }

            $result = $payload["results"][0];
            if (isset($result["flagged"])) {
                if ($result["flagged"]) {
                    return true;
                }

                if ($this->thresholdMet($result)) {
                    return true;
                }

                return false;
            }

            if ($this->thresholdMet($result)) {
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning("ProfanityDetector: moderation request error", [
                "exception" => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function thresholdMet(array $result): bool
    {
        if (isset($result["category_scores"]) && is_array($result["category_scores"])) {
            foreach ($result["category_scores"] as $score) {
                if (!is_numeric($score)) {
                    continue;
                }
                if ((float) $score >= $this->flagThreshold) {
                    return true;
                }
            }
        }

        if (isset($result["categories"]) && is_array($result["categories"])) {
            foreach ($result["categories"] as $value) {
                if (is_bool($value) && $value) {
                    return true;
                }
            }
        }

        return false;
    }

    private function shouldUseAi(): bool
    {
        return $this->aiEnabled && !empty($this->apiKey);
    }

    private function matchKeywords(string $normalized): bool
    {
        foreach ($this->keywordList as $keyword) {
            if ($keyword === "") {
                continue;
            }
            if (str_contains($normalized, $keyword)) {
                return true;
            }
        }

        $collapsed = str_replace([" ", "-", "_", "'", ",", "."], "", $normalized);
        foreach ($this->collapsedKeywords as $keyword) {
            if ($keyword === "") {
                continue;
            }
            if (str_contains($collapsed, $keyword)) {
                return true;
            }
        }

        return false;
    }
}
