// ==========================================
// 1. HELPERS HANDLEBARS (Formatage des données)
// ==========================================
// Les "helpers" sont des petites fonctions qui transforment les données brutes de l'API 
// avant de les afficher dans le template HTML (ex: {{dateLongue date}}).

// Transforme "2027-07-08" en "jeudi 8 juillet 2027" (T12:00 évite les bugs de fuseau horaire)
Handlebars.registerHelper('dateLongue', date => new Date(`${date}T12:00:00`).toLocaleDateString(
    'fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }
));

// Transforme le nombre 35 en "35,00 €"
Handlebars.registerHelper('prix', prix =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(prix)
);

// Traduit les codes de l'API en texte lisible
Handlebars.registerHelper('nomTarif', nom => (nom === 'reduit' ? 'Tarif réduit' : 'Tarif normal'));

// Transforme un tableau ['Artiste 1', 'Artiste 2'] en chaîne "Artiste 1, Artiste 2"
Handlebars.registerHelper('join', liste => liste.join(', '));


// ==========================================
// 2. CHARGEMENT DE LA SOIRÉE DEPUIS L'API
// ==========================================
const container = document.querySelector('.soiree-container');
// On compile le template HTML (situé dans soiree.html) pour qu'il soit prêt à recevoir les données
const template = Handlebars.compile(document.getElementById('tpl-soiree').innerHTML);

// Affiche un message proprement si la soirée n'existe pas ou si l'API plante
function afficherErreur(message) {
    container.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'erreur';
    p.textContent = message;
    container.appendChild(p);
}

async function chargerSoiree() {
    // Récupération de l'ID de la soirée dans l'URL (ex: soiree.html?id=123)
    const id = new URLSearchParams(location.search).get('id');
    if (!id) {
        afficherErreur('Aucune soirée sélectionnée.');
        return;
    }

    try {
        const response = await fetch(`${API}/soiree/${encodeURIComponent(id)}`);

        if (response.status === 404 || response.status === 400) {
            afficherErreur("Cette soirée n'existe pas.");
            return;
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        
        // Mise à jour du titre de l'onglet du navigateur
        document.title = `${data.soiree.nom} - NRV`;
        
        // Injection des données de l'API dans le template, puis affichage dans le conteneur
        container.innerHTML = template(data.soiree);
        
        // Une fois le HTML généré, on active la logique d'ajout au panier
        brancherAjoutPanier(id);
    }
    catch (e) {
        console.error(e);
        afficherErreur('Impossible de charger la soirée, réessayez plus tard.');
    }
}

// Lancement automatique au chargement du script
chargerSoiree();


// ==========================================
// 3. GESTION DU MENU MOBILE (Burger)
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
    const burgerBtn = document.getElementById('burger-btn');
    const mainNav = document.getElementById('main-nav');

    if (burgerBtn && mainNav) {
        burgerBtn.addEventListener('click', () => {
            burgerBtn.classList.toggle('active');
            mainNav.classList.toggle('active');
        });
    }
});


// ==========================================
// 4. LOGIQUE D'AJOUT AU PANIER
// ==========================================
function brancherAjoutPanier(idSoiree) {
    const form = document.getElementById('form-ajout');
    if (!form) return; // Si la soirée n'a pas de tarif (ex: gratuite ou complète), on arrête là

    const messageAjout = document.getElementById('ajout-message');
    const valeur = document.getElementById('qte-valeur');
    const moins = document.getElementById('qte-moins');
    const plus = document.getElementById('qte-plus');
    const bouton = document.getElementById('btn-ajout');
    let quantite = 1;

    // Récupère le tarif actuellement sélectionné par l'utilisateur (radio button)
    const tarifChoisi = () => form.querySelector('input[name="ajout-tarif"]:checked');

    // Met à jour visuellement le compteur et le texte du bouton
    function majAffichage() {
        valeur.textContent = quantite;
        moins.disabled = quantite <= 1; // On ne peut pas commander moins de 1 billet
        bouton.textContent = `🛒 Ajouter ${quantite} billet${quantite > 1 ? 's' : ''}`;
    }

    function afficherAjout(texte, erreur) {
        messageAjout.textContent = texte; 
        messageAjout.className = erreur ? 'message message-error' : 'message message-success';
        messageAjout.hidden = false;
    }

    // Gestion des boutons + et -
    moins.addEventListener('click', () => {
        if (quantite > 1) {
            quantite--;
            majAffichage();
        }
    });

    plus.addEventListener('click', () => {
        quantite++;
        majAffichage();
    });

    // Envoi de l'ajout au panier vers l'API
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Sécurité : l'utilisateur doit être connecté pour avoir un panier
        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) {
            window.location.href = 'connexion.html';
            return;
        }

        messageAjout.hidden = true;
        bouton.disabled = true; // Empêche les clics multiples pendant l'envoi

        try {
            const response = await fetch(`${API}/panier/lignes`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${token}`,
                },
                body: JSON.stringify({ idSoiree, tarif: tarifChoisi().value, quantite }),
            });

            // Si le token est expiré, on renvoie vers la connexion
            if (response.status === 401) { 
                localStorage.removeItem(TOKEN_KEY);
                window.location.href = 'connexion.html';
                return;
            }

            let data = {};
            try { data = await response.json(); } catch {}

            // Succès
            if (response.ok) {
                const nb = data.panier.nbBillets;
                afficherAjout(`Ajouté. Votre panier contient ${nb} billet${nb > 1 ? 's' : ''}.`, false);
                quantite = 1; // On réinitialise la quantité à 1 après l'ajout
                mettreAJourNavigation(); // Met à jour le (0) de la navbar (défini dans script.js)
            } else {
                // Erreur (ex: stock insuffisant)
                afficherAjout(data.error || data.message || `Erreur ${response.status}`, true);
            }
        } catch (err) {
            console.error(err);
            afficherAjout('Impossible de joindre le serveur API.', true);
        } finally {
            bouton.disabled = false;
            majAffichage();
        }
    });

    // Initialisation de l'affichage au démarrage
    majAffichage();
}