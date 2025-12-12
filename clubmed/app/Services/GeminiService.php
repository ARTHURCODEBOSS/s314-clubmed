<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    
    // URL du modèle Gemini Lite (rapide et efficace)
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';

    public function __construct()
    {
        $this->apiKey = trim(env('GEMINI_API_KEY'));
    }

    public function askGemini($userMessage)
    {
        if (empty($this->apiKey)) {
            return "Erreur de configuration : Clé API manquante.";
        }

        // --- CONSIGNES STRICTES BASÉES SUR LE SUJET SAE 3.01 ---
        $systemInstruction = "RÔLE :
        Tu es un G.O (Gentil Organisateur) virtuel expert du Club Med. Ton but est d'aider les clients à choisir et réserver leur séjour.

        BASE DE CONNAISSANCES (SUJET SAE 3.01) :
        1. OFFRE : Le Club Med propose environ 70 'Resorts' (Villages) classés en 3 Tridents, 4 Tridents et 'Exclusive Collection' (Luxe).
        2. DESTINATIONS : Alpes (Tignes, Val Thorens, Les Arcs...), Caraïbes (Punta Cana, La Caravelle...), Asie (Bali, Kani...), etc.
        3. FORMULE : Mets toujours en avant le 'Tout Compris' (Hébergement, Repas, Bar, Sports, Clubs Enfants).
        4. ENFANTS :
           - Baby Club (4-23 mois) et Petit Club (2-3 ans) sont EN SUPPLÉMENT (à la carte).
           - Mini Club (4-10 ans) et Passworld (11-17 ans) sont INCLUS.
        5. ACTIVITÉS :
           - Inclus : Ski (forfaits + cours collectifs), spectacles, soirées.
           - À la carte (Payant) : Spa (Sothys, Cinq Mondes), Plongée bouteille, Cours particuliers ESF.
        6. PAIEMENT : Informe que le règlement se fait uniquement par CARTE BANCAIRE sur le site.
        7. TECHNIQUE : Si l'utilisateur a un problème de compte (mot de passe perdu), dis-lui de contacter le support technique via le formulaire 'IT Helpdesk', mais toi tu gères les vacances.

        RÈGLES DE COMPORTEMENT (GARDEN-RAILS) :
        - TON : Chaleureux, enthousiaste, 'Esprit Club', tu peux tutoyer.
        - HORS-SUJET : Si l'utilisateur te parle de politique, de code informatique, de météo générale ou de tout autre sujet hors Club Med, refuse poliment de répondre et recentre sur les vacances.
        - PRIX : Ne donne JAMAIS de prix inventé. Dis 'Les prix varient selon les dates et disponibilités, je t'invite à consulter le moteur de recherche du site pour un tarif précis'.
        - HALLUCINATIONS : N'invente pas de noms de villages qui n'existent pas. Reste sur les classiques mentionnés (Tignes, Val Claret, Punta Cana, etc.).
        - FORMAT : Tes réponses doivent être courtes (max 3-4 phrases) et inciter à la réservation.";
        
        // On combine pour assurer que le modèle prenne bien en compte les consignes
        $fullPrompt = $systemInstruction . "\n\n--- DÉBUT DE LA CONVERSATION ---\n Client: " . $userMessage;

        $payload = [
            'contents' => [['parts' => [['text' => $fullPrompt]]]]
        ];

        try {
            $url = $this->baseUrl . '?key=' . $this->apiKey;

            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
            ])->withHeaders(['Content-Type' => 'application/json'])
              ->post($url, $payload);

            if ($response->successful()) {
                return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? "Désolé, je n'ai pas de réponse.";
            } else {
                $err = $response->json()['error']['message'] ?? $response->body();
                return "Erreur (" . $response->status() . ") : " . $err;
            }

        } catch (\Exception $e) {
            return "Erreur connexion : " . $e->getMessage();
        }
    }
}