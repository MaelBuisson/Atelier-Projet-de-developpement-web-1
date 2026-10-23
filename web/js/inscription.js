// On s'assure que la page est entièrement chargée avant d'attacher les événements
document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    const errorDiv = document.getElementById('register-error');
    const successDiv = document.getElementById('register-success');

    if (registerForm) {
        // Écoute de la soumission du formulaire
        registerForm.addEventListener('submit', async (e) => {
            
            // 1. Empêche le rechargement complet de la page (comportement par défaut d'un formulaire)
            e.preventDefault();

            // 2. Récupération des valeurs saisies dans les champs HTML
            const nom = document.getElementById('nom').value;
            const prenom = document.getElementById('prenom').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Réinitialisation de l'affichage des messages (on les masque)
            errorDiv.hidden = true;
            successDiv.hidden = true;

            try {
                // 3. Envoi de la requête POST à l'API pour créer le compte
                const reponse = await fetch(`${API}/auth/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, nom, prenom, password })
                });

                const data = await reponse.json();

                // 4. Traitement de la réponse selon le code HTTP renvoyé par l'API
                if (reponse.status === 201) {
                    // Code 201 (Created) : Inscription validée par le serveur
                    successDiv.hidden = false;
                    registerForm.reset(); // Vide les champs du formulaire

                    // Redirection vers l'accueil après un court délai pour laisser le temps de lire le message
                    setTimeout(() => {
                        window.location.href = 'index.html';
                    }, 2000);
                    
                } else if (reponse.status === 400 || reponse.status === 409) {
                    // Code 400 (Erreur de saisie) ou 409 (Email déjà utilisé)
                    // On affiche le message d'erreur précis renvoyé par le back-end
                    errorDiv.textContent = data.error; 
                    errorDiv.hidden = false;
                } else {
                    // Cas non prévu par la documentation de l'API
                    throw new Error(`Erreur inattendue : ${reponse.status}`);
                }
            } catch (erreur) {
                // Erreur réseau (ex: serveur éteint ou problème de connexion internet)
                console.error("Erreur d'inscription :", erreur);
                errorDiv.textContent = "Impossible de joindre le serveur API.";
                errorDiv.hidden = false;
            }
        });
    }
});