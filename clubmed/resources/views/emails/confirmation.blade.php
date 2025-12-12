<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle Réservation - Espace Manager</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #eaeff2; margin: 0; padding: 0; color: #333; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 6px; overflow: hidden; border: 1px solid #dce0e3; }
        .header { background-color: #2c3e50; color: #ffffff; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .content { padding: 30px; }
        .alert-box { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .info-table td { padding: 12px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: bold; color: #555; width: 40%; }
        .client-info { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin-top: 20px; }
        .btn-container { text-align: center; margin-top: 30px; }
        .btn { background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; }
        .footer { background-color: #f8f9fa; text-align: center; padding: 15px; font-size: 11px; color: #999; border-top: 1px solid #eee; }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>📂 Nouvelle Réservation Reçue</h1>
        </div>

        <div class="content">
            <p>Bonjour l'équipe du <strong>{{ $reservation->club->titre ?? 'Club' }}</strong>,</p>
            
            <p>Une nouvelle demande de réservation vient d'être effectuée sur la plateforme. Merci de la traiter dans les plus brefs délais.</p>

            <div class="alert-box">
                Dossier N° {{ $reservation->numreservation }}
            </div>

            <table class="info-table">
                <tr>
                    <td>Dates du séjour :</td>
                    <td>Du {{ \Carbon\Carbon::parse($reservation->datedebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($reservation->datefin)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td>Nombre de personnes :</td>
                    <td>{{ $reservation->nbpersonnes }} personne(s)</td>
                </tr>
                <tr>
                    <td>Montant total :</td>
                    <td>{{ $reservation->prix }} €</td>
                </tr>
                <tr>
                    <td>Statut actuel :</td>
                    <td><strong>{{ $reservation->statut }}</strong></td>
                </tr>
            </table>

            <div class="client-info">
                <h3 style="margin-top:0; font-size:16px;">👤 Informations Client</h3>
                <p style="margin:5px 0;"><strong>Nom :</strong> {{ $reservation->client->prenom }} {{ $reservation->client->nom }}</p>
                <p style="margin:5px 0;"><strong>Email :</strong> <a href="mailto:{{ $reservation->client->email }}">{{ $reservation->client->email }}</a></p>
            </div>

            @if(isset($lienValidation))
            <div style="text-align: center; margin: 30px 0;">
                <p style="margin-bottom: 15px; color: #555;">Veuillez confirmer si vous avez de la place :</p>
                
                <a href="{{ $lienValidation }}" 
                style="background-color: #27ae60; color: white; padding: 14px 28px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;">
                 Gérer la Disponibilité
                </a>
                
                <p style="font-size: 12px; color: #999; margin-top: 10px;">
                    Ce lien est sécurisé et unique à cette réservation.
                </p>
            </div>
            @endif
            
        </div>

        <div class="footer">
            <p>Email généré automatiquement par le système central Club Med.</p>
        </div>
    </div>

</body>
</html>