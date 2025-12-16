<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle Réservation Partenaire</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #eaeff2; margin: 0; padding: 0; color: #333; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 6px; overflow: hidden; border: 1px solid #dce0e3; }
        .header { background-color: #2c3e50; color: #ffffff; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0; font-size: 14px; opacity: 0.8; }
        .content { padding: 30px; }
        .intro-text { font-size: 16px; color: #333; margin-bottom: 20px; line-height: 1.5; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: bold; color: #555; width: 40%; }
        .activity-box { margin-top: 20px; background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; }
        .activity-title { margin: 0 0 10px; color: #007bff; font-size: 16px; }
        .activity-list { margin: 0; padding-left: 0; color: #444; }
        .activity-item { margin-bottom: 5px; list-style: none; }
        .contact-section { margin-top: 30px; font-size: 14px; color: #888; text-align: center; }
        .contact-link { color: #007bff; text-decoration: none; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 11px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="header">
            <h1>Action Requise</h1>
            <p>Notification Partenaire - Club Med</p>
        </div>

        <div class="content">
            <p class="intro-text">Bonjour <strong>{{ $activitePartenaire->partenaire->nom ?? 'Partenaire' }}</strong>,</p>
            
            <p class="intro-text">
                Une nouvelle réservation a été confirmée pour votre activité <strong>{{ $activitePartenaire->titre }}</strong>.
                <br>Voici les détails pour la prise en charge du client :
            </p>

            <table class="info-table">
                <tr>
                    <td>Client</td>
                    <td>
                        {{ $reservation->client->prenom ?? 'Inconnu' }} {{ $reservation->client->nom ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td>Nombre de personnes</td>
                    <td style="font-size: 16px; font-weight: bold;">
                        {{ $reservation->nbpersonnes }} pers.
                    </td>
                </tr>
                <tr>
                    <td>Dates du séjour</td>
                    <td>
                        Du {{ \Carbon\Carbon::parse($reservation->datedebut)->format('d/m/Y') }} 
                        au {{ \Carbon\Carbon::parse($reservation->datefin)->format('d/m/Y') }}
                    </td>
                </tr>
                <tr>
                    <td>Lieu / Club</td>
                    <td>
                        {{ $reservation->club->titre ?? 'Club Med' }}
                    </td>
                </tr>
            </table>

            <div class="activity-box">
                <h3 class="activity-title">Activité à fournir</h3>
                <ul class="activity-list">
                    <li class="activity-item">
                        <strong style="font-size: 16px;">{{ $activitePartenaire->titre }}</strong> 
                        <br>
                        <span style="font-size: 14px; color: #555;">{{ $activitePartenaire->description }}</span>
                    </li>
                </ul>
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
            &copy; {{ date('Y') }} Club Med - Gestion des Partenaires
            <br>Réf Réservation: #{{ $reservation->numreservation }} | Réf Activité: #{{ $activitePartenaire->idactivite }}
        </div>
    </div>

</body>
</html>