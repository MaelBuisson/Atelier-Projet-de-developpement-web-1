
document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {

            e.preventDefault();

            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            loginError.hidden = true;

            try {
                const response = await fetch(`${API}/auth/signin`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    localStorage.setItem(TOKEN_KEY, data.token);

                    window.location.href = 'index.html';
                } else {
                    loginError.textContent = data.error || "Email ou mot de passe incorrect.";
                    loginError.hidden = false;
                }
            } catch (erreur) {
                console.error("Erreur de connexion :", erreur);
                loginError.textContent = "Impossible de joindre le serveur API.";
                loginError.hidden = false;
            }
        });
    }
});