document.addEventListener('DOMContentLoaded', async () => {
    const idCommande = new URLSearchParams(window.location.search).get('id');
    const container = document.getElementById('billets-container');
    const token = localStorage.getItem(TOKEN_KEY);

    if (!token || !idCommande) {
        container.innerHTML = "<p style='text-align:center; color:red;'>Erreur : Accès non autorisé ou commande introuvable.</p>";
        return;
    }

    try {
        const responseCommande = await fetch(`${API}/commandes/${encodeURIComponent(idCommande)}`, {
            headers: { Authorization: `Bearer ${token}` }
        });

        if (!responseCommande.ok) throw new Error("Erreur serveur lors de la récupération de la commande");
        const dataCommande = await responseCommande.json();
        
        container.innerHTML = ''; 

        dataCommande.commande.lignes.forEach((ligne, indexLigne) => {
            for (let i = 0; i < ligne.quantite; i++) {
                const nomTarif = ligne.tarif === 'reduit' ? 'Tarif réduit' : 'Tarif normal';
                
                
                const dataQr = `${idCommande}-${indexLigne}-${i}`;
                
                const urlQrCode = `https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=${encodeURIComponent(dataQr)}`;

                const htmlBillet = `
                    <div class="billet">
                        <div class="billet-info">
                            <h2>FESTIVAL NRV</h2>
                            <h3>Soirée : ${ligne.nomSoiree}</h3>
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
        });

        
        setTimeout(() => {
            window.print();
        }, 1000);

    } catch (e) {
        console.error(e);
        container.innerHTML = "<p style='text-align:center; color:red;'>Impossible de charger vos billets.</p>";
    }
});