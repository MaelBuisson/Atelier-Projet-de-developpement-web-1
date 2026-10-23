document.addEventListener('DOMContentLoaded', async () => {
    const idCommande = new URLSearchParams(window.location.search).get('id');
    const container = document.getElementById('billets-container');
    const token = localStorage.getItem(TOKEN_KEY);

    if (!token || !idCommande) {
        container.innerHTML = "<p style='text-align:center; color:red;'>Erreur : Accès non autorisé ou commande introuvable.</p>";
        return;
    }

    
    let nomComplet = "Billet Acheteur";
    try {
        const responseProfil = await fetch(`${API}/auth/me`, { 
            headers: { Authorization: `Bearer ${token}` }
        });
        
        if (responseProfil.ok) {
            const dataProfil = await responseProfil.json();
            
            
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

    try {
        const responseCommande = await fetch(`${API}/commandes/${encodeURIComponent(idCommande)}`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        if (!responseCommande.ok) throw new Error("Erreur serveur lors de la récupération de la commande");
        const dataCommande = await responseCommande.json();
        
        container.innerHTML = ''; 

        for (let indexLigne = 0; indexLigne < dataCommande.commande.lignes.length; indexLigne++) {
            const ligne = dataCommande.commande.lignes[indexLigne];
            
            let dateSoiree = "Date à confirmer";
            let heureSoiree = "Heure à confirmer";
            
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

            for (let i = 0; i < ligne.quantite; i++) {
                const nomTarif = ligne.tarif === 'reduit' ? 'Tarif réduit' : 'Tarif normal';
                const dataQr = `${idCommande}-${indexLigne}-${i}`;
                const urlQrCode = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(dataQr)}`;

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

        setTimeout(() => {
            window.print();
        }, 1500);

    } catch (e) {
        console.error(e);
        container.innerHTML = "<p style='text-align:center; color:red;'>Impossible de charger vos billets.</p>";
    }
});