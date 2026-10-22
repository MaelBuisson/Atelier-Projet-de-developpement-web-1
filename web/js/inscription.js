document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.getElementById('register-form');
    const errorDiv = document.getElementById('register-error');
    const successDiv = document.getElementById('register-success');

    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nom = document.getElementById('nom').value;
            const prenom = document.getElementById('prenom').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            errorDiv.hidden = true;
            successDiv.hidden = true;

            try {
                const reponse = await fetch(`http://localhost:7080/auth/register`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, nom, prenom, password })
                });

                const data = await reponse.json();

                if (reponse.status === 201) {
                    successDiv.hidden = false;
                    registerForm.reset();
                } else if (reponse.status === 400 || reponse.status === 409) {
                    errorDiv.textContent = data.error; 
                    errorDiv.hidden = false;
                } else {
                    throw new Error(`Erreur inattendue : ${reponse.status}`);
                }
            } catch (erreur) {
                console.error("Erreur d'inscription :", erreur);
                errorDiv.textContent = "Impossible de joindre le serveur API.";
                errorDiv.hidden = false;
            }
        });
    }
});