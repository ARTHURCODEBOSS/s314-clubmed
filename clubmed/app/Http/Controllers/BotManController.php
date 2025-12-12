<?php

namespace App\Http\Controllers;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Http\Request;
use App\Services\GeminiService; // IMPORTANT : On importe le service Gemini ici

class BotManController extends Controller
{
    protected $geminiService;

    /**
     * Injection du service Gemini dans le constructeur.
     * Laravel va automatiquement créer l'instance de GeminiService.
     */
    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Point d'entrée principal du Chatbot
     */
    public function handle()
    {
        $botman = app('botman');

        // On écoute n'importe quel message avec '{message}'
        $botman->hears('{message}', function($botman, $message) {
            
            // 1. Effet visuel "en train d'écrire" (facultatif mais sympa)
            $botman->typesAndWaits(1);

            // 2. On envoie le message à Gemini via notre Service
            // C'est ici que l'IA traite la demande (Club Med, ski, soleil, etc.)
            $aiResponse = $this->geminiService->askGemini($message);

            // 3. Le bot répond avec le texte généré par Gemini
            $botman->reply($aiResponse);

        });

        $botman->listen();
    }
}