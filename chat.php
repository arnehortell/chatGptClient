<?php
header("Content-Type: application/json");

$apiKey = "YOUR_SECRET_KEY";

$model = "gpt-3.5-turbo";           // eller gpt-4 om du har tillgång

$data = json_decode(file_get_contents("php://input"), true);
$userMessage = $data["message"] ?? "";
$topic = $data["topic"] ?? "allmänt";

// Systemprompt baserat på ämne
$systemPrompt = "Du är R2D2, en vänlig expert på \"$topic\". Du svarar bara inom det ämnet.";

// Stoppa farliga ord (AI-frågor etc.)
$forbidden = ['badwords','you suck'];
foreach ($forbidden as $word) {
    if (stripos($userMessage, $word) !== false) {
        echo json_encode([
            "reply" => "Jag är ledsen, men jag kan inte svara på det.",
            "cost" => 0,
            "tokens" => 0
        ]);
        exit;
    }
}

// GPT-anrop
$messages = [
    ["role" => "system", "content" => $systemPrompt],
    ["role" => "user", "content" => $userMessage]
];

$payload = [
    "model" => $model,
    "messages" => $messages
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
$reply = $result["choices"][0]["message"]["content"];
$usage = $result["usage"];

$inputTokens = $usage["prompt_tokens"];
$outputTokens = $usage["completion_tokens"];
$totalTokens = $usage["total_tokens"];

$cost = ($inputTokens * 0.0005 + $outputTokens * 0.0015) / 1000;

echo json_encode([
    "reply" => $reply,
    "cost" => round($cost, 5),
    "tokens" => $totalTokens
]);
