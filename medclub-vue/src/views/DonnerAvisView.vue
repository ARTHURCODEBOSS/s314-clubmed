<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import NavBar from '../components/NavBar.vue'
const router = useRouter();

const note = ref(0)
const titreAvis = ref('')
const commentaire = ref('')
const resaStocke = localStorage.getItem('resa_avis')
const resaAvis = ref(null);
const avisCree = ref(false);
const erreurEnvoi = ref(false);


//champsAsaisir
const messageNote = ref('');
const messageTitre = ref('');
const messageCommentaire = ref('');

const retournerAuCompte = () => {
    router.push('/compte');
};

onMounted(async () => {
    const token = localStorage.getItem('user_token');
    const response = await fetch('http://51.83.36.122:8039/api/check-token', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}` 
        }
    });
    if (response.ok) {
        console.log("Session accepté")
    } else {
        console.log("Session expirée");
        localStorage.removeItem('user_token');
        router.push('/connexion');
    }
});
if (resaStocke) {
    try {
       resaAvis.value = JSON.parse(resaStocke);
       console.log(resaAvis.value)
    } catch (e) {
        console.error("Erreur de lecture du JSON", e);
    }
}
const register = async () => {
    let champSaisie = true;
    if (!titreAvis.value){
        messageTitre.value = "Veuillez saisir un titre";
        champSaisie = false;
    }
    else{
        messageTitre.value = '';
    }
    if(!commentaire.value){
        messageCommentaire.value = "La saisie du commentaire est obligatoire";
        champSaisie = false;
    }
    else{
        messageCommentaire.value = '';
    }
    if(note.value <= 0){
        messageNote.value = "La note est obligatoire";
        champSaisie = false;
    }
    else{
        messageNote.value = '';
    }
    if(!champSaisie){
        return;
    }
    console.log("a")
    try {
        const response = await fetch('http://51.83.36.122:8039/api/enregistrerAvis', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                idclub : resaAvis.value.idclub,
                numclient: resaAvis.value.numclient,
                numreservation: resaAvis.value.numreservation,
                titre: titreAvis.value,
                commentaire: commentaire.value,
                note: note.value
            })
        });

        const data = await response.json();

        if (response.ok) {
            console.log(data)
            avisCree.value = true; 
            erreurEnvoi.value  = false;
            localStorage.removeItem('resa_avis');
        } else {
            avisCree.value = false;
            erreurEnvoi.value  = true;
        }

    } catch (e) {
        console.error(e);
        message.value = "Erreur serveur";
    }
}

</script>

<template>
    <NavBar />
    
    <div class="avis-container">
        
        <header class="avis-header">
            <h1 class="club-title">{{ resaAvis.club.titre }}</h1>
            <p class="description">Comment s'est passé votre séjour ?</p>
        </header>

        <div class="avis-form-card">
            
            <!-- SECTION NOTE -->
            <div class="form-group rating-section">
                <div class="bloc-etoiles">
                    <span 
                        v-for="num in 5" 
                        :key="num" 
                        @click="note = num" 
                        class="etoile"
                        :class="{ 'active': num <= note }"
                    >
                        <!-- Utilisation de SVG pour des étoiles plus nettes -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="star-icon">
                            <path fill-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.007 5.404.433c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.433 2.082-5.006z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
                <p class="rating-text" :class="{ 'visible': note > 0 }">
                    <span v-if="note > 0">{{ note }}/5</span>
                    <span v-else>Sélectionnez une note</span>
                </p>
            </div>

            <div v-if="messageNote" class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="currentColor"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                <span>{{ messageNote }}</span>
            </div>

            <!-- SECTION TITRE -->
            <div class="form-group">
                <label for="titreAvis">Titre de votre avis</label>
                <input 
                    id="titreAvis"
                    v-model="titreAvis" 
                    type="text" 
                    class="input-clean"
                    placeholder="Quelques mots."
                >
            </div>

            <div v-if="messageTitre" class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="currentColor"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                <span>{{ messageTitre }}</span>
            </div>

            <!-- SECTION COMMENTAIRE -->
            <div class="form-group">
                <label for="commentaire">Votre commentaire</label>
                <textarea 
                    id="commentaire"
                    v-model="commentaire" 
                    class="input-clean textarea"
                    placeholder="Commentez les détails."
                    rows="4"
                ></textarea>
            </div>

            <div v-if="messageCommentaire" class="error-message">
                <svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 -960 960 960" width="18px" fill="currentColor"><path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>
                <span>{{ messageCommentaire }}</span>
            </div>

            <button @click="register" type="submit" class="btn-submit">
                Envoyer mon avis
            </button>
        </div>
    </div>

    <!-- MODALES / POPUPS -->
    <div v-if="avisCree" class="overlay">
        <div class="popup popup-success">
            <div class="icon-circle success">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="currentColor">
                    <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/>
                </svg>
            </div>
            <h2>Avis publié !</h2>
            <p>Merci d'avoir donner votre avis.</p>
            <button @click="retournerAuCompte" class="btn-popup">Retour au compte</button>
        </div>
    </div>

    <div v-if="erreurEnvoi" class="overlay">
        <div class="popup popup-error">
            <div class="icon-circle error">
                <svg xmlns="http://www.w3.org/2000/svg" height="40px" viewBox="0 -960 960 960" width="40px" fill="currentColor">
                    <path d="M256-213.85 213.85-256l224-224-224-224L256-746.15l224 224 224-224L746.15-704l-224 224 224 224L704-213.85l-224-224-224 224Z"/>
                </svg>
            </div>
            <h2>Annulé !</h2>
            <p>Vous avez déjà donné votre avis pour ce séjour.</p>
            <button @click="retournerAuCompte" class="btn-popup">Compris</button>
        </div>
    </div>

</template>

<style scoped>
.avis-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 0 20px;
    color: #1f2937;
}

.avis-header {
    text-align: center;
    margin-bottom: 40px;
}

.club-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 64px;
    font-weight: 300;
    font-style: italic;
    margin: 120px 0 10px 0;
}

.description {
    font-family: 'Cormorant Garamond', serif;
    font-size: 24px;
    font-style: italic;
    color: #777;
}

.form-group {
    margin-bottom: 24px;
}

.rating-section {
    text-align: center;
    margin-bottom: 30px;
}

.bloc-etoiles {
    display: inline-flex;
    gap: 8px;
    margin-bottom: 10px;
}

.etoile {
    cursor: pointer;
    transition: .2s ease;
}

.star-icon {
    width: 32px;
    height: 32px;
    color: #e5e7eb; 
    transition: .3s ease;
}

.etoile.active .star-icon {
    color: #fbbf24; 
}

.rating-text {
    font-size: 0.9rem;
    color: #9ca3af;
    font-weight: 500;
    transition: .3s;
}

.rating-text.visible {
    color: #1f2937;
}

label {
    display: block;
    color: #000;
    margin-bottom: 8px;
    font-weight: 600;
}

.input-clean {
    outline: none;
    width: 100%;
    padding: 12px 10px;
    border: 1px solid #ccc;
}

.input-clean:focus {
    border-color: #000;
}

.textarea {
    resize: vertical;
    min-height: 100px;
}

.btn-submit {
    width: 100%;
    background-color: #000;
    color: #fff;
    padding: 14px;
    border: 1px solid #000;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: .2s;
    margin-top: 10px;
    font-weight: bold;
}

.btn-submit:hover {
    color: #000;
    background-color: #fff;
}

.btn-submit:active {
    transform: translateY(0);
}

/* MESSAGES D'ERREUR */
.error-message {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ef4444;
    font-size: 0.85rem;
    margin-top: -16px; 
    margin-bottom: 16px;
    padding: 8px 12px;
    border-radius: 6px;
}

.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.popup {
    background: white;
    padding: 40px;
    text-align: center;
    width: 90%;
    max-width: 400px;
}

.icon-circle {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px auto;
}

.icon-circle.success {
    background-color: #d1fae5;
    color: #059669;
}

.icon-circle.error {
    background-color: #fee2e2;
    color: #dc2626;
}

.popup h2 {
    font-size: 1.5rem;
    color: #111827;
    margin-bottom: 8px;
    font-weight: 600;
}

.popup p {
    color: #6b7280;
    margin-bottom: 24px;
    line-height: 1.5;
}

.btn-popup {
    background-color: #000;
    color: white;
    border: 1px solid #000;
    padding: 10px 24px;
    font-size: 0.95rem;
    cursor: pointer;
    font-weight: bold;
    transition: .2s;
}

.btn-popup:hover {
    background-color: #fff;
    color: #000;
}
</style>