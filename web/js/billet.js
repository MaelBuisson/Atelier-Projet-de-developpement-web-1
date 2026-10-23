document.addEventListener('DOMContentLoaded', async () => {
    // 1. Récupération de l'ID de commande dans l'URL (?id=...) et du token de connexion
    const idCommande = new URLSearchParams(window.location.search).get('id');
    const container = document.getElementById('billets-container');
    const token = localStorage.getItem(TOKEN_KEY);

    // Sécurité : on bloque l'accès si l'utilisateur n'est pas connecté ou s'il manque l'ID
    if (!token || !idCommande) {
        container.innerHTML = "<p style='text-align:center; color:red;'>Erreur : Accès non autorisé ou commande introuvable.</p>";
        return;
    }

    // 2. Récupération du profil utilisateur (pour afficher le nom sur le billet)
    let nomComplet = "Billet Acheteur";
    try {
        const responseProfil = await fetch(`${API}/auth/me`, { 
            headers: { Authorization: `Bearer ${token}` }
        });
        
        if (responseProfil.ok) {
            const dataProfil = await responseProfil.json();
            
            // Adaptation à la structure de l'API pour extraire le prénom/nom ou l'email par défaut
            const user = dataProfil.utilisateur || dataProfil.user || dataProfil;
            const prenom = user.prenom || "";
            const nom = user.nom || "";
            
            if (prenom || nom) {
                nomComplet = `${prenom} ${nom}`.trim();
            } else if (user.email) {
                nomComplet = user.email;
            }
        }
    } catch (err) {
        console.warn("Impossible de récupérer le profil :", err);
    }

    // 3. Récupération de la commande et génération des billets
    try {
        const responseCommande = await fetch(`${API}/commandes/${encodeURIComponent(idCommande)}`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        if (!responseCommande.ok) throw new Error("Erreur serveur lors de la récupération de la commande");
        const dataCommande = await responseCommande.json();
        
        container.innerHTML = ''; // On vide le message de chargement

        // Boucle sur chaque ligne de la commande (ex: 2 billets Rock, 1 billet Electro)
        for (let indexLigne = 0; indexLigne < dataCommande.commande.lignes.length; indexLigne++) {
            const ligne = dataCommande.commande.lignes[indexLigne];
            
            let dateSoiree = "Date à confirmer";
            let heureSoiree = "Heure à confirmer";
            
            // 3.1. Récupération des détails de la soirée pour avoir la date et l'heure exactes
            try {
                const responseSoiree = await fetch(`${API}/soiree/${ligne.idSoiree}`);
                if (responseSoiree.ok) {
                    const json = await responseSoiree.json();
                    const soireeInfos = (json.data && json.data.soiree) ? json.data.soiree : (json.soiree || json);
                    
                    if (soireeInfos.date) dateSoiree = soireeInfos.date;
                    if (soireeInfos.horaire) heureSoiree = soireeInfos.horaire;
                }
            } catch (err) {
                console.warn(`Impossible de charger les détails de la soirée ${ligne.idSoiree}`, err);
            }

            // 3.2. Génération HTML : on crée autant de billets physiques que la 'quantite' demandée
            for (let i = 0; i < ligne.quantite; i++) {
                const nomTarif = ligne.tarif === 'reduit' ? 'Tarif réduit' : 'Tarif normal';
                
                // Génération d'une donnée unique pour le QR Code (Commande-Ligne-Index)
                const dataQr = `${idCommande}-${indexLigne}-${i}`;
                const urlQrCode = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(dataQr)}`;

                // Injection du template du billet
                const htmlBillet = `
                    <div class="billet">
                        <div class="billet-info">
                            <h2>FESTIVAL NRV</h2>
                            <h3>Soirée : ${ligne.nomSoiree}</h3>
                            <p><strong>Date :</strong> ${dateSoiree} à ${heureSoiree}</p>
                            <p><strong>Titulaire :</strong> ${nomComplet}</p>
                            <p><strong>Tarif :</strong> ${nomTarif}</p>
                            <p style="font-size: 0.8rem; color: #555; margin-top: 1rem;">Commande N° ${idCommande}</p>
                        </div>
                        <div class="billet-code">
                            <img src="${urlQrCode}" alt="QR Code du billet" width="120" height="120" style="object-fit: contain;">
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', htmlBillet);
            }
        }

        // 4. Lancement automatique de la fenêtre d'impression après 1.5s (le temps que les QR codes chargent)
        setTimeout(() => {
            window.print();
        }, 1500);

    } catch (e) {
        console.error(e);
        container.innerHTML = "<p style='text-align:center; color:red;'>Impossible de charger vos billets.</p>";
    }
});