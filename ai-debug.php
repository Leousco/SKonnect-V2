<?php
// Run from a terminal:  C:\xampp\php\php.exe ai-debug.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/backend/services/AIContextProvider.php';
require_once __DIR__ . '/backend/services/GeminiClient.php';

// echo "=== PORTAL DATA ===\n";
// echo (new AIContextProvider())->build(), "\n\n";

echo "=== GEMINI ===\n";
print_r((new GeminiClient())->generate('Reply in one short sentence.', 'Say hello.'));