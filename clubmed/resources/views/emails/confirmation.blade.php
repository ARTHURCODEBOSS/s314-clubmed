<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle Réservation - Espace Manager</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            background-color: #f8f9fa; 
            margin: 0; 
            padding: 0; 
            color: #333; 
        }
        .container { 
            max-width: 600px; 
            margin: 40px auto; 
            background-color: #ffffff; 
            border-radius: 0; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
        }
        .header { 
            background-color: #ffffff; 
            color: #111827; 
            padding: 30px 25px 20px; 
            text-align: center; 
            border-bottom: 3px solid #000000; 
        }
        .header h1 { 
            margin: 0; 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 28px; 
            font-weight: 400; 
            font-style: italic; 
            color: #111827;
        }
        .content { 
            padding: 40px 30px; 
        }
        .alert-box { 
            background-color: #f3f4f6; 
            border: none;
            color: #111827; 
            padding: 15px; 
            margin-bottom: 30px; 
            text-align: center; 
            font-weight: 600; 
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px; 
        }
        .info-table td { 
            padding: 15px 10px; 
            border-bottom: 1px solid #e5e7eb; 
            font-size: 15px;
        }
        .info-table td:first-child { 
            font-weight: 600; 
            color: #6b7280; 
            width: 40%; 
        }
        .client-info { 
            background-color: #ffffff; 
            padding: 20px 0; 
            border-top: 1px solid #e5e7eb; 
            margin-top: 25px; 
        }
        .client-info h3 {
            font-family: 'Times New Roman', Times, serif;
            font-style: italic;
            font-size: 22px;
            font-weight: 400;
            color: #111827;
            margin-bottom: 15px;
        }
        .btn-container { 
            text-align: center; 
            margin-top: 30px; 
        }
        .btn-action { 
            background-color: #fbbf24; 
            color: #000000; 
            padding: 14px 35px; 
            text-decoration: none; 
            border-radius: 25px; 
            font-weight: bold; 
            font-size: 14px;
            text-transform: uppercase;
            display: inline-block; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .footer { 
            background-color: #ffffff; 
            text-align: center; 
            padding: 25px; 
            font-size: 12px; 
            color: #9ca3af; 
            border-top: 1px solid #f3f4f6; 
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Nouvelle Réservation</h1>
        </div>

        <div class="content">
            <p style="font-size: 16px; line-height: 1.6; color: #4b5563;">
                Bonjour l'équipe du <strong>{{ $reservation->club->titre ?? 'Club' }}</strong>,
            </p>
            
            <p style="font-size: 15px; line-height: 1.6; color: #4b5563; margin-bottom: 25px;">
                Une nouvelle demande de séjour a été enregistrée. Voici les détails pour validation.
            </p>

            <div class="alert-box">
                Dossier N° {{ $reservation->numreservation }}
            </div>

            <table class="info-table">
                <tr>
                    <td>Dates du séjour</td>
                    <td>Du {{ \Carbon\Carbon::parse($reservation->datedebut)->format('d/m/Y') }} <br>au {{ \Carbon\Carbon::parse($reservation->datefin)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Participants</td>
                    <td>{{ $reservation->nbpersonnes }} personne(s)</td>
                </tr>
                <tr>
                    <td>Montant total</td>
                    <td>{{ $reservation->prix }} €</td>
                </tr>
                <tr>
                    <td>Statut</td>
                    <td><span style="background-color: #e5e7eb; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;">{{ $reservation->statut }}</span></td>
                </tr>
            </table>

            <div class="client-info">
                <h3>Informations Client</h3>
                <p style="margin:8px 0; color: #4b5563;">{{ $reservation->client->prenom }} {{ $reservation->client->nom }}</p>
                <p style="margin:8px 0;"><a href="mailto:{{ $reservation->client->email }}" style="color: #2563eb; text-decoration: none;">{{ $reservation->client->email }}</a></p>
            </div>

            @if(isset($lienValidation))
            <div style="text-align: center; margin: 40px 0 20px;">
                <p style="margin-bottom: 20px; color: #4b5563; font-size: 15px;">Merci de confirmer la disponibilité :</p>
                
                <a href="{{ $lienValidation }}" class="btn-action">
                   Gérer la Disponibilité
                </a>
                
                <p style="font-size: 11px; color: #9ca3af; margin-top: 15px;">
                    Lien sécurisé à usage unique.
                </p>
            </div>
            @endif
            
        </div>

        <div class="footer">
            <p>Club Med - Système de Réservation Centralisé</p>
        </div>
    </div>

</body>
</html>