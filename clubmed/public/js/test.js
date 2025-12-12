async function getReservations() {
    try {
        // 1. L'appel vers votre API Laravel (attention au /api/)
        // ✅ BON (Cherche sur le serveur à distance)
        const response = await fetch('http://51.83.36.122:8044/api/adresses');

        // 2. Vérification : Est-ce que le serveur a répondu 200 OK ?
        if (!response.ok) {
            throw new Error(`Erreur HTTP ! statut : ${response.status}`);
        }

        // 3. Conversion de la réponse brute en JSON utilisable
        const data = await response.json();

        // 4. Utilisation des données (ici on les affiche dans la console)
        console.log("Mes réservations :", data);
        
        // Exemple : Afficher le nom de la première réservation si elle existe
        if(data.length > 0) {
             console.log("Premier client :", data[0].nom_client); // Adaptez 'nom_client' à vos colonnes
        }

    } catch (error) {
        // 5. Gestion des erreurs (serveur éteint, problème réseau, etc.)
        console.error("Impossible de récupérer les réservations :", error);
    }
}
async function getClubs() {
    try {
        // 1. L'appel vers votre API Laravel (attention au /api/)
        // ✅ BON (Cherche sur le serveur à distance)
        const response = await fetch('http://51.83.36.122:8044/api/clubs');

        // 2. Vérification : Est-ce que le serveur a répondu 200 OK ?
        if (!response.ok) {
            throw new Error(`Erreur HTTP ! statut : ${response.status}`);
        }

        // 3. Conversion de la réponse brute en JSON utilisable
        const data = await response.json();

        // 4. Utilisation des données (ici on les affiche dans la console)
        console.log("Mes clubs :", data);
        
        // Exemple : Afficher le nom de la première réservation si elle existe
        if(data.length > 0) {
             console.log("Premier client :", data[0].nom_client); // Adaptez 'nom_client' à vos colonnes
        }

    } catch (error) {
        // 5. Gestion des erreurs (serveur éteint, problème réseau, etc.)
        console.error("Impossible de récupérer les réservations :", error);
    }
}

getReservations();
getClubs();