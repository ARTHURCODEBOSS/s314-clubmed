<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle Réservation Partenaire</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0;">

    <div style="max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        
        <div style="background-color: #2c3e50; padding: 20px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px;">Action Requise</h1>
            <p style="margin: 5px 0 0; font-size: 14px;">Notification Partenaire - Club Med</p>
        </div>

        <div style="padding: 30px;">
            <p style="font-size: 16px; color: #333;">Bonjour <strong>{{ $activitePartenaire->partenaire->nom ?? 'Partenaire' }}</strong>,</p>
            
            <p style="font-size: 16px; color: #555; line-height: 1.5;">
                Une nouvelle réservation a été confirmée pour votre activité <strong>{{ $activitePartenaire->titre }}</strong>.
                <br>Voici les détails pour la prise en charge du client :
            </p>

            <table style="width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #eeeeee;">
                <tr style="background-color: #f9f9f9;">
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; font-weight: bold; color: #555;">Client</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; color: #333;">
                        {{ $reservation->client->prenom ?? 'Inconnu' }} {{ $reservation->client->nom ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; font-weight: bold; color: #555;">Nombre de personnes</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; color: #333; font-size: 18px; font-weight: bold;">
                        {{ $reservation->nbpersonnes }} pers.
                    </td>
                </tr>
                <tr style="background-color: #f9f9f9;">
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; font-weight: bold; color: #555;">Dates du séjour</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; color: #333;">
                        Du {{ \Carbon\Carbon::parse($reservation->datedebut)->format('d/m/Y') }} 
                        au {{ \Carbon\Carbon::parse($reservation->datefin)->format('d/m/Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; font-weight: bold; color: #555;">Lieu / Club</td>
                    <td style="padding: 12px; border-bottom: 1px solid #eeeeee; color: #333;">
                        {{ $reservation->club->titre ?? 'Club Med' }}
                    </td>
                </tr>
            </table>

            <div style="margin-top: 30px; background-color: #e8f4fd; padding: 15px; border-left: 5px solid #3498db; border-radius: 4px;">
                <h3 style="margin: 0 0 10px; color: #2980b9;">Activité à fournir</h3>
                <ul style="margin: 0; padding-left: 20px; color: #444;">
                    <li style="margin-bottom: 5px; list-style: none;">
                        <strong style="font-size: 18px;">{{ $activitePartenaire->titre }}</strong> 
                        <br>
                        <span style="font-size: 14px; color: #555;">{{ $activitePartenaire->description }}</span>
                    </li>
                </ul>
            </div>

            <p style="margin-top: 30px; font-size: 14px; color: #888; text-align: center;">
                Merci de valider la disponibilité pour ce créneau.<br>
                En cas de problème, contactez directement le manager du Club :<br>
                <a href="mailto:{{ $reservation->club->email ?? '' }}">{{ $reservation->club->email ?? 'Contact Club' }}</a>
            </p>
        </div>

        <div style="background-color: #eeeeee; padding: 15px; text-align: center; font-size: 12px; color: #777;">
            &copy; {{ date('Y') }} Club Med - Gestion des Partenaires
            <br>Réf Réservation: #{{ $reservation->numreservation }} | Réf Activité: #{{ $activitePartenaire->idactivite }}
        </div>
    </div>

</body>
</html>