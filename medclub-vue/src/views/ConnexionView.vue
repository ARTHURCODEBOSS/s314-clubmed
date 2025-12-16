<script setup>
import { ref, onMounted  } from 'vue';
import { useRouter, useRoute } from 'vue-router'; 
import NavBar from '../components/NavBar.vue'

const route = useRoute();
const router = useRouter();
const email = ref('');
const password = ref('');
const message = ref('');
const messageError = ref('');
const lienServerLaravel = 'http://51.83.36.122:8039/api/login';
const reservation = localStorage.getItem('reservation');

const login = async () => {
  message.value = "Envoi en cours...";
  messageError.value = '';

  try {
    const response = await fetch(lienServerLaravel, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        email: email.value,
        password: password.value
      })
    });

    const data = await response.json();

    if (!response.ok) {
      message.value='';
      messageError.value = "Informations d'identification non valides";
    } else {
      // message.value = "Connexion réussie ! Token reçu.";
      console.log("Token:", data);
      localStorage.setItem('user_token', data.token);
      localStorage.setItem('user_infos', JSON.stringify(data.user));
      if(reservation){
        localStorage.setItem('reservation',false)
        router.push('/reservation');
      }
      else{
        router.push('/compte');
        
      }
      
    }

  } catch (error) {
    console.error(error);
    message.value = "Erreur réseau ou serveur injoignable.";
  }
};
onMounted(async () => {
    const token = localStorage.getItem('user_token');
    const response = await fetch('http://51.83.36.122:8039/api/user', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}` 
        }
    });
    if (response.ok) {
        const data = await response.json();
        console.log("Données sécurisées reçues :", data);
        if(reservation){
        localStorage.removeItem('reservation')
        router.push('/reservation');
      }
      else{
          router.push('/compte');
      }
        
    } else {
        console.log("Session expirée");
        localStorage.removeItem('user_token');
    }
});
</script>

<template>
  <NavBar />
  <div class="wrapped-connexion">
    <h1>Connexion</h1>
    
    <form @submit.prevent="login">
      <div v-if="messageError" class="error">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M480-280q17 0 28.5-11.5T520-320q0-17-11.5-28.5T480-360q-17 0-28.5 11.5T440-320q0 17 11.5 28.5T480-280Zm-40-160h80v-240h-80v240Zm40 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>
                    </svg>
                    <span c>{{ messageError}}</span>
            </div>
      <div>
        <label>Email</label><br>
        <input type="email" placeholder="Email" v-model="email" required>
      </div>
      
      <br>

      <div>
        <label>Mot de passe</label><br>
        <input type="password" v-model="password" placeholder="Mot de passe" required>
      </div>

      <br>

      <button type="submit">Se Connecter</button>
    </form>
    <RouterLink to="/inscription" class="se-connecter">
                Créer un Compte
    </RouterLink>
    <br>
    
    <div v-if="message">
      {{ message }}
    </div>
  </div>
</template>

<style scoped>

.wrapped-connexion {
  width: 350px;
  margin: 80px auto;
}

.wrapped-connexion h1 {
  text-align: center;
  font-family: 'Cormorant Garamond';
  font-size: 64px;
  font-weight: 100;
  font-style: italic;
  margin-bottom: 40px;
}

.wrapped-connexion label {
  font-weight: bold;
}

.wrapped-connexion input {
  height: 45px;
  width: 100%;
  border: 1px solid #ccc;
  outline: none;
  padding: 0 18px;
}

.wrapped-connexion input:focus {
  border-color: #000;
}

.wrapped-connexion button {
  display: block;
  outline: none;
  border: 1px solid #000;
  background-color: #000;
  padding: 12px 24px;
  color: #fff;
  font-size: 18px;
  font-weight: bold;
  margin: 40px auto 20px auto;
  transition: .2s;
  cursor: pointer;
}

.wrapped-connexion button:hover {
  background-color: #fff;
  color: #000;
}

.wrapped-connexion a {
  display: block;
  color:#000;
  margin: 0 auto;
  text-align: center;
}

.wrapped-connexion a:hover {
  text-decoration: none;
}

</style>