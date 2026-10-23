// On s'assure que le DOM est chargé avant d'attacher nos événements
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');

    if (loginForm) {
        // Écoute de la soumission du formulaire
        loginForm.addEventListener('submit', async (e) => {

            // 1. Empêcher le rechargement classique de la page
            e.preventDefault();

            // 2. Récupération des données saisies par l'utilisateur
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            // Masquer les éventuels messages d'erreur précédents
            loginError.hidden = true;

            try {
                // 3. Appel POST à l'API d'authentification
                const response = await fetch(`${API}/auth/signin`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                // 4. Traitement de la réponse
                if (response.ok) {
                    // Succès : L'API nous renvoie un Token JWT.
                    // On le sauvegarde dans le localStorage pour le réutiliser sur les routes protégées (ex: /panier)
                    localStorage.setItem(TOKEN_KEY, data.token);

                    // Redirection vers le programme une fois connecté
                    window.location.href = 'index.html';
                } else {
                    // Échec (ex: mauvais mot de passe) : Affichage de l'erreur renvoyée par l'API
                    loginError.textContent = data.error || "Email ou mot de passe incorrect.";
                    loginError.hidden = false;
                }
            } catch (erreur) {
                // Erreur réseau (ex: le serveur back-end est éteint)
                console.error("Erreur de connexion :", erreur);
                loginError.textContent = "Impossible de joindre le serveur API.";
                loginError.hidden = false;
            }
        });
    }
});