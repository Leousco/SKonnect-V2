<?php

class AIController
{
    public function handleMessage(string $message, int $residentId): array
    {
        // The resident ID is supplied by the authenticated route for future use.
        if ($residentId < 1 || trim($message) === '') {
            return ['success' => false, 'message' => 'Invalid chat request.'];
        }

        return [
            'success' => true,
            'message' => 'Chatbot backend connected successfully. AI responses are not enabled yet.',
        ];
    }
}
